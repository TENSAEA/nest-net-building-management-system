<?php
include '../db.php'; // Updated path to the database connection file

if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['id'])) {
    $tenantId = $_GET['id'];
    $sql = "DELETE FROM tenant WHERE tenant_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $tenantId);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}
?>