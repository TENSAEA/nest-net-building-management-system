<?php
include '../db.php';

if (isset($_GET['id'])) {
    $document_id = $_GET['id'];

    $sql = "SELECT * FROM document WHERE document_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $document_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $document = $result->fetch_assoc();

    echo json_encode($document);
}
?>