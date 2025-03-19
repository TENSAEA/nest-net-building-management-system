<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $building_name = $_POST['building_name'];
    $trade_name = $_POST['trade_name'];
    $address = $_POST['address'];
    $tel_no = $_POST['tel_no'];
    $email = $_POST['email'];
    $pobox = $_POST['pobox'];
    $tin_no = $_POST['tin_no'];
    $floors_no = $_POST['floors_no'];

    $sql = "INSERT INTO building (building_name, trade_name, address, tel_no, email, pobox, tin_no, floors_no) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssi", $building_name, $trade_name, $address, $tel_no, $email, $pobox, $tin_no, $floors_no);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
}
?>