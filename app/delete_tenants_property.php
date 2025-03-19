<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    parse_str(file_get_contents("php://input"), $data);
    $id = $data['id'];

    // Fetch tenants_property_no
    $sql = "SELECT tenants_property_no FROM tenants_property WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $tenant_property = $result->fetch_assoc();
    $tenants_property_no = $tenant_property['tenants_property_no'];

    // Delete from tenant_property table
    $sql = "DELETE FROM tenants_property WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Delete from tenants_property_details table
        $sql = "DELETE FROM tenants_property_details WHERE tenants_property_no = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $tenants_property_no);
        $stmt->execute();

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
}
?>