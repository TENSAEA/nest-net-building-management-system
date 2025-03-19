<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'db_connect.php';

$startDate = $_POST['startDate'] ?? null;
$endDate = $_POST['endDate'] ?? null;
$reportType = $_POST['reportType'] ?? null;
$fileType = $_POST['fileType'] ?? 'csv';

// Convert dates to the appropriate format if they are set
if ($startDate && $endDate) {
    $startDate = DateTime::createFromFormat('m/d/Y', $startDate)->format('Y-m-d');
    $endDate = DateTime::createFromFormat('m/d/Y', $endDate)->format('Y-m-d');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fetchData'])) {
    $data = [];
    switch ($reportType) {
        case 'tenant':
            $sql = "SELECT tenant_id, full_name, company_name, floor, room, mobile_no, rent_amount ,status FROM tenant";
            break;
        case 'payment':
            $sql = "SELECT p.tenant, t.full_name AS tenant_name, p.tenant_tin, p.room, p.paid_months, p.fs_number, p.payment_date, p.total 
                    FROM payment p 
                    LEFT JOIN tenant t ON p.tenant = t.tenant_id 
                    WHERE p.payment_date BETWEEN '$startDate' AND '$endDate'";
            break;
        case 'additional_fee':
            $sql = "SELECT description, registration_date, price
                    FROM additional_fee 
                    WHERE registration_date BETWEEN '$startDate' AND '$endDate'";
            break;
        case 'expense':
            $sql = "SELECT  date, expense_type, cost 
                    FROM expense 
                    WHERE date BETWEEN '$startDate' AND '$endDate'";
            break;
        case 'room':
            $sql = "SELECT room, floor, area, tenant, monthly_price, status FROM room";
            break;
        default:
            echo json_encode(['error' => 'Invalid report type']);
            exit();
    }

    $query = $connect->query($sql);
    if (!$query) {
        echo json_encode(['error' => 'Query failed: ' . mysqli_error($connect)]);
        exit();
    }

    while ($result = mysqli_fetch_assoc($query)) {
        $data[] = $result;
    }

    echo json_encode($data);
    exit();
}

if ($fileType === 'csv') {
    $filename = "report_" . date('Ymd') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Add header row to CSV
    switch ($reportType) {
        case 'tenant':
            $sql = "SELECT tenant_id, full_name, company_name, floor, room, mobile_no, rent_amount, status FROM tenant";
            $query = $connect->query($sql);
            if (!$query) {
                die("Query failed: " . mysqli_error($connect));
            }
            fputcsv($output, ['No', 'Full Name', 'Company Name', 'Floor', 'Room', 'Mobile No', 'Rent Amount',  'Status']);
            $no = 1;
            while ($result = mysqli_fetch_assoc($query)) {
                fputcsv($output, [$no++, $result['full_name'], $result['company_name'], $result['floor'], $result['room'], $result['mobile_no'], $result['rent_amount'],  $result['status']]);
            }
            break;

        case 'payment':
            $sql = "SELECT p.tenant, t.full_name AS tenant_name, p.tenant_tin, p.room, p.paid_months, p.fs_number, p.payment_date, p.total 
                    FROM payment p 
                    LEFT JOIN tenant t ON p.tenant = t.tenant_id 
                    WHERE p.payment_date BETWEEN '$startDate' AND '$endDate'";
            $query = $connect->query($sql);
            if (!$query) {
                die("Query failed: " . mysqli_error($connect));
            }
            fputcsv($output, ['No', 'Tenant Name', 'TIN No', 'Room', 'Paid Month', 'FS Number', 'Payment Date',  'Total']);
            $no = 1;
            $totalAmount = 0;
            while ($result = mysqli_fetch_assoc($query)) {
                $totalAmount += $result['total'];
                fputcsv($output, [$no++, $result['tenant_name'], $result['tenant_tin'], $result['room'], $result['paid_months'], $result['fs_number'], $result['payment_date'], $result['z_no'], $result['total']]);
            }
            fputcsv($output, ['', '', '', '', '', '', '', 'Total', $totalAmount]);
            break;

        case 'additional_fee':
            $sql = "SELECT description, registration_date, price
                    FROM additional_fee 
                    WHERE registration_date BETWEEN '$startDate' AND '$endDate'";
            $query = $connect->query($sql);
            if (!$query) {
                die("Query failed: " . mysqli_error($connect));
            }
            fputcsv($output, ['No', 'Description', 'Registration Date',  'Price']);
            $no = 1;
            $totalAmount = 0;
            while ($result = mysqli_fetch_assoc($query)) {
                $totalAmount += $result['amount'];
                fputcsv($output, [$no++, $result['description'], $result['payment_date'], $result['month'], $result['amount']]);
            }
            fputcsv($output, ['', '', '', 'Total Amount', $totalAmount]);
            break;

        case 'expense':
            $sql = "SELECT  date, expense_type, cost 
                    FROM expense 
                    WHERE date BETWEEN '$startDate' AND '$endDate'";
            $query = $connect->query($sql);
            if (!$query) {
                die("Query failed: " . mysqli_error($connect));
            }
            fputcsv($output, ['No', 'Date', 'Expense Type', 'Total']);
            $no = 1;
            $totalAmount = 0;
            while ($result = mysqli_fetch_assoc($query)) {
                $totalAmount += $result['cost'];
                fputcsv($output, [$no++, $result['expense_for'], $result['date'], $result['expense_type'], $result['cost']]);
            }
            fputcsv($output, ['', '', '', 'Total Cost', $totalAmount]);
            break;

        case 'room':
            $sql = "SELECT room, floor, area, tenant, monthly_price, status FROM room"; // Select specified fields from the room table
            $query = $connect->query($sql);
            if (!$query) {
                die("Query failed: " . mysqli_error($connect));
            }
            fputcsv($output, ['No', 'Room', 'Floor', 'Area', 'Tenant', 'Monthly Price', 'Status']);
            $no = 1;
            while ($result = mysqli_fetch_assoc($query)) {
                fputcsv($output, [$no++, $result['room'], $result['floor'], $result['area'], $result['tenant'], $result['monthly_price'], $result['status']]);
            }
            break;
    }

    fclose($output);
    exit();
}
?>