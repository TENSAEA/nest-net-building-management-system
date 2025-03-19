<?php
// add_tenant.php

// Enable error reporting for debugging (Disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set the response header to JSON
header('Content-Type: application/json');

// Initialize response array
$response = ['success' => false, 'message' => ''];

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit();
}

// Function to sanitize input data
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Retrieve and sanitize POST data
$full_name = isset($_POST['full_name']) ? sanitize_input($_POST['full_name']) : '';
$company_name = isset($_POST['company_name']) ? sanitize_input($_POST['company_name']) : '';
$tin_no = isset($_POST['tin_no']) ? sanitize_input($_POST['tin_no']) : '';
$mobile_no = isset($_POST['mobile_no']) ? sanitize_input($_POST['mobile_no']) : '';
$email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
$address = isset($_POST['address']) ? sanitize_input($_POST['address']) : '';
$building = isset($_POST['building']) ? sanitize_input($_POST['building']) : '';
$floor = isset($_POST['floor']) ? sanitize_input($_POST['floor']) : '';
$room = isset($_POST['room']) ? sanitize_input($_POST['room']) : '';
$rent_amount = isset($_POST['rent_amount']) ? sanitize_input($_POST['rent_amount']) : '';
$contract_duration_month = isset($_POST['contract_duration_month']) ? sanitize_input($_POST['contract_duration_month']) : '';
$rent_due_date = isset($_POST['rent_due_date']) ? sanitize_input($_POST['rent_due_date']) : '';
$contract_period_starts = isset($_POST['contract_period_starts']) ? sanitize_input($_POST['contract_period_starts']) : '';
$term_of_payment = isset($_POST['term_of_payment']) ? sanitize_input($_POST['term_of_payment']) : '';
$initial_deposit = isset($_POST['initial_deposit']) ? sanitize_input($_POST['initial_deposit']) : '';
$contract_date_in_ethiopian_calender = isset($_POST['contract_date_in_ethiopian_calender']) ? sanitize_input($_POST['contract_date_in_ethiopian_calender']) : '';
$status = isset($_POST['status']) ? sanitize_input($_POST['status']) : '';
$last_payment_date = isset($_POST['last_payment_date']) ? sanitize_input($_POST['last_payment_date']) : NULL;
$next_payment_date = isset($_POST['next_payment_date']) ? sanitize_input($_POST['next_payment_date']) : NULL;
$move_out_date = isset($_POST['move_out_date']) ? sanitize_input($_POST['move_out_date']) : NULL;

// Basic validation (expand as needed)
$required_fields = ['full_name', 'mobile_no', 'email', 'building', 'floor', 'room', 'rent_amount', 'status'];
foreach ($required_fields as $field) {
    if (empty($$field)) {
        $response['message'] = 'Please fill in all required fields.';
        echo json_encode($response);
        exit();
    }
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Invalid email format.';
    echo json_encode($response);
    exit();
}

// Map form status to standardized status
$status_mapping = [
    'Moved In' => 'moved_in',
    'Moved Out' => 'moved_out',
];

if (array_key_exists($status, $status_mapping)) {
    $status = $status_mapping[$status];
} else {
    $status = '';
}

// Validate status
$valid_statuses = ['moved_in', 'moved_out'];
if (!in_array($status, $valid_statuses)) {
    $response['message'] = 'Invalid status. Must be "Moved In" or "Moved Out".';
    echo json_encode($response);
    exit();
}

// Include the database connection file
require_once '../db.php'; // Adjust the path as needed

// Check the database connection
if ($conn->connect_error) {
    $response['message'] = 'Database connection failed: ' . $conn->connect_error;
    echo json_encode($response);
    exit();
}

// Begin transaction
$conn->begin_transaction();

try {
    // Check if the room exists
    $room_check_stmt = $conn->prepare("SELECT status FROM room WHERE room_no = ?");
    if (!$room_check_stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $room_check_stmt->bind_param("s", $room);
    $room_check_stmt->execute();
    $room_check_stmt->store_result();

    if ($room_check_stmt->num_rows === 0) {
        throw new Exception('The specified room does not exist.');
    }

    $room_check_stmt->close();

    // Insert tenant
    $stmt = $conn->prepare("INSERT INTO tenant 
        (full_name, company_name, tin_no, mobile_no, email, address, building, floor, room, rent_amount, 
         contract_duration_month, rent_due_date, contract_period_starts, term_of_payment, initial_deposit, 
         contract_date_in_ethiopian_calender, status, last_payment_date, next_payment_date, move_out_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    // Bind parameters
    // Adjust parameter types according to your database schema
    // Example: "ssssssssssssssssssss" assumes all fields are strings. Modify as needed.
    $stmt->bind_param(
        "ssssssssssssssssssss",
        $full_name,
        $company_name,
        $tin_no,
        $mobile_no,
        $email,
        $address,
        $building,
        $floor,
        $room,
        $rent_amount,
        $contract_duration_month,
        $rent_due_date,
        $contract_period_starts,
        $term_of_payment,
        $initial_deposit,
        $contract_date_in_ethiopian_calender,
        $status,
        $last_payment_date,
        $next_payment_date,
        $move_out_date
    );
    
    // Execute the statement
    if (!$stmt->execute()) {
        throw new Exception('Failed to add tenant: ' . $stmt->error);
    }

    // Get the inserted tenant ID (optional)
    $tenant_id = $stmt->insert_id;

    $stmt->close();

    // Update room status and tenant based on tenant status
    if ($status === 'moved_in') {
        $room_status = 'occupied';
        $tenant_name = $full_name;
    } else { // 'moved_out'
        $room_status = 'vacant';
        $tenant_name = NULL;
    }

    $update_room_stmt = $conn->prepare("UPDATE room SET status = ?, tenant = ? WHERE room_no = ?");
    if (!$update_room_stmt) {
        throw new Exception('Prepare failed (room update): ' . $conn->error);
    }
    $update_room_stmt->bind_param("sss", $room_status, $tenant_name, $room);

    if (!$update_room_stmt->execute()) {
        throw new Exception('Failed to update room status: ' . $update_room_stmt->error);
    }

    $update_room_stmt->close();

    // Commit transaction
    $conn->commit();

    // Prepare successful response
    $response['success'] = true;
    $response['message'] = 'Tenant added successfully.';
    $response['tenant'] = [
        'id' => $tenant_id,
        'name' => $full_name
    ];

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $response['message'] = $e->getMessage();
}

// Close the connection
$conn->close();

// Return the JSON response
echo json_encode($response);
exit();
?>