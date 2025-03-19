<?php
header('Content-Type: application/json');

// Include your database connection
require '../db.php';

// Check if 'id' is provided
if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Missing additional fee ID.']);
    exit;
}

$feeId = intval($_GET['id']);

// Fetch the additional fee by ID
$stmt = $conn->prepare("SELECT * FROM additional_fee WHERE id = ?");
if ($stmt === false) {
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}
$stmt->bind_param("i", $feeId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Additional fee not found.']);
    exit;
}

$fee = $result->fetch_assoc();

// Extract payment_id and registration_date from the fetched fee
$payment_id = $fee['payment_id'];
$registration_date = $fee['registration_date'];

// Fetch all additional fees with the same payment_id and registration_date
$stmt = $conn->prepare("SELECT * FROM additional_fee WHERE payment_id = ? AND registration_date = ?");
if ($stmt === false) {
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}
$stmt->bind_param("is", $payment_id, $registration_date);
$stmt->execute();
$feesResult = $stmt->get_result();

$additional_fees = [];
while ($row = $feesResult->fetch_assoc()) {
    $additional_fees[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'price' => $row['price'],
        'description' => $row['description'],
        'registration_date' => $row['registration_date'],
        'payment_id' => $row['payment_id']
    ];
}

// Fetch related payment details
$stmt = $conn->prepare("SELECT * FROM payment WHERE id = ?");
if ($stmt === false) {
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$paymentResult = $stmt->get_result();

if ($paymentResult->num_rows === 0) {
    echo json_encode(['error' => 'Related payment not found.']);
    exit;
}

$payment = $paymentResult->fetch_assoc();

// Prepare the response
$response = [
    'additional_fees' => $additional_fees,
    'payment_details' => [
        'tenant' => $payment['tenant'],
        'tenant_tin' => $payment['tenant_tin'],
        'room' => $payment['room'],
        'fs_number' => $payment['fs_number'],
        'paid_months' => $payment['paid_months'],
        'paid_days' => $payment['paid_days'],
        'payment_method' => $payment['payment_method'],
        'bank_name' => $payment['bank_name'],
        'transaction_no' => $payment['transaction_no'],
        'cheque_ref_no' => $payment['cheque_ref_no'],
        'rent_amount' => $payment['rent_amount'],
        'price' => $payment['price'],
        'deposited_amount' => $payment['deposited_amount'],
        'vat' => $payment['vat'],
        'withhold' => $payment['withhold'],
        'discount' => $payment['discount'],
        'penality' => $payment['penality'],
        'total' => $payment['total'],
        'rent_due_date' => $payment['rent_due_date'],
        'payment_date' => $payment['payment_date'],
        'received_by' => $payment['received_by']
    ]
];

echo json_encode($response);
?>