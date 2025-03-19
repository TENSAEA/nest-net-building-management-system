<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $building_id = $_POST['building_id'];
    $building_name = $_POST['building_name'];
    $trade_name = $_POST['trade_name'];
    $address = $_POST['address'];
    $tel_no = $_POST['tel_no'];
    $email = $_POST['email'];
    $pobox = $_POST['pobox'];
    $tin_no = $_POST['tin_no'];
    $floors_no = $_POST['floors_no'];

    $sql = "UPDATE building SET building_name = ?, trade_name = ?, address = ?, tel_no = ?, email = ?, pobox = ?, tin_no = ?, floors_no = ? WHERE building_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssii", $building_name, $trade_name, $address, $tel_no, $email, $pobox, $tin_no, $floors_no, $building_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
}
?>