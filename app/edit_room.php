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

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate required fields
    $required_fields = ['room_id', 'building', 'floor', 'room_no', 'category', 'area', 'sqt_mtr_price', 'monthly_price', 'tenant', 'status', 'added_by'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            respond_with_error("Missing required field: {$field}.");
        }
    }

    // Sanitize and assign variables
    $room_id = intval($_POST['room_id']);
    $building = sanitize_input($_POST['building']);
    $floor = intval($_POST['floor']);
    $room_no = sanitize_input($_POST['room_no']);
    $category = intval($_POST['category']);
    $area = floatval($_POST['area']);
    $sqt_mtr_price = floatval($_POST['sqt_mtr_price']);
    $monthly_price = floatval($_POST['monthly_price']);
    $tenant = sanitize_input($_POST['tenant']);
    $status = sanitize_input($_POST['status']);
    $added_by = sanitize_input($_POST['added_by']);

    // Prepare the SQL statement
    $sql = "UPDATE room SET building = ?, floor = ?, room_no = ?, category = ?, area = ?, sqt_mtr_price = ?, monthly_price = ?, tenant = ?, status = ?, added_by = ? WHERE room_id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        respond_with_error('SQL Prepare Failed: ' . $conn->error);
    }

    // Bind parameters
    if (!$stmt->bind_param("sissddssssi", $building, $floor, $room_no, $category, $area, $sqt_mtr_price, $monthly_price, $tenant, $status, $added_by, $room_id)) {
        respond_with_error('Binding Parameters Failed: ' . $stmt->error);
    }

    // Execute the statement
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        respond_with_error('Execute Failed: ' . $stmt->error);
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}

// End output buffering
ob_end_flush();
?>