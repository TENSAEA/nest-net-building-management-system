<?php
// Start output buffering to prevent any unexpected output
ob_start();

try {
    include '../db.php';

  

    // Set the response header to JSON
    header('Content-Type: application/json');

    // Function to sanitize input data
    function sanitize_input($data) {
        return htmlspecialchars(stripslashes(trim($data)));
    }

    // Retrieve and sanitize POST data
    $tenant = isset($_POST['tenant']) ? sanitize_input($_POST['tenant']) : '';
    $tenant_tin = isset($_POST['tenant_tin_no']) ? sanitize_input($_POST['tenant_tin_no']) : '';
    $room = isset($_POST['room']) ? sanitize_input($_POST['room']) : '';
    $fs_number = isset($_POST['fs_number']) ? sanitize_input($_POST['fs_number']) : '';
    $paid_months = isset($_POST['paid_months']) ? intval($_POST['paid_months']) : 0;
    $paid_days = isset($_POST['paid_days']) ? intval($_POST['paid_days']) : 0;
    $payment_method = isset($_POST['payment_method']) ? sanitize_input($_POST['payment_method']) : '';
    $bank_name = isset($_POST['bank_name']) ? sanitize_input($_POST['bank_name']) : '';
    $transaction_no = isset($_POST['transaction_no']) ? sanitize_input($_POST['transaction_no']) : '';
    $cheque_ref_no = isset($_POST['cheque_ref_no']) ? sanitize_input($_POST['cheque_ref_no']) : '';
    $rent_amount = isset($_POST['rent_amount']) ? floatval($_POST['rent_amount']) : 0.0;
    $price = isset($_POST['sub_total']) ? floatval($_POST['sub_total']) : 0.0;
    $deposited_amount = isset($_POST['deposited_amount']) ? floatval($_POST['deposited_amount']) : 0.0;
    $vat = isset($_POST['vat']) ? floatval($_POST['vat']) : 0.0;
    $withhold = isset($_POST['withhold']) ? floatval($_POST['withhold']) : 0.0;
    $discount = isset($_POST['discount']) ? floatval($_POST['discount']) : 0.0;
    $penality = isset($_POST['penality']) ? floatval($_POST['penality']) : 0.0;
    $total = isset($_POST['total']) ? floatval($_POST['total']) : 0.0;
    $rent_due_date = isset($_POST['rent_due_date']) ? sanitize_input($_POST['rent_due_date']) : '';
    $payment_date = isset($_POST['payment_date']) ? sanitize_input($_POST['payment_date']) : '';
    $received_by = isset($_POST['received_by']) ? sanitize_input($_POST['received_by']) : '';

    // Validate required fields
    $required_fields = [
        'tenant' => $tenant,
        'room' => $room,
        'payment_method' => $payment_method,
        'payment_date' => $payment_date,
        'total' => $total
    ];

    foreach ($required_fields as $field => $value) {
        if (empty($value)) {
            echo json_encode(['success' => false, 'message' => "The field '{$field}' is required."]);
            exit();
        }
    }

    // Prepare the SQL statement
    $sql = "INSERT INTO payment (
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
                rent_amount,
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
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        // Ensure the format string matches the number of variables (21 in this case)
        $stmt->bind_param(
            "ssssiissssddddddddsss", // 21 specifiers: s=string, i=integer, d=double
            $tenant,
            $tenant_tin,
            $room,
            $fs_number,
            $paid_months,
            $paid_days,
            $payment_method,
            $bank_name,
            $transaction_no,
            $cheque_ref_no,
            $rent_amount,
            $price,
            $deposited_amount,
            $vat,
            $withhold,
            $discount,
            $penality,
            $total,
            $rent_due_date,
            $payment_date,
            $received_by
        );

        if ($stmt->execute()) {
            $payment_id = $stmt->insert_id; // Get the last inserted payment ID
            $response = ['success' => true, 'payment_id' => $payment_id];
        } else {
            // Log the error message for debugging
            error_log("Database Error [{$stmt->errno}]: {$stmt->error}");
            $response = ['success' => false, 'message' => 'Failed to execute the query.'];
        }

        $stmt->close();
    } else {
        // Log the error message for debugging
        error_log("Prepare Failed: {$conn->errno} - {$conn->error}");
        $response = ['success' => false, 'message' => 'Failed to prepare the SQL statement.'];
    }

    $conn->close();

    // Return the response as JSON
    echo json_encode($response);
} catch (Exception $e) {
    // Log the exception message
    error_log("Exception: " . $e->getMessage());
    // Return a JSON error response with the exception message for debugging
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()]);
}