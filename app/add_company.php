<?php
include '../db.php';

$name = $_POST['name'];
$phone = $_POST['phone'];
$email = $_POST['email'];
$address = $_POST['address'];
$tin_no = $_POST['tin_no'];

// Handle file upload
$logo = $_FILES['logo']['name'];
$target_dir = "uploads/";
$target_file = $target_dir . basename($logo);
move_uploaded_file($_FILES['logo']['tmp_name'], $target_file);

$query = "INSERT INTO company_info (name, phone, email, address, logo, tin_no) VALUES ('$name', '$phone', '$email', '$address', '$target_file', '$tin_no')";
if (mysqli_query($conn, $query)) {
    header("Location: company-info.php");
} else {
    echo "Error: " . $query . "<br>" . mysqli_error($conn);
}
?>