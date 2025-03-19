<?php
// update_tenant.php
header('Content-Type: application/json');
require '../db.php'; // Include your database connection

// Start output buffering to prevent any unexpected output
ob_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize POST data
    $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : null;
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : null;
    $company_name = isset($_POST['company_name']) ? trim($_POST['company_name']) : null;
    $tin_no = isset($_POST['tin_no']) ? trim($_POST['tin_no']) : null;
    $mobile_no = isset($_POST['mobile_no']) ? trim($_POST['mobile_no']) : null;
    $email = isset($_POST['email']) ? trim($_POST['email']) : null;
    $address = isset($_POST['address']) ? trim($_POST['address']) : null;
    $building = isset($_POST['building']) ? trim($_POST['building']) : null;
    $floor = isset($_POST['floor']) ? trim($_POST['floor']) : null;
    $room = isset($_POST['room']) ? trim($_POST['room']) : null;
    $rent_amount = isset($_POST['rent_amount']) ? floatval($_POST['rent_amount']) : null;
    $contract_duration_month = isset($_POST['contract_duration_month']) ? intval($_POST['contract_duration_month']) : null;
    $rent_due_date = isset($_POST['rent_due_date']) ? $_POST['rent_due_date'] : null;
    $contract_period_starts = isset($_POST['contract_period_starts']) ? $_POST['contract_period_starts'] : null;
    $term_of_payment = isset($_POST['term_of_payment']) ? intval($_POST['term_of_payment']) : null;
    $initial_deposit = isset($_POST['initial_deposit']) ? floatval($_POST['initial_deposit']) : null;
    $contract_date_in_ethiopian_calender = isset($_POST['contract_date_in_ethiopian_calender']) ? trim($_POST['contract_date_in_ethiopian_calender']) : null;
    $status = isset($_POST['status']) ? $_POST['status'] : null;
    $last_payment_date = isset($_POST['last_payment_date']) ? $_POST['last_payment_date'] : null;
    $next_payment_date = isset($_POST['next_payment_date']) ? $_POST['next_payment_date'] : null;
    $move_out_date = isset($_POST['move_out_date']) && !empty($_POST['move_out_date']) ? $_POST['move_out_date'] : null;

    // Check if tenant_id is provided
    if (is_null($tenant_id)) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Tenant ID is missing.']);
        exit;
    }

    // Prepare the SQL statement
    if ($status === 'Moved Out') {
        $sql = "UPDATE tenant SET 
                    full_name = ?, 
                    company_name = ?, 
                    tin_no = ?, 
                    mobile_no = ?, 
                    email = ?, 
                    address = ?, 
                    building = ?, 
                    floor = ?, 
                    room = ?, 
                    rent_amount = ?, 
                    contract_duration_month = ?, 
                    rent_due_date = ?, 
                    contract_period_starts = ?, 
                    term_of_payment = ?, 
                    initial_deposit = ?, 
                    contract_date_in_ethiopian_calender = ?, 
                    status = ?, 
                    last_payment_date = ?, 
                    next_payment_date = ?, 
                    move_out_date = ? 
                WHERE tenant_id = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            $error_message = $conn->error;
            error_log("Prepare failed: $error_message");
            ob_end_clean();
            echo json_encode(['status' => 'error', 'message' => 'Failed to prepare the SQL statement.', 'error' => $error_message]);
            exit;
        }

        // Bind parameters
        $bind_result = $stmt->bind_param(
            "sssssssssdissidsssssi",
            $full_name,
            $company_name,
            $tin_no,
            $mobile_no,
            $email,
            $address,
            $building,
                $floor,
                $room,
                $rent_amount,
                $contract_duration_month,
                $rent_due_date,
                $contract_period_starts,
                $term_of_payment,
                $initial_deposit,
                $contract_date_in_ethiopian_calender,
                $status,
                $last_payment_date,
                $next_payment_date,
                $move_out_date,
                $tenant_id
        );

    } else { // 'Moved In' case
        $sql = "UPDATE tenant SET 
                    full_name = ?, 
                    company_name = ?, 
                    tin_no = ?, 
                    mobile_no = ?, 
                    email = ?, 
                    address = ?, 
                    building = ?, 
                    floor = ?, 
                    room = ?, 
                    rent_amount = ?, 
                    contract_duration_month = ?, 
                    rent_due_date = ?, 
                    contract_period_starts = ?, 
                    term_of_payment = ?, 
                    initial_deposit = ?, 
                    contract_date_in_ethiopian_calender = ?, 
                    status = ?, 
                    last_payment_date = ?, 
                    next_payment_date = ?, 
                    move_out_date = NULL 
                WHERE tenant_id = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            $error_message = $conn->error;
            error_log("Prepare failed: $error_message");
            ob_end_clean();
            echo json_encode(['status' => 'error', 'message' => 'Failed to prepare the SQL statement.', 'error' => $error_message]);
            exit;
        }

        // Bind parameters
        $bind_result = $stmt->bind_param(
            "sssssssssdissidssssi",
            $full_name,
            $company_name,
            $tin_no,
            $mobile_no,
            $email,
            $address,
            $building,
                $floor,
                $room,
                $rent_amount,
                $contract_duration_month,
                $rent_due_date,
                $contract_period_starts,
                $term_of_payment,
                $initial_deposit,
                $contract_date_in_ethiopian_calender,
                $status,
                $last_payment_date,
                $next_payment_date,
                $tenant_id
        );
    }

    // Check if bind_param was successful
    if ($bind_result === false) {
        $error_message = $stmt->error;
        error_log("Bind Param failed: $error_message");
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Failed to bind parameters.', 'error' => $error_message]);
        exit;
    }

    // Execute the statement
    if ($stmt->execute()) {
        ob_end_clean();
        echo json_encode(['status' => 'success']);
    } else {
        $error_message = $stmt->error;
        error_log("Execute failed: $error_message");
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Failed to update tenant.', 'error' => $error_message]);
    }

    $stmt->close();
} else {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}

$conn->close();
?>