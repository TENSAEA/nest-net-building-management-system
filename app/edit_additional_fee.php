<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $registration_date = $_POST['registration_date'];
    $payment_id = $_POST['payment_id'];

    $sql = "UPDATE additional_fee SET title = ?, price = ?, description = ?, registration_date = ?, payment_id = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdssii", $title, $price, $description, $registration_date, $payment_id, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
}
?>