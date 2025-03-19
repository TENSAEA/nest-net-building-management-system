<?php
include '../db.php';

if (isset($_GET['id'])) {
    $building_id = $_GET['id'];

    $sql = "SELECT * FROM building WHERE building_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $building_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $building = $result->fetch_assoc();

    echo json_encode($building);
}
?>