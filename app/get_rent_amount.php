<?php
// Assuming you have a database connection established
include '../db.php';

if (isset($_GET['room_no'])) {
    $roomNo = $_GET['room_no'];
    $query = "SELECT monthly_price FROM room WHERE room_no = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $roomNo); // Assuming room_no is a string
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if ($data) {
        echo json_encode(['success' => true, 'monthly_price' => $data['monthly_price']]);
    } else {
        echo json_encode(['success' => false]);
    }
}
?>