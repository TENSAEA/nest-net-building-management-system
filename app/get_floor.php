<?php
include '../db.php';

if (isset($_GET['id'])) {
    $floor_id = $_GET['id'];

    $sql = "SELECT * FROM floor WHERE floor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $floor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $floor = $result->fetch_assoc();

    echo json_encode($floor);
}
?>