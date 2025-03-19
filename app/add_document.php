<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $documentId = isset($_POST['document_id']) ? $_POST['document_id'] : null;
    $userId = $_POST['user_id'];
    $title = $_POST['title'];
    $category = $_POST['category'];
    $addedBy = $_POST['added_by'];

    $uploadDir = __DIR__ . "/uploads/tenant_$userId/";

    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Handle file upload
    $fileUploaded = false;
    $documentArray = [];
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['document']['tmp_name'];
        $fileName = basename($_FILES['document']['name']);
        $fileSize = $_FILES['document']['size'];
        $fileType = mime_content_type($fileTmpPath);

        // Validate file type and size
        $allowedTypes = [
            'application/pdf' => 'pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
        ];
        if (!array_key_exists($fileType, $allowedTypes) || $fileSize > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'Invalid file type or size. Only PDF and DOCX files under 5 MB are allowed.']);
            exit;
        }

        // Generate a unique file name to prevent overwriting
        $fileExtension = $allowedTypes[$fileType];
        $newFileName = uniqid() . '.' . $fileExtension;
        $destPath = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $fileUploaded = true;
            // Prepare JSON data for the document field
            $documentArray[] = [
                'name' => "tenant_$userId/$newFileName",
                'usrName' => $fileName,
                'size' => $fileSize,
                'type' => $fileType,
                'searchStr' => "$fileName,!:sStrEnd"
            ];
        } else {
            echo json_encode(['success' => false, 'error' => 'Error moving the uploaded file.']);
            exit;
        }
    }

    if ($documentId) {
        // Edit existing document
        if ($fileUploaded) {
            $stmt = $conn->prepare("UPDATE document SET user_id = ?, title = ?, category = ?, document = ?, added_by = ?, updated_date = NOW() WHERE document_id = ?");
            $documentJson = json_encode($documentArray);
            $stmt->bind_param("issssi", $userId, $title, $category, $documentJson, $addedBy, $documentId);
        } else {
            $stmt = $conn->prepare("UPDATE document SET user_id = ?, title = ?, category = ?, added_by = ?, updated_date = NOW() WHERE document_id = ?");
            $stmt->bind_param("isssi", $userId, $title, $category, $addedBy, $documentId);
        }
    } else {
        // Add new document
        if ($fileUploaded) {
            $stmt = $conn->prepare("INSERT INTO document (user_id, title, category, document, added_by, added_date) VALUES (?, ?, ?, ?, ?, NOW())");
            $documentJson = json_encode($documentArray);
            $stmt->bind_param("issss", $userId, $title, $category, $documentJson, $addedBy);
        } else {
            echo json_encode(['success' => false, 'error' => 'No file uploaded.']);
            exit;
        }
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database operation failed.']);
    }

    $stmt->close();
}
?>