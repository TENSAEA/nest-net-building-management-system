<?php
// Include database connection
include '../db.php'; // Adjust the path as necessary

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Function to fetch tenant by ID
function getTenantById($conn, $tenantId) {
    $stmt = $conn->prepare("SELECT tin_no, room, rent_due_date, rent_amount, term_of_payment FROM tenant WHERE tenant_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $tenantId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            return $result->fetch_assoc();
        }
        $stmt->close();
    }
    return false;
}

// Function to fetch all tenants
function getAllTenants($conn) {
    $sql = "SELECT tenant_id, full_name FROM tenant";
    $result = $conn->query($sql);

    if ($result) {
        $tenants = [];
        while ($row = $result->fetch_assoc()) {
            $tenants[] = [
                'id' => $row['tenant_id'],
                'name' => htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') // Sanitize output
            ];
        }
        return $tenants;
    }
    return false;
}

// Determine the tenant ID from GET parameters
$tenantId = 0;
if (isset($_GET['tenant_id'])) {
    $tenantId = intval($_GET['tenant_id']);
} elseif (isset($_GET['id'])) {
    $tenantId = intval($_GET['id']);
}

if ($tenantId > 0) {
    // Fetch specific tenant details
    $tenant = getTenantById($conn, $tenantId);
    if ($tenant) {
        echo json_encode([
            'success' => true,
            'tenant' => $tenant
        ]);
    } else {
        // Tenant not found
        echo json_encode([
            'success' => false,
            'message' => 'Tenant not found.'
        ]);
    }
} else {
    // Fetch list of all tenants
    $tenants = getAllTenants($conn);
    if ($tenants !== false) {
        echo json_encode([
            'success' => true,
            'tenants' => $tenants
        ]);
    } else {
        // Failed to retrieve tenants
        echo json_encode([
            'success' => false,
            'message' => 'Failed to retrieve tenants.'
        ]);
    }
}

$conn->close();
?>