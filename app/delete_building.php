<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    parse_str(file_get_contents("php://input"), $data);
    $building_id = $data['id'];

    $sql = "DELETE FROM building WHERE building_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $building_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
}
?>