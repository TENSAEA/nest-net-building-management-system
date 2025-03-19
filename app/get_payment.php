<?php
// File: html/dashboard/app/get_payment.php

header('Content-Type: application/json');

include '../db.php';

// Function to sanitize input data
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Retrieve and sanitize the payment ID from GET parameters
$payment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($payment_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment ID.']);
    exit();
}

// Prepare the SQL statement to fetch payment data
$sql = "SELECT 
            id,
            tenant,
            tenant_tin,
            room,
            fs_number,
            paid_months,
            paid_days,
            payment_method,
            bank_name,
            transaction_no,
            cheque_ref_no,
            price,
            deposited_amount,
            vat,
            withhold,
            discount,
            penality,
            total,
            rent_due_date,
            payment_date,
            received_by
        FROM payment 
        WHERE id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $payment_id);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $payment = $result->fetch_assoc();
            echo json_encode($payment);
        } else {
            echo json_encode(['success' => false, 'message' => 'Payment not found.']);
        }
    } else {
        // Log the error for debugging
        error_log("Database Execution Error [{$stmt->errno}]: {$stmt->error}");
        echo json_encode(['success' => false, 'message' => 'Failed to execute the query.']);
    }

    $stmt->close();
} else {
    // Log the error for debugging
    error_log("Database Preparation Error [{$conn->errno}]: {$conn->error}");
    echo json_encode(['success' => false, 'message' => 'Failed to prepare the SQL statement.']);
}

$conn->close();
?>