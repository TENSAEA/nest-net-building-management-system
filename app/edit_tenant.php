<?php
include '../db.php'; // Updated path to the database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenant_id = $_POST['tenant_id'];
    $full_name = $_POST['full_name'];
    $company_name = $_POST['company_name'];
    $building = $_POST['building'];
    $floor = $_POST['floor'];
    $room = $_POST['room'];
    $mobile_no = $_POST['mobile_no'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $tin_no = $_POST['tin_no'];
    $rent_amount = $_POST['rent_amount'];
    $contract_period_starts = $_POST['contract_period_starts'];
    $contract_duration_month = $_POST['contract_duration_month'];
    $rent_due_date = $_POST['rent_due_date'];
    $term_of_payment = $_POST['term_of_payment'];
    $initial_deposit = $_POST['initial_deposit'];
    $last_payment_date = $_POST['last_payment_date'];
    $next_payment_date = $_POST['next_payment_date'];
    $status = $_POST['status'];
    $move_out_date = $_POST['move_out_date'];
    $contract_date_in_ethiopian_calender = $_POST['contract_date_in_ethiopian_calender'];

    $sql = "UPDATE tenant SET full_name = ?, company_name = ?, building = ?, floor = ?, room = ?, mobile_no = ?, email = ?, address = ?, tin_no = ?, rent_amount = ?, contract_period_starts = ?, contract_duration_month = ?, rent_due_date = ?, term_of_payment = ?, initial_deposit = ?, last_payment_date = ?, next_payment_date = ?, status = ?, move_out_date = ?, contract_date_in_ethiopian_calender = ? WHERE tenant_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssssssssssssi", $full_name, $company_name, $building, $floor, $room, $mobile_no, $email, $address, $tin_no, $rent_amount, $contract_period_starts, $contract_duration_month, $rent_due_date, $term_of_payment, $initial_deposit, $last_payment_date, $next_payment_date, $status, $move_out_date, $contract_date_in_ethiopian_calender, $tenant_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}
?>