<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];

    $sql = "INSERT INTO expense_type (title) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $title);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
}
?>