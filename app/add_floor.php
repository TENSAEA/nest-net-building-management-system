<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $building_id = $_POST['building_id'];
    $number = $_POST['number'];
    $area = $_POST['area'];
    $price = $_POST['price'];
    $added_date = $_POST['added_date'];

    $sql = "INSERT INTO floor (building_id, number, area, price, added_date) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiids", $building_id, $number, $area, $price, $added_date);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
}
?>