<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $documentId = isset($_POST['document_id']) ? $_POST['document_id'] : null;
    $tenantId = $_POST['user_id'];
    $title = $_POST['title'];
    $category = $_POST['category'];
    $addedBy = $_POST['added_by'];

    $uploadDir = __DIR__ . "/uploads/tenant_$tenantId/";

    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Handle file upload
    $fileUploaded = false;
    $destPath = null;
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['document']['tmp_name'];
        $fileName = $_FILES['document']['name'];
        $destPath = $uploadDir . $fileName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $fileUploaded = true;
        } else {
            echo json_encode(['success' => false, 'error' => 'Error moving the uploaded file.']);
            exit;
        }
    }

    if ($documentId) {
        // Edit existing document
        if ($fileUploaded) {
            $stmt = $conn->prepare("UPDATE documents SET tenant_id = ?, title = ?, category = ?, document_path = ?, added_by = ? WHERE document_id = ?");
            $stmt->bind_param("issssi", $tenantId, $title, $category, $destPath, $addedBy, $documentId);
        } else {
            $stmt = $conn->prepare("UPDATE documents SET tenant_id = ?, title = ?, category = ?, added_by = ? WHERE document_id = ?");
            $stmt->bind_param("isssi", $tenantId, $title, $category, $addedBy, $documentId);
        }
    } else {
        // Add new document
        $stmt = $conn->prepare("INSERT INTO documents (tenant_id, title, category, document_path, added_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $tenantId, $title, $category, $destPath, $addedBy);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database operation failed.']);
    }

    $stmt->close();
}
?>