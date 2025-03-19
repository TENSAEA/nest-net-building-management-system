<?php
// Start output buffering to capture any unexpected output
ob_start();

// Disable direct error display, enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'C:\\wamp64\\logs\\php_error.log'); // **Ensure this path is correct and writable**
error_reporting(E_ALL);

// Enable MySQLi exceptions for better error handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Set the response header to JSON
header('Content-Type: application/json');

// Initialize a debug array to collect debug messages
$debug = [];

// Function to respond with JSON and exit
function respond($data) {
    echo json_encode($data);
    exit();
}

try {
    // Include the database connection
    include '../db.php';
    $debug[] = "Database connection included.";

    // Check if the request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond([
            'success' => false,
            'message' => 'Invalid request method.',
            'debug' => $debug
        ]);
    }
    $debug[] = "Received POST request.";

    // Retrieve and sanitize input data
    $building = isset($_POST['building']) ? intval($_POST['building']) : null;
    $floor = isset($_POST['floor']) ? intval($_POST['floor']) : null;
    $room_no = isset($_POST['room_no']) ? trim($_POST['room_no']) : null;
    $category = isset($_POST['category']) ? trim($_POST['category']) : null;
    $area = isset($_POST['area']) ? floatval($_POST['area']) : null;
    $sqt_mtr_price = isset($_POST['sqt_mtr_price']) ? floatval($_POST['sqt_mtr_price']) : null;
    $monthly_price = isset($_POST['monthly_price']) ? floatval($_POST['monthly_price']) : null;
    $tenant = isset($_POST['tenant']) ? trim($_POST['tenant']) : null;
    $status = isset($_POST['status']) ? trim($_POST['status']) : null;
    $added_by = isset($_POST['added_by']) ? trim($_POST['added_by']) : null;

    $debug[] = "Retrieved POST data: " . json_encode($_POST);
    $debug[] = "Sanitized data: " . json_encode([
        'building' => $building,
        'floor' => $floor,
        'room_no' => $room_no,
        'category' => $category,
        'area' => $area,
        'sqt_mtr_price' => $sqt_mtr_price,
        'monthly_price' => $monthly_price,
        'tenant' => $tenant,
        'status' => $status,
        'added_by' => $added_by
    ]);

    // Validate required fields
    if (is_null($building) || is_null($floor) || empty($room_no) || empty($category) || is_null($area) || is_null($sqt_mtr_price) || is_null($monthly_price) || empty($status)) {
        respond([
            'success' => false,
            'message' => 'All required fields are mandatory.',
            'debug' => $debug
        ]);
    }
    $debug[] = "All required fields are present.";

    // Check for existing room_no
    $check_sql = "SELECT COUNT(*) FROM room WHERE room_no = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $room_no);
    $check_stmt->execute();
    $check_stmt->bind_result($count);
    $check_stmt->fetch();
    $check_stmt->close();

    if ($count > 0) {
        respond([
            'success' => false,
            'message' => 'A room with this number already exists.',
            'debug' => $debug
        ]);
    }
    $debug[] = "Room number is unique.";

    // Prepare the SQL statement
    $sql = "INSERT INTO room (building, floor, room_no, category, area, sqt_mtr_price, monthly_price, tenant, status, added_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $debug[] = "SQL statement prepared successfully.";

    // Bind parameters to the SQL statement
    $stmt->bind_param("iissdddsss", $building, $floor, $room_no, $category, $area, $sqt_mtr_price, $monthly_price, $tenant, $status, $added_by);
    $debug[] = "Parameters bound successfully.";

    // Execute the statement
    $stmt->execute();
    $debug[] = "Room added successfully with ID: " . $stmt->insert_id;

    // Close the statement and connection
    $stmt->close();
    $conn->close();

    // Respond with success
    respond([
        'success' => true,
        'message' => 'Room added successfully.',
        'debug' => $debug
    ]);

} catch (mysqli_sql_exception $e) {
    // Handle MySQLi SQL exceptions
    $debug[] = "MySQLi Exception: " . $e->getMessage();
    respond([
        'success' => false,
        'message' => 'Database Error: ' . $e->getMessage(),
        'debug' => $debug
    ]);
} catch (Exception $e) {
    // Handle any other exceptions
    $debug[] = "General Exception: " . $e->getMessage();
    respond([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'debug' => $debug
    ]);
}

// End output buffering and clean the buffer to prevent any unexpected output
ob_end_clean();
?>