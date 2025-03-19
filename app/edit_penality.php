<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $percent = $_POST['percent'];
    $measure = $_POST['measure'];
    $color = $_POST['color'];

    $sql = "UPDATE penality SET percent = ?, measure = ?, color = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $percent, $measure, $color, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
}
?>