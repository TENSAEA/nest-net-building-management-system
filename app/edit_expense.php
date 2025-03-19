<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Function to sanitize input data
    function sanitize($data, $conn) {
        return htmlspecialchars(strip_tags($conn->real_escape_string($data)));
    }

    // Retrieve and sanitize POST data
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $expense_type = isset($_POST['expense_type']) ? (int)$_POST['expense_type'] : 0;
    $payment_method = isset($_POST['payment_method']) ? sanitize($_POST['payment_method'], $conn) : '';
    $cost = isset($_POST['cost']) ? (float)$_POST['cost'] : 0.0;
    $paid_to = isset($_POST['paid_to']) ? sanitize($_POST['paid_to'], $conn) : '';
    $tin_number = isset($_POST['tin_number']) ? sanitize($_POST['tin_number'], $conn) : '';
    $description = isset($_POST['description']) ? sanitize($_POST['description'], $conn) : '';
    $date = isset($_POST['date']) ? sanitize($_POST['date'], $conn) : '';

    // Validate required fields
    if ($id > 0 && $expense_type > 0 && !empty($payment_method) && $cost >= 0 && $date !== '') {
        // Prepare the UPDATE statement
        $sql = "UPDATE expense SET expense_type = ?, payment_method = ?, cost = ?, paid_to = ?, tin_number = ?, description = ?, date = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("isdssssi", $expense_type, $payment_method, $cost, $paid_to, $tin_number, $description, $date, $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Expense updated successfully.']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update expense.']);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error: Unable to prepare statement.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
    }
}
?>