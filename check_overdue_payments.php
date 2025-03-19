<?php
// Include the database connection
include './db.php';

// Fetch all unpaid payments where due_date is past
$overduePaymentsQuery = "
    SELECT 
        payment.id AS payment_id,
        payment.tenant AS tenant_id,
        tenant.full_name
    FROM 
        payment
    JOIN 
        tenant ON payment.tenant = tenant.tenant_id
    WHERE 
        payment.due_date < CURDATE() AND 
        payment.status != 'paid'
";

$overduePaymentsResult = $conn->query($overduePaymentsQuery);

if ($overduePaymentsResult->num_rows > 0) {
    while ($payment = $overduePaymentsResult->fetch_assoc()) {
        $userId = $payment['tenant_id'];
        $paymentId = $payment['payment_id'];
        $tenantName = htmlspecialchars($payment['full_name'], ENT_QUOTES, 'UTF-8');
        $message = "Payment for invoice #{$paymentId} by {$tenantName} is overdue.";

        // Check if a notification for this payment already exists to avoid duplicates
        $checkNotificationStmt = $conn->prepare("SELECT id FROM notifications WHERE user_id = ? AND message = ? AND status = 'unread'");
        $checkNotificationStmt->bind_param("is", $userId, $message);
        $checkNotificationStmt->execute();
        $checkNotificationResult = $checkNotificationStmt->get_result();

        if ($checkNotificationResult->num_rows == 0) {
            // Insert notification into the database
            $insertNotificationStmt = $conn->prepare("INSERT INTO notifications (user_id, message, status, created_at) VALUES (?, ?, 'unread', NOW())");
            $insertNotificationStmt->bind_param("is", $userId, $message);
            $insertNotificationStmt->execute();
            $insertNotificationStmt->close();
        }

        $checkNotificationStmt->close();
    }
}

$conn->close();
?>