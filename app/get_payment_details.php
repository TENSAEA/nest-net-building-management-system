<?php
// get_payment_details.php

// **For Debugging Only:** Enable error reporting (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
include "../db.php";

// Set response header to JSON
header('Content-Type: application/json');

// Check database connection
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

// Get payment ID from URL and validate it
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid payment ID.']);
    exit;
}

$payment_id = intval($_GET['id']);

// Prepare SQL statement to fetch payment details
$query = "
SELECT 
    p.id, 
    t.full_name AS tenant, 
    p.tenant_tin, 
    p.room, 
    p.fs_number, 
    p.paid_months, 
    p.paid_days, 
    p.payment_method, 
    p.bank_name, 
    p.transaction_no, 
    p.cheque_ref_no, 
    p.price, 
    p.deposited_amount, 
    p.vat, 
    p.withhold, 
    p.discount, 
    p.penality, 
    p.total, 
    p.rent_due_date, 
    p.payment_date, 
    p.received_by,
    t.contract_period_starts, 
    t.last_payment_date,
    t.next_payment_date,
    t.term_of_payment  -- Added term_of_payment here
FROM payment p
JOIN tenant t ON p.tenant = t.tenant_id
WHERE p.id = ?
";

$stmt = $conn->prepare($query);

if ($stmt === false) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $payment_id);

if (!$stmt->execute()) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Execute failed: ' . $stmt->error]);
    exit;
}

// Bind result variables
$stmt->bind_result(
    $id, $tenant, $tenant_tin, $room, $fs_number, 
    $paid_months, $paid_days, $payment_method, $bank_name, 
    $transaction_no, $cheque_ref_no, $price, $deposited_amount, 
    $vat, $withhold, $discount, $penality, $total, 
    $rent_due_date, $payment_date, $received_by,
    $contract_period_starts, $last_payment_date,
    $next_payment_date, $term_of_payment  // Bind the new term_of_payment variable
);

$payment = [];

if ($stmt->fetch()) {
    $payment = [
        'id' => $id,
        'tenant' => $tenant,
        'tenant_tin' => $tenant_tin,
        'room' => $room,
        'fs_number' => $fs_number,
        'paid_months' => $paid_months,
        'paid_days' => $paid_days,
        'payment_method' => $payment_method,
        'bank_name' => $bank_name,
        'transaction_no' => $transaction_no,
        'cheque_ref_no' => $cheque_ref_no,
        'price' => floatval($price),
        'deposited_amount' => floatval($deposited_amount),
        'vat' => floatval($vat),
        'withhold' => floatval($withhold),
        'discount' => floatval($discount),
        'penality' => floatval($penality),
        'total' => floatval($total),
        'rent_due_date' => $rent_due_date,
        'payment_date' => $payment_date,
        'received_by' => $received_by,
        'contract_period_starts' => $contract_period_starts,
        'last_payment_date' => $last_payment_date,
        'next_payment_date' => $next_payment_date,
        'term_of_payment' => intval($term_of_payment)  // Ensure it's an integer
    ];
} else {
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'Payment not found.']);
    exit;
}

$stmt->close();
$conn->close();

// Output the payment details as JSON
echo json_encode($payment);
?>