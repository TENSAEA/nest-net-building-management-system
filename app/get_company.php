<?php
include '../db.php';

$id = $_GET['id'];
$query = "SELECT * FROM company_info WHERE id='$id'";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

echo json_encode($data);
?>