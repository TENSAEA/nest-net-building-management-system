<?php
// get_tenant_details.php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Include your database connection
include '../db.php';

// Set the Content-Type header to application/json
header('Content-Type: application/json');

if (isset($_GET['name'])) {
    $name = $_GET['name'];

   // Fetch tenant details from the database
   $query = "SELECT tin_no, room, rent_due_date, price FROM tenant WHERE name = ?";
   $stmt = $conn->prepare($query);
   $stmt->bind_param("s", $name);
   $stmt->execute();
   $result = $stmt->get_result();

   if ($result->num_rows > 0) {
       $tenant = $result->fetch_assoc();
       echo json_encode(['success' => true, 'tenant' => $tenant]);
   } else {
       echo json_encode(['success' => false, 'message' => 'Tenant not found.']);
   }

   $stmt->close();
   $conn->close();
} else {
   echo json_encode(['success' => false, 'message' => 'Invalid tenant name.']);
}
?>