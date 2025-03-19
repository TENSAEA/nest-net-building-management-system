<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $floor_id = $_POST['floor_id'];
    $building_id = $_POST['building_id'];
    $number = $_POST['number'];
    $area = $_POST['area'];
    $price = $_POST['price'];
    $added_date = $_POST['added_date'];

    $sql = "UPDATE floor SET building_id = ?, number = ?, area = ?, price = ?, added_date = ? WHERE floor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiidsi", $building_id, $number, $area, $price, $added_date, $floor_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
}
?>