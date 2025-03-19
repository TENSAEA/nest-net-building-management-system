<?php
include '../db.php';

$id = $_GET['id'];
$query = "DELETE FROM company_info WHERE id='$id'";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>