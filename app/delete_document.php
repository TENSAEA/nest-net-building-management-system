<?php
// delete_document.php
header('Content-Type: application/json');

// Include database connection
include '../db.php';

// Function to get input data for DELETE request
function getDeleteData() {
    parse_str(file_get_contents("php://input"), $vars);
    return $vars;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = getDeleteData();
    if (!isset($data['id'])) {
        echo json_encode(['success' => false, 'error' => 'No document ID provided.']);
        exit;
    }

    $documentId = intval($data['id']);

    // Fetch the document entry to get the file path
    $stmt = $conn->prepare("SELECT document FROM document WHERE document_id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Database preparation failed.']);
        exit;
    }
    $stmt->bind_param("i", $documentId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Document not found.']);
        exit;
    }

    $document = $result->fetch_assoc();

    // Decode the JSON data from the 'document' field
    $documentData = json_decode($document['document'], true);

    if (!is_array($documentData) || !isset($documentData[0]['name'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid document data.']);
        exit;
    }

    $documentPath = $documentData[0]['name']; // e.g., "tenant_510/file.pdf"

    // Construct the server file path
    $serverPath = __DIR__ . "/uploads/" . $documentPath;

    // Attempt to delete the file from the server
    if (file_exists($serverPath)) {
        if (!unlink($serverPath)) {
            echo json_encode(['success' => false, 'error' => 'Failed to delete the file from the server.']);
            exit;
        }
    } else {
        // Optionally log that the file was not found
        // error_log("File not found: " . $serverPath);
    }

    // Delete the document entry from the database
    $deleteStmt = $conn->prepare("DELETE FROM document WHERE document_id = ?");
    if (!$deleteStmt) {
        echo json_encode(['success' => false, 'error' => 'Database deletion preparation failed.']);
        exit;
    }
    $deleteStmt->bind_param("i", $documentId);
    if ($deleteStmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        // Optionally log the database error
        // error_log("Database deletion failed for document ID: " . $documentId);
        echo json_encode(['success' => false, 'error' => 'Failed to delete the document from the database.']);
    }

    $stmt->close();
    $deleteStmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
?>