<?php
header('Content-Type: application/json');

// Include database connection
include '../db.php'; // Adjust the path as necessary

// Check if tenant_id is provided
if (!isset($_GET['tenant_id']) || empty($_GET['tenant_id'])) {
    echo json_encode(['success' => false, 'error' => 'Tenant ID not provided.']);
    exit;
}

$tenant_id = mysqli_real_escape_string($conn, $_GET['tenant_id']);

// Query to fetch room based on tenant_id
$query = "SELECT room FROM tenant WHERE tenant_id = '$tenant_id' LIMIT 1";
$result = mysqli_query($conn, $query);

if ($result) {
    if (mysqli_num_rows($result) > 0) {
        $tenant = mysqli_fetch_assoc($result);
        echo json_encode(['success' => true, 'room' => $tenant['room']]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Tenant not found.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Database query failed.']);
}
?>