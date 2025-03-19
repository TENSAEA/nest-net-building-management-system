<?php
include '../db.php';

if (isset($_GET['id'])) {
    $room_id = $_GET['id'];

    $sql = "SELECT * FROM room WHERE room_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $room = $result->fetch_assoc();

    echo json_encode($room);
}
?>