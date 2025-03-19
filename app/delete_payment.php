<?php
include '../db.php';

// Check if 'id' parameter exists
if (isset($_GET['id'])) {
    $payment_id = intval($_GET['id']);

    // Prepare the SQL statement
    $sql = "DELETE FROM payment WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $payment_id);
        if ($stmt->execute()) {
            // Redirect with success message
            header("Location: payment.php?success=Payment+deleted+successfully");
            exit();
        } else {
            // Redirect with error message
            header("Location: payment.php?error=Failed+to+delete+payment");
            exit();
        }
        $stmt->close();
    } else {
        // Redirect with error message
        header("Location: payment.php?error=Failed+to+prepare+delete+statement");
        exit();
    }
} else {
    // Redirect with error message
    header("Location: payment.php?error=Invalid+payment+ID");
    exit();
}

$conn->close();
?>