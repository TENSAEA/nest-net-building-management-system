<?php
// get_tenant.php
header('Content-Type: application/json');
require '../db.php'; // Include your database connection

// Start output buffering
ob_start();

// Priority: tenant_id over id
if(isset($_GET['tenant_id'])) {
    $tenant_id = intval($_GET['tenant_id']);
} elseif(isset($_GET['id'])) {
    $tenant_id = intval($_GET['id']);
} else {
    $tenant_id = null;
}

if($tenant_id !== null) {
    // Fetch tenant data
    $stmt = $conn->prepare("SELECT * FROM tenant WHERE tenant_id = ?");
    $stmt->bind_param("i", $tenant_id);

    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        $tenant = $result->fetch_assoc();

        $response = [
            'status' => 'success',
            'data' => $tenant
        ];

        // Clear the buffer and output JSON
        ob_end_clean();
        echo json_encode($response);
    } else {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Tenant not found']);
    }

    $stmt->close();
} else {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'No tenant ID provided']);
}

$conn->close();
?>