<?php
include './db.php'; // Update the path to your database connection file

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT ID, username, email, fullname, role FROM xionbms_users WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    echo json_encode($user);
} else {
    echo json_encode(['error' => 'User ID not provided']);
}
?>