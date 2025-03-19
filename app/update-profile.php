<?php
session_start();
include '../db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_SESSION['username'];
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $groupid = $_POST['groupid'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm-password'];

    if ($password !== $confirm_password) {
        echo "<div class='alert alert-danger'>Passwords do not match</div>";
    } else {
        $hashed_password = !empty($password) ? password_hash($password, PASSWORD_BCRYPT) : null;

        $sql = "UPDATE xionbms_users SET fullname = ?, email = ?, groupid = ?";
        if ($hashed_password) {
            $sql .= ", password = ?";
        }
        $sql .= " WHERE username = ?";

        $stmt = $conn->prepare($sql);
        if ($hashed_password) {
            $stmt->bind_param("sssss", $fullname, $email, $groupid, $hashed_password, $username);
        } else {
            $stmt->bind_param("ssss", $fullname, $email, $groupid, $username);
        }

        if ($stmt->execute() === TRUE) {
            $_SESSION['fullname'] = $fullname;
            $_SESSION['email'] = $email;
            $_SESSION['groupid'] = $groupid;
            echo "<div class='alert alert-success'>Profile updated successfully</div>";
        } else {
            echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
}
header("Location: user-profile.php");
exit();
?>