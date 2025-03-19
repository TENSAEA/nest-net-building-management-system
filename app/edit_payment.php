<?php
// File: html/dashboard/app/edit_payment.php
header('Content-Type: application/json');
include '../db.php'; // Adjust the path as necessary

// Check if required POST data is set
if (!isset($_POST['payment_id'])) {
    echo json_encode(['success' => false, 'message' => 'Payment ID is missing.']);
    exit;
}

$paymentId = intval($_POST['payment_id']);
$tenantId = intval($_POST['tenant_id']);
$receivedBy = $conn->real_escape_string($_POST['received_by']);

// Retrieve other payment fields similarly...
$paymentMethod = $conn->real_escape_string($_POST['payment_method']);
$bankName = $conn->real_escape_string($_POST['bank_name']);
$transactionNo = $conn->real_escape_string($_POST['transaction_no']);
$paymentDate = $conn->real_escape_string($_POST['payment_date']);
$depositedAmount = floatval($_POST['deposited_amount']);
$total = floatval($_POST['total']);
$withhold = isset($_POST['withheldCheckbox']) ? floatval($_POST['withhold']) : 0;
$discount = isset($_POST['discountedCheckbox']) ? floatval($_POST['discount']) : 0;
$penality = isset($_POST['penalityCheckbox']) ? floatval($_POST['penality']) : 0;

// Retrieve and decode additional fees
$additionalFeesJson = $_POST['additional_fees'];
$additionalFees = json_decode($additionalFeesJson, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid additional fees data.']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Update main payment data using prepared statement
    $updateStmt = $conn->prepare("UPDATE payment SET tenant_id = ?, payment_method = ?, bank_name = ?, transaction_no = ?, payment_date = ?, deposited_amount = ?, total = ?, withhold = ?, discount = ?, penality = ?, received_by = ? WHERE id = ?");
    if (!$updateStmt) {
        throw new Exception('Failed to prepare payment update statement: ' . $conn->error);
    }

    $updateStmt->bind_param(
        "issssssddddsi",
        $tenantId,
        $paymentMethod,
        $bankName,
        $transactionNo,
        $paymentDate,
        $depositedAmount,
        $total,
        $withhold,
        $discount,
        $penality,
        $receivedBy,
        $paymentId
    );

    if (!$updateStmt->execute()) {
        throw new Exception('Failed to update payment: ' . $updateStmt->error);
    }

    $updateStmt->close();

    // Delete existing additional fees
    $deleteStmt = $conn->prepare("DELETE FROM additional_fee WHERE payment_id = ?");
    if (!$deleteStmt) {
        throw new Exception('Failed to prepare additional fees delete statement: ' . $conn->error);
    }
    $deleteStmt->bind_param("i", $paymentId);
    if (!$deleteStmt->execute()) {
        throw new Exception('Failed to delete existing additional fees: ' . $deleteStmt->error);
    }
    $deleteStmt->close();

    // Insert new additional fees
    if (!empty($additionalFees)) {
        $insertStmt = $conn->prepare("INSERT INTO additional_fee (payment_id, title, price, description, registration_date) VALUES (?, ?, ?, ?, ?)");
        if (!$insertStmt) {
            throw new Exception('Failed to prepare additional fees insert statement: ' . $conn->error);
        }

        foreach ($additionalFees as $fee) {
            // Validate and sanitize fee data
            $feeTitle = $conn->real_escape_string($fee['title']);
            $feePrice = floatval($fee['price']);
            $feeDescription = $conn->real_escape_string($fee['description']);
            $feeRegDate = $conn->real_escape_string($fee['registration_date']);

            $insertStmt->bind_param("isdss", $paymentId, $feeTitle, $feePrice, $feeDescription, $feeRegDate);
            if (!$insertStmt->execute()) {
                throw new Exception('Failed to insert additional fee: ' . $insertStmt->error);
            }
        }

        $insertStmt->close();
    }

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Payment and additional fees updated successfully.']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>