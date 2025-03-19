<?php
include '../db.php'; // Include your database connection

// Fetch all tenants
$tenant_sql = "SELECT tenant_id, term_of_payment FROM tenant";
$tenant_result = $conn->query($tenant_sql);
$tenants = $tenant_result->fetch_all(MYSQLI_ASSOC);

foreach ($tenants as $tenant) {
    $tenant_id = $tenant['tenant_id'];
    $term_of_payment = $tenant['term_of_payment'];

    // Fetch the last payment date for the tenant
    $sql = "SELECT payment_date FROM payment WHERE tenant = ? ORDER BY payment_date DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $last_payment = $result->fetch_assoc();
    $stmt->close();

    if ($last_payment) {
        $last_payment_date = $last_payment['payment_date'];
        // Calculate the next payment date
        $next_payment_date = date('Y-m-d', strtotime("+$term_of_payment months", strtotime($last_payment_date)));

        // Update the tenant table with the last and next payment dates
        $update_sql = "UPDATE tenant SET last_payment_date = ?, next_payment_date = ? WHERE tenant_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssi", $last_payment_date, $next_payment_date, $tenant_id);
        $update_stmt->execute();
        $update_stmt->close();
    }
}

echo json_encode(['status' => 'success']);
?>