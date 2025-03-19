<?php
header('Content-Type: application/json');

// Include database connection
include '../db.php'; // Adjust the path as necessary

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Tenant Property ID not provided.']);
    exit;
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

$query = "SELECT tp.id, tp.tenant, t.room, tp.tenants_property_no
          FROM tenants_property tp
          JOIN tenant t ON tp.tenant = t.tenant_id
          WHERE tp.id = '$id' LIMIT 1";
$result = mysqli_query($conn, $query);

if ($result) {
    if (mysqli_num_rows($result) > 0) {
        $tenant_property = mysqli_fetch_assoc($result);
        
        // Fetch property details
        $details_query = "SELECT property_name, quantity, description FROM tenants_property_details WHERE tenants_property_no = '$id'";
        $details_result = mysqli_query($conn, $details_query);
        
        $details = [];
        if ($details_result) {
            while ($row = mysqli_fetch_assoc($details_result)) {
                $details[] = $row;
            }
        }

        echo json_encode([
            'success' => true,
            'id' => $tenant_property['id'],
            'tenant_id' => $tenant_property['tenant'],
            'room' => $tenant_property['room'],
            'tenants_property_no' => $tenant_property['tenants_property_no'],
            'details' => $details
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Tenant Property not found.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Database query failed.']);
}
?>