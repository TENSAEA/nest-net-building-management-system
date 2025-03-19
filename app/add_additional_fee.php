<?php
// Start output buffering
ob_start();

// Include the database connection
include '../db.php';

// Set the response header to JSON
header('Content-Type: application/json');

// Function to sanitize input data
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Function to log and respond with errors
function respond_with_error($message) {
    error_log($message);
    // Clear output buffer to prevent mixed content
    ob_clean();
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

// Retrieve the raw POST data
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

// Check for JSON decoding errors
if (json_last_error() !== JSON_ERROR_NONE) {
    respond_with_error('Invalid JSON data: ' . json_last_error_msg());
}

// Validate required fields
if (!isset($data['payment_id']) || !is_array($data['additional_fees'])) {
    respond_with_error('Missing required fields: payment_id or additional_fees.');
}

$payment_id = intval($data['payment_id']);
$additional_fees = $data['additional_fees'];

// Prepare the SQL statement
$sql = "INSERT INTO additional_fee (payment_id, title, price, description, registration_date) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    respond_with_error('SQL Prepare Failed: ' . $conn->error);
}

// Bind parameters and execute for each additional fee
foreach ($additional_fees as $index => $fee) {
    // Validate each fee's required fields
    if (!isset($fee['title'], $fee['price'], $fee['description'], $fee['registration_date'])) {
        respond_with_error("Missing fields in additional fee entry at index {$index}.");
    }

    $title = sanitize_input($fee['title']);
    $price = floatval($fee['price']);
    $description = sanitize_input($fee['description']);
    $registration_date = sanitize_input($fee['registration_date']);

    // Bind parameters
    if (!$stmt->bind_param("isdss", $payment_id, $title, $price, $description, $registration_date)) {
        respond_with_error("Binding parameters failed for entry at index {$index}: " . $stmt->error);
    }

    // Execute the statement
    if (!$stmt->execute()) {
        respond_with_error("Execute failed for entry at index {$index}: (" . $stmt->errno . ") " . $stmt->error);
    }
}

// Close the statement and connection
$stmt->close();
$conn->close();

// Clear the output buffer and send the success response
ob_clean();
echo json_encode(['success' => true]);

// End output buffering
ob_end_flush();