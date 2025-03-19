<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include database connection
include '../db.php'; // Ensure the path is correct

$response = array();
$response['success'] = false; // Default to false

// Prepare the SQL statement to fetch all notifications
$stmt = $conn->prepare("SELECT id, message, status, created_at FROM notifications ORDER BY created_at DESC");
if ($stmt) {
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $notifications = array();

        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }

        $response['notifications'] = $notifications;
        $response['success'] = true;
    } else {
        // Execution failed
        $response['error'] = "Execution failed: " . $stmt->error;
    }
    $stmt->close();
} else {
    // Preparation failed
    $response['error'] = "Preparation failed: " . $conn->error;
}

// Output the JSON response
echo json_encode($response);
?>