<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include database connection
include './db.php'; // Ensure the path is correct

$response = array();
$response['success'] = false; // Default to false

// Retrieve notification ID from POST data
$notificationId = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id'])) {
        $notificationId = intval($_POST['id']);
    }
}

if ($notificationId > 0) {
    // Prepare the SQL statement to update the notification status
    $stmt = $conn->prepare("UPDATE notifications SET status = 'read' WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $notificationId);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response['success'] = true;
            } else {
                $response['error'] = "No notification updated. It may not exist.";
            }
        } else {
            // Execution failed
            $response['error'] = "Execution failed: " . $stmt->error;
        }
        $stmt->close();
    } else {
        // Preparation failed
        $response['error'] = "Preparation failed: " . $conn->error;
    }
} else {
    $response['error'] = "Invalid notification ID.";
}

// Output the JSON response
echo json_encode($response);
?>