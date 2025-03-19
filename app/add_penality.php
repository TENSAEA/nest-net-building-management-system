<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $percent = $_POST['percent'];
    $measure = $_POST['measure'];
    $color = $_POST['color'];

    $sql = "INSERT INTO penality (percent, measure, color) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $percent, $measure, $color);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
}
?>