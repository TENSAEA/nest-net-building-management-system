<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $expense_type = $_POST['expense_type'];
    $cost = $_POST['cost'];
    $paid_to = $_POST['paid_to'];
    $payment_method = $_POST['payment_method'];
    $tin_number = $_POST['tin_number'];
    $description = $_POST['description'];
    $date = $_POST['date'];

    $sql = "INSERT INTO expense (expense_type, cost, paid_to, payment_method, tin_number, description, date) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdsssds", $expense_type, $cost, $paid_to, $payment_method, $tin_number, $description, $date);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
}
?>