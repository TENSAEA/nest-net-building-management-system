<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: auth/sign-in.php");
    exit();
}
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user'; // Default to 'user' if role is not set

$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') : '';

include '../db.php'; // Updated path to the database connection file
// Fetch bank names from the database
$banks = [];
$sql = "SELECT id, name FROM banks";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $banks[] = $row;
    }
}
$tenant_sql = "SELECT tenant_id, full_name FROM tenant";
$tenant_result = $conn->query($tenant_sql);
$tenants = $tenant_result->fetch_all(MYSQLI_ASSOC);
// Fetch building data
$building_sql = "SELECT building_id, building_name FROM building";
$building_result = $conn->query($building_sql);
$buildings = $building_result->fetch_all(MYSQLI_ASSOC);

// Fetch floor data
$floor_sql = "SELECT floor_id, number FROM floor";
$floor_result = $conn->query($floor_sql);
$floors = $floor_result->fetch_all(MYSQLI_ASSOC);

// Fetch room data
$room_sql = "SELECT room_id, room_no, category, area, monthly_price, status FROM room";
$room_result = $conn->query($room_sql);
$rooms = $room_result->fetch_all(MYSQLI_ASSOC);

// Fetch user permissions from the database
$username = $_SESSION['username'];
$sql = "SELECT role FROM xionbms_users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$role = $user['role'];

// Pagination logic
$limit = 12; // Number of entries to show in a page.
$page = isset($_GET["page"]) ? intval($_GET["page"]) : 1;
$start_from = ($page - 1) * $limit;

// Search logic
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_param = '%' . $search . '%';

// Updated query to join payments with tenants to get tenant's full name and search functionality
$query = "
    SELECT 
        payment.*, 
        tenant.full_name AS tenant_full_name
    FROM 
        payment
    JOIN 
        tenant
    ON 
        payment.tenant = tenant.tenant_id
    WHERE 
        tenant.full_name LIKE ? OR
        payment.tenant_tin LIKE ? OR
        payment.room LIKE ? OR
        payment.fs_number LIKE ? OR
        payment.payment_method LIKE ? OR
        payment.bank_name LIKE ? OR
        payment.transaction_no LIKE ?
    ORDER BY 
        payment.payment_date DESC 
    LIMIT ?, ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssssssssi", $search_param, $search_param, $search_param, $search_param, $search_param, $search_param, $search_param, $start_from, $limit);
$stmt->execute();
$result = $stmt->get_result();
$payments = $result->fetch_all(MYSQLI_ASSOC);



// Handle success and error messages
$success_message = isset($_GET['success']) ? htmlspecialchars($_GET['success'], ENT_QUOTES, 'UTF-8') : '';
$error_message = isset($_GET['error']) ? htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') : '';


// Determine the current action
$action = isset($_GET['action']) ? $_GET['action'] : '';
$payment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$editing_payment = null;

$editing_payment = null;
$additional_fees = []; // Initialize the array

// Fetch payment details if editing
if ($action === 'edit' && $payment_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM payment WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $editing_payment = $result->fetch_assoc();

            // Fetch additional fees
            $fees_stmt = $conn->prepare("SELECT title, price, description, registration_date FROM additional_fee WHERE payment_id = ?");
            if ($fees_stmt) {
                $fees_stmt->bind_param("i", $payment_id);
                $fees_stmt->execute();
                $fees_result = $fees_stmt->get_result();
                while ($fee = $fees_result->fetch_assoc()) {
                    $additional_fees[] = $fee;
                }
                $editing_payment['additional_fees'] = $additional_fees;
                $fees_stmt->close();
            }
        } else {
            echo "Payment not found.";
            exit;
        }
        $stmt->close();
    } else {
        // Handle statement preparation error
        echo "Error preparing statement: " . $conn->error;
        exit;
    }
}

// Get total number of records
$sql = "SELECT COUNT(id) FROM payment";
$result = $conn->query($sql);
$row = $result->fetch_row();
$total_records = $row[0];
$total_pages = ceil($total_records / $limit);

// Handle Add Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    // Include the add_payment.php logic here or redirect to it
    // For simplicity, assuming the add_payment.php handles adding and redirects back
    include 'add_payment.php';
    exit();
}

// Handle Edit Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_payment'])) {
    // Include the edit_payment.php logic here or redirect to it
    // For simplicity, assuming the edit_payment.php handles editing and redirects back
    include 'edit_payment.php';
    exit();
}

// Handle Delete Payment
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    include 'delete_payment.php';
    exit();
}

// Fetch penality data
$sql = "SELECT * FROM penality";
$result = $conn->query($sql);
$penalities = $result->fetch_all(MYSQLI_ASSOC);

// Convert penalities to JSON for JavaScript
$penalities_json = json_encode($penalities);

?>
<!doctype html>
<html lang="en" dir="ltr" data-bs-theme="light" data-bs-theme-color="theme-color-default">
  <head>
    <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
      <title>Building Management System</title>
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
   <!-- Bootstrap CSS -->
   <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">

   <!-- Bootstrap Bundle with Popper -->
   <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
      <!-- Favicon -->
      <link rel="shortcut icon" href="../../assets/images/favicon.ico">
      
      <!-- Library / Plugin Css Build -->
      <link rel="stylesheet" href="../../assets/css/core/libs.min.css">
      
      
      <!-- Nest-Net Design System Css -->
      <link rel="stylesheet" href="../../assets/css/hope-ui.min.css?v=5.0.0">
      
      <!-- Custom Css -->
      <link rel="stylesheet" href="../../assets/css/custom.min.css?v=5.0.0">
      
      <!-- Customizer Css -->
      <link rel="stylesheet" href="../../assets/css/customizer.min.css?v=5.0.0">
      
      <!-- RTL Css -->
      <link rel="stylesheet" href="../../assets/css/rtl.min.css?v=5.0.0">
       <style>
      .form-control {
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        box-shadow: none;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
      }
      .form-control:focus {
        border-color: #80bdff;
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
      }
      .form-label {
        font-weight: bold;
      }
      .modal-content {
        border-radius: 0.5rem;
      }

.container-fluid.content-inner {
    padding-top: 0;
}

.card {
    margin-top: 0;
}

.hidden {
            display: none;
        }
    
/* Media query for screens with a max width of 426px */
@media (max-width: 426px) {
    .navbar-brand h4.logo-title {
        font-size: 1.2rem; /* Adjust the font size for the logo title */
    }

    .navbar-toggler {
        font-size: 1rem; /* Adjust the font size for the navbar toggler */
    }

    .search-input {
        width: 100%; /* Make the search input take full width */
    }

    .nav-item .nav-link {
        font-size: 0.9rem; /* Adjust the font size for nav links */
    }

    .table-responsive {
        overflow-x: auto; /* Enable horizontal scrolling for tables */
    }

    .table thead th {
        font-size: 0.8rem; /* Adjust the font size for table headers */
    }

    .table tbody td {
        font-size: 0.8rem; /* Adjust the font size for table cells */
    }

    .modal-dialog {
        width: 100%; /* Make modals take full width */
        margin: 0; /* Remove default margin */
    }

    .modal-content {
        padding: 1rem; /* Add padding to modal content */
    }

    .form-control {
        font-size: 0.9rem; /* Adjust the font size for form controls */
    }

    .btn {
        font-size: 0.9rem; /* Adjust the font size for buttons */
    }

    .pagination {
        font-size: 0.8rem; /* Adjust the font size for pagination */
    }

    .pagination .page-item .page-link {
        padding: 0.5rem; /* Adjust the padding for pagination links */
    }
}
/* Add this CSS to your existing styles */
@media (max-width: 768px) {
    .card {
      margin-top: 1em;
      padding: 1em;
    }
 
    .table-responsive {
        overflow-x: auto;
    }
    .table thead {
        display: none;
    }
    .table tbody tr {
        display: flex;
        flex-direction: column;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 10px;
        margin-bottom: 10px;
    }
    .table tbody tr td {
        display: block;
        padding: 5px 0;
    }
    .table tbody tr td::before {
        content: attr(data-label);
        font-weight: bold;
        display: block;
        margin-bottom: 5px;
    }
    .table tbody tr td:last-child {
        justify-content: flex-start;
    }
}
      </style>
  </head>
  <body class="  ">
    <!-- loader Start -->
    <div id="loading">
      <div class="loader simple-loader">
          <div class="loader-body">
          </div>
      </div>    </div>
    <!-- loader END -->
    
    <aside class="sidebar sidebar-default sidebar-white sidebar-base navs-rounded-all ">
        <div class="sidebar-header d-flex align-items-center justify-content-start">
            <a href="../../dashboard/index.php" class="navbar-brand">
                
                <!--Logo start-->
                <div class="logo-main">
    <div class="logo-normal">
        <svg class="icon-30" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="8" y="8" width="48" height="48" rx="4" fill="currentColor"/>
            <rect x="16" y="16" width="8" height="8" fill="white"/>
            <rect x="28" y="16" width="8" height="8" fill="white"/>
            <rect x="40" y="16" width="8" height="8" fill="white"/>
            <rect x="16" y="28" width="8" height="8" fill="white"/>
            <rect x="28" y="28" width="8" height="8" fill="white"/>
            <rect x="40" y="28" width="8" height="8" fill="white"/>
            <rect x="16" y="40" width="8" height="8" fill="white"/>
            <rect x="28" y="40" width="8" height="8" fill="white"/>
            <rect x="40" y="40" width="8" height="8" fill="white"/>
        </svg>
    </div>
    <div class="logo-mini">
        <svg class="icon-30" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="8" y="8" width="48" height="48" rx="4" fill="currentColor"/>
            <rect x="16" y="16" width="8" height="8" fill="white"/>
            <rect x="28" y="16" width="8" height="8" fill="white"/>
            <rect x="40" y="16" width="8" height="8" fill="white"/>
            <rect x="16" y="28" width="8" height="8" fill="white"/>
            <rect x="28" y="28" width="8" height="8" fill="white"/>
            <rect x="40" y="28" width="8" height="8" fill="white"/>
            <rect x="16" y="40" width="8" height="8" fill="white"/>
            <rect x="28" y="40" width="8" height="8" fill="white"/>
            <rect x="40" y="40" width="8" height="8" fill="white"/>
        </svg>
    </div>
</div>
                <!--logo End-->
                
                
                
                
                <h4 class="logo-title">Nest-Net
            </a>
            <div class="sidebar-toggle" data-toggle="sidebar" data-active="true">
                <i class="icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4.25 12.2744L19.25 12.2744" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M10.2998 18.2988L4.2498 12.2748L10.2998 6.24976" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </i>
            </div>
        </div>
        <div class="sidebar-body pt-0 data-scrollbar">
            <div class="sidebar-list">
                <!-- Sidebar Menu Start -->
                <ul class="navbar-nav iq-main-menu" id="sidebar-menu">
                    <li class="nav-item static-item">
                        <a class="nav-link static-item disabled" href="#" tabindex="-1">
                            <span class="default-icon">Home</span>
                            <span class="mini-icon">-</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="../../dashboard/index.php">
                            <i class="icon">
                                <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-20">
                                    <path opacity="0.4" d="M16.0756 2H19.4616C20.8639 2 22.0001 3.14585 22.0001 4.55996V7.97452C22.0001 9.38864 20.8639 10.5345 19.4616 10.5345H16.0756C14.6734 10.5345 13.5371 9.38864 13.5371 7.97452V4.55996C13.5371 3.14585 14.6734 2 16.0756 2Z" fill="currentColor"></path>
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M4.53852 2H7.92449C9.32676 2 10.463 3.14585 10.463 4.55996V7.97452C10.463 9.38864 9.32676 10.5345 7.92449 10.5345H4.53852C3.13626 10.5345 2 9.38864 2 7.97452V4.55996C2 3.14585 3.13626 2 4.53852 2ZM4.53852 13.4655H7.92449C9.32676 13.4655 10.463 14.6114 10.463 16.0255V19.44C10.463 20.8532 9.32676 22 7.92449 22H4.53852C3.13626 22 2 20.8532 2 19.44V16.0255C2 14.6114 3.13626 13.4655 4.53852 13.4655ZM19.4615 13.4655H16.0755C14.6732 13.4655 13.537 14.6114 13.537 16.0255V19.44C13.537 20.8532 14.6732 22 16.0755 22H19.4615C20.8637 22 22 20.8532 22 19.44V16.0255C22 14.6114 20.8637 13.4655 19.4615 13.4655Z" fill="currentColor"></path>
                                </svg>
                            </i>
                            <span class="item-name">Dashboard</span>
                        </a>
                    </li>
                   
                     <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="./company-info.php">
                            <i class="icon">
                                 <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M21.9964 8.37513H17.7618C15.7911 8.37859 14.1947 9.93514 14.1911 11.8566C14.1884 13.7823 15.7867 15.3458 17.7618 15.3484H22V15.6543C22 19.0136 19.9636 21 16.5173 21H7.48356C4.03644 21 2 19.0136 2 15.6543V8.33786C2 4.97862 4.03644 3 7.48356 3H16.5138C19.96 3 21.9964 4.97862 21.9964 8.33786V8.37513ZM6.73956 8.36733H12.3796H12.3831H12.3902C12.8124 8.36559 13.1538 8.03019 13.152 7.61765C13.1502 7.20598 12.8053 6.87318 12.3831 6.87491H6.73956C6.32 6.87664 5.97956 7.20858 5.97778 7.61852C5.976 8.03019 6.31733 8.36559 6.73956 8.36733Z" fill="currentColor"></path>
                                    <path opacity="0.4" d="M16.0374 12.2966C16.2465 13.2478 17.0805 13.917 18.0326 13.8996H21.2825C21.6787 13.8996 22 13.5715 22 13.166V10.6344C21.9991 10.2297 21.6787 9.90077 21.2825 9.8999H17.9561C16.8731 9.90338 15.9983 10.8024 16 11.9102C16 12.0398 16.0128 12.1695 16.0374 12.2966Z" fill="currentColor"></path>
                                    <circle cx="18" cy="11.8999" r="1" fill="currentColor"></circle>
                                </svg>
                                                         
                            </i>
                            <span class="item-name">Company Information</span>
                        </a>
                    </li>
                    <li><hr class="hr-horizontal"></li>
                    <li class="nav-item static-item">
                        <a class="nav-link static-item disabled" href="#" tabindex="-1">
                            <span class="default-icon">Pages</span>
                            <span class="mini-icon">-</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="./tenant.php">
                            <i class="icon">
                            <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="12" cy="8" r="4" fill="currentColor"></circle>
                                    <path d="M4 20c0-4 4-6 8-6s8 2 8 6" fill="currentColor"></path>
                                </svg>
                                                         
                            </i>
                            <span class="item-name">Tenant</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="./payment.php">
                            <i class="icon">
                            <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M2 7h20v10H2V7z" fill="currentColor"></path>
                                    <path d="M2 10h20" stroke="white" stroke-width="2"></path>
                                    <path d="M6 14h4" stroke="white" stroke-width="2"></path>
                                </svg>
                                                         
                            </i>
                            <span class="item-name">Payment</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="./document.php">
                            <i class="icon">
                            <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3 3h18v18H3V3z" fill="currentColor"></path>
                                    <path d="M6 6h12v12H6V6z" fill="white"></path>
                                 </svg>
                                                         
                            </i>
                            <span class="item-name">Document</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="./expense_record.php">
                            <i class="icon">
                            <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 2L2 22h20L12 2z" fill="currentColor"></path>
                                    <path d="M12 8v8" stroke="white" stroke-width="2"></path>
                                    <path d="M8 16h8" stroke="white" stroke-width="2"></path>
                                 </svg>
                                                         
                            </i>
                            <span class="item-name">Expense Record</span>
                        </a>
                    </li>
                   
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="./tenant_property.php">
                            <i class="icon">
                            <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 2L2 12h10v10h4V12h10L12 2z" fill="currentColor"></path>
                                 </svg>
                                                         
                            </i>
                            <span class="item-name">Tenant Property</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="collapse" href="#sidebar-user" role="button" aria-expanded="false" aria-controls="sidebar-user">
                            <i class="icon">
                                <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M11.9488 14.54C8.49884 14.54 5.58789 15.1038 5.58789 17.2795C5.58789 19.4562 8.51765 20.0001 11.9488 20.0001C15.3988 20.0001 18.3098 19.4364 18.3098 17.2606C18.3098 15.084 15.38 14.54 11.9488 14.54Z" fill="currentColor"></path>
                                    <path opacity="0.4" d="M11.949 12.467C14.2851 12.467 16.1583 10.5831 16.1583 8.23351C16.1583 5.88306 14.2851 4 11.949 4C9.61293 4 7.73975 5.88306 7.73975 8.23351C7.73975 10.5831 9.61293 12.467 11.949 12.467Z" fill="currentColor"></path>
                                    <path opacity="0.4" d="M21.0881 9.21923C21.6925 6.84176 19.9205 4.70654 17.664 4.70654C17.4187 4.70654 17.1841 4.73356 16.9549 4.77949C16.9244 4.78669 16.8904 4.802 16.8725 4.82902C16.8519 4.86324 16.8671 4.90917 16.8895 4.93889C17.5673 5.89528 17.9568 7.0597 17.9568 8.30967C17.9568 9.50741 17.5996 10.6241 16.9728 11.5508C16.9083 11.6462 16.9656 11.775 17.0793 11.7948C17.2369 11.8227 17.3981 11.8371 17.5629 11.8416C19.2059 11.8849 20.6807 10.8213 21.0881 9.21923Z" fill="currentColor"></path>
                                    <path d="M22.8094 14.817C22.5086 14.1722 21.7824 13.73 20.6783 13.513C20.1572 13.3851 18.747 13.205 17.4352 13.2293C17.4155 13.232 17.4048 13.2455 17.403 13.2545C17.4003 13.2671 17.4057 13.2887 17.4316 13.3022C18.0378 13.6039 20.3811 14.916 20.0865 17.6834C20.074 17.8032 20.1698 17.9068 20.2888 17.8888C20.8655 17.8059 22.3492 17.4853 22.8094 16.4866C23.0637 15.9589 23.0637 15.3456 22.8094 14.817Z" fill="currentColor"></path>
                                    <path opacity="0.4" d="M7.04459 4.77973C6.81626 4.7329 6.58077 4.70679 6.33543 4.70679C4.07901 4.70679 2.30701 6.84201 2.9123 9.21947C3.31882 10.8216 4.79355 11.8851 6.43661 11.8419C6.60136 11.8374 6.76343 11.8221 6.92013 11.7951C7.03384 11.7753 7.09115 11.6465 7.02668 11.551C6.3999 10.6234 6.04263 9.50765 6.04263 8.30991C6.04263 7.05904 6.43303 5.89462 7.11085 4.93913C7.13234 4.90941 7.14845 4.86348 7.12696 4.82926C7.10906 4.80135 7.07593 4.78694 7.04459 4.77973Z" fill="currentColor"></path>
                                    <path d="M3.32156 13.5127C2.21752 13.7297 1.49225 14.1719 1.19139 14.8167C0.936203 15.3453 0.936203 15.9586 1.19139 16.4872C1.65163 17.4851 3.13531 17.8066 3.71195 17.8885C3.83104 17.9065 3.92595 17.8038 3.91342 17.6832C3.61883 14.9167 5.9621 13.6046 6.56918 13.3029C6.59425 13.2885 6.59962 13.2677 6.59694 13.2542C6.59515 13.2452 6.5853 13.2317 6.5656 13.2299C5.25294 13.2047 3.84358 13.3848 3.32156 13.5127Z" fill="currentColor"></path>
                                </svg>
                            </i>
                            <span class="item-name">User</span>
                            <i class="right-icon">
                                <svg class="icon-18" xmlns="http://www.w3.org/2000/svg" width="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </i>
                        </a>
                        <ul class="sub-nav collapse" id="sidebar-user" data-bs-parent="#sidebar-menu">
                            <li class="nav-item">
                                <a class="nav-link " href="./user-profile.php">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> U </i>
                                    <span class="item-name">User Profile</span>
                                </a>
                            </li>
                            
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="collapse" href="#utilities-error" role="button" aria-expanded="false" aria-controls="utilities-error">
                            <i class="icon">
                                <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.4" d="M11.9912 18.6215L5.49945 21.864C5.00921 22.1302 4.39768 21.9525 4.12348 21.4643C4.0434 21.3108 4.00106 21.1402 4 20.9668V13.7087C4 14.4283 4.40573 14.8725 5.47299 15.37L11.9912 18.6215Z" fill="currentColor"></path>
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M8.89526 2H15.0695C17.7773 2 19.9735 3.06605 20 5.79337V20.9668C19.9989 21.1374 19.9565 21.3051 19.8765 21.4554C19.7479 21.7007 19.5259 21.8827 19.2615 21.9598C18.997 22.0368 18.7128 22.0023 18.4741 21.8641L11.9912 18.6215L5.47299 15.3701C4.40573 14.8726 4 14.4284 4 13.7088V5.79337C4 3.06605 6.19625 2 8.89526 2ZM8.22492 9.62227H15.7486C16.1822 9.62227 16.5336 9.26828 16.5336 8.83162C16.5336 8.39495 16.1822 8.04096 15.7486 8.04096H8.22492C7.79137 8.04096 7.43991 8.39495 7.43991 8.83162C7.43991 9.26828 7.79137 9.62227 8.22492 9.62227Z" fill="currentColor"></path>
                                </svg>
                            </i>
                            <span class="item-name">Property</span>
                            <i class="right-icon">
                                <svg class="icon-18" xmlns="http://www.w3.org/2000/svg" width="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </i>
                        </a>
                        <ul class="sub-nav collapse" id="utilities-error" data-bs-parent="#sidebar-menu">
                            <li class="nav-item">
                                <a class="nav-link " href="./room.php">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <span class="item-name">Room</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link " href="./floor.php">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <span class="item-name">Floor</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link " href="./building.php">
                                    <i class="icon">
                                        <svg  class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <span class="item-name">Building</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                     
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="collapse" href="#sidebar-auth" role="button" aria-expanded="false" aria-controls="sidebar-user">
                            <i class="icon">
                                <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.4" d="M12.0865 22C11.9627 22 11.8388 21.9716 11.7271 21.9137L8.12599 20.0496C7.10415 19.5201 6.30481 18.9259 5.68063 18.2336C4.31449 16.7195 3.5544 14.776 3.54232 12.7599L3.50004 6.12426C3.495 5.35842 3.98931 4.67103 4.72826 4.41215L11.3405 2.10679C11.7331 1.96656 12.1711 1.9646 12.5707 2.09992L19.2081 4.32684C19.9511 4.57493 20.4535 5.25742 20.4575 6.02228L20.4998 12.6628C20.5129 14.676 19.779 16.6274 18.434 18.1581C17.8168 18.8602 17.0245 19.4632 16.0128 20.0025L12.4439 21.9088C12.3331 21.9686 12.2103 21.999 12.0865 22Z" fill="currentColor"></path>
                                    <path d="M11.3194 14.3209C11.1261 14.3219 10.9328 14.2523 10.7838 14.1091L8.86695 12.2656C8.57097 11.9793 8.56795 11.5145 8.86091 11.2262C9.15387 10.9369 9.63207 10.934 9.92906 11.2193L11.3083 12.5451L14.6758 9.22479C14.9698 8.93552 15.448 8.93258 15.744 9.21793C16.041 9.50426 16.044 9.97004 15.751 10.2574L11.8519 14.1022C11.7049 14.2474 11.5127 14.3199 11.3194 14.3209Z" fill="currentColor"></path>
                                </svg>
                            </i>
                            <span class="item-name">Category</span>
                            <i class="right-icon">
                                <svg class="icon-18" xmlns="http://www.w3.org/2000/svg" width="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </i>
                        </a>
                        <ul class="sub-nav collapse" id="sidebar-auth" data-bs-parent="#sidebar-menu">
                            <li class="nav-item">
                                <a class="nav-link" href="./room_cat.php">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> L </i>
                                    <span class="item-name">Room Category</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="./expense_type.php">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> R </i>
                                    <span class="item-name">Expense Type</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="./document_cat.php">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> C </i>
                                    <span class="item-name">Document Category</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="./banks.php">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> L </i>
                                    <span class="item-name">Banks</span>
                                </a>
                            </li>
                            <!-- <li class="nav-item">
                                <a class="nav-link" href="../dashboard/auth/recoverpw.html">
                                   <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> R </i>
                                    <span class="item-name">Recover password</span>
                                </a>
                            </li> -->
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link"  href="./additional_fee">
                            <i class="icon">
                            <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 2L2 12h10v10h4V12h10L12 2z" fill="currentColor"></path>
                                    <path d="M12 8v8" stroke="white" stroke-width="2"></path>
                                    <path d="M8 16h8" stroke="white" stroke-width="2"></path>
                                </svg>
                            </i>
                            <span class="item-name">Additional Fee</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link"  href="./penality.php">
                            <i class="icon">
                            <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 2L2 12h10v10h4V12h10L12 2z" fill="currentColor"></path>
                                </svg>
                            </i>
                            <span class="item-name">Penality</span>
                        </a>
                    </li>
                   
                    <?php if ($role === 'admin'): ?>
                      <li class="nav-item">
                        <a class="nav-link "  href="../../dashboard/admin.php">
                            <i class="icon">
                            <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M7.7688 8.71387H16.2312C18.5886 8.71387 20.5 10.5831 20.5 12.8885V17.8254C20.5 20.1308 18.5886 22 16.2312 22H7.7688C5.41136 22 3.5 20.1308 3.5 17.8254V12.8885C3.5 10.5831 5.41136 8.71387 7.7688 8.71387ZM11.9949 17.3295C12.4928 17.3295 12.8891 16.9419 12.8891 16.455V14.2489C12.8891 13.772 12.4928 13.3844 11.9949 13.3844C11.5072 13.3844 11.1109 13.772 11.1109 14.2489V16.455C11.1109 16.9419 11.5072 17.3295 11.9949 17.3295Z" fill="currentColor"></path>
                            <path opacity="0.4" d="M17.523 7.39595V8.86667C17.1673 8.7673 16.7913 8.71761 16.4052 8.71761H15.7447V7.39595C15.7447 5.37868 14.0681 3.73903 12.0053 3.73903C9.94257 3.73903 8.26594 5.36874 8.25578 7.37608V8.71761H7.60545C7.20916 8.71761 6.83319 8.7673 6.47754 8.87661V7.39595C6.4877 4.41476 8.95692 2 11.985 2C15.0537 2 17.523 4.41476 17.523 7.39595Z" fill="currentColor"></path>
                            </svg>
                            </i>
                            <span class="item-name">Admin</span>
                        </a>
                    </li>
                    <?php endif; ?>


                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="collapse" href="#sidebar-table" role="button" aria-expanded="false" aria-controls="sidebar-table">
                            <i class="icon">
                                <svg class="icon-20" xmlns="http://www.w3.org/2000/svg" width="20"  viewBox="0 0 24 24" fill="none">
                                    <path d="M2 5C2 4.44772 2.44772 4 3 4H8.66667H21C21.5523 4 22 4.44772 22 5V8H15.3333H8.66667H2V5Z" fill="currentColor" stroke="currentColor"/>
                                    <path d="M6 8H2V11M6 8V20M6 8H14M6 20H3C2.44772 20 2 19.5523 2 19V11M6 20H14M14 8H22V11M14 8V20M14 20H21C21.5523 20 22 19.5523 22 19V11M2 11H22M2 14H22M2 17H22M10 8V20M18 8V20" stroke="currentColor"/>
                                </svg>
                            </i>
                            <span class="item-name">Table</span>
                            <i class="right-icon">
                                <svg class="icon-18" xmlns="http://www.w3.org/2000/svg" width="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </i>
                        </a>
                        <ul class="sub-nav collapse" id="sidebar-table" data-bs-parent="#sidebar-menu">
                            <li class="nav-item">
                                <a class="nav-link " href="../../dashboard/table/bootstrap-table.html">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> B </i>
                                    <span class="item-name">Bootstrap Table</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link " href="../../dashboard/table/table-data.html">
                                   <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                   <i class="sidenav-mini-icon"> D </i>
                                   <span class="item-name">Datatable</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item mb-5">
                        <a class="nav-link" data-bs-toggle="collapse" href="#sidebar-icons" role="button" aria-expanded="false" aria-controls="sidebar-icons">
                            <i class="icon">
                                <svg class="icon-20" xmlns="http://www.w3.org/2000/svg" width="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M8 10.5378C8 9.43327 8.89543 8.53784 10 8.53784H11.3333C12.4379 8.53784 13.3333 9.43327 13.3333 10.5378V19.8285C13.3333 20.9331 14.2288 21.8285 15.3333 21.8285H16C16 21.8285 12.7624 23.323 10.6667 22.9361C10.1372 22.8384 9.52234 22.5913 9.01654 22.3553C8.37357 22.0553 8 21.3927 8 20.6832V10.5378Z" fill="currentColor"/>
                                    <rect opacity="0.4" x="8" y="1" width="5" height="5" rx="2.5" fill="currentColor"/>
                                </svg>
                            </i>
                            <span class="item-name">Icons</span>
                            <i class="right-icon">
                                <svg class="icon-18" xmlns="http://www.w3.org/2000/svg" width="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </i>
                        </a>
                        <ul class="sub-nav collapse" id="sidebar-icons" data-bs-parent="#sidebar-menu">
                            <li class="nav-item">
                                <a class="nav-link " href="../../dashboard/icons/solid.html">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> S </i>
                                     <span class="item-name">Solid</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link " href="../../dashboard/icons/outline.html">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> O </i>
                                     <span class="item-name">Outlined</span></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link " href="../../dashboard/icons/dual-tone.html">
                                   <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> D </i>
                                     <span class="item-name">Dual Tone</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
                <!-- Sidebar Menu End -->        </div>
        </div>
        <div class="sidebar-footer"></div>
    </aside>    <main class="main-content">
      <div class="position-relative iq-banner">
        <!--Nav Start-->
        <nav class="nav navbar navbar-expand-xl navbar-light iq-navbar">
          <div class="container-fluid navbar-inner">
            <a href="../../dashboard/index.php" class="navbar-brand">
                
                <!--Logo start-->
                <div class="logo-main">
                    <div class="logo-normal">
                        <svg class="text-primary icon-30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="-0.757324" y="19.2427" width="28" height="4" rx="2" transform="rotate(-45 -0.757324 19.2427)" fill="currentColor"/>
                            <rect x="7.72803" y="27.728" width="28" height="4" rx="2" transform="rotate(-45 7.72803 27.728)" fill="currentColor"/>
                            <rect x="10.5366" y="16.3945" width="16" height="4" rx="2" transform="rotate(45 10.5366 16.3945)" fill="currentColor"/>
                            <rect x="10.5562" y="-0.556152" width="28" height="4" rx="2" transform="rotate(45 10.5562 -0.556152)" fill="currentColor"/>
                        </svg>
                    </div>
                    <div class="logo-mini">
                        <svg class="text-primary icon-30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="-0.757324" y="19.2427" width="28" height="4" rx="2" transform="rotate(-45 -0.757324 19.2427)" fill="currentColor"/>
                            <rect x="7.72803" y="27.728" width="28" height="4" rx="2" transform="rotate(-45 7.72803 27.728)" fill="currentColor"/>
                            <rect x="10.5366" y="16.3945" width="16" height="4" rx="2" transform="rotate(45 10.5366 16.3945)" fill="currentColor"/>
                            <rect x="10.5562" y="-0.556152" width="28" height="4" rx="2" transform="rotate(45 10.5562 -0.556152)" fill="currentColor"/>
                        </svg>
                    </div>
                </div>
                <!--logo End-->
                
                
                
                
                <h4 class="logo-title">Nest-Net
            </a>
            <div class="sidebar-toggle" data-toggle="sidebar" data-active="true">
                <i class="icon">
                 <svg  width="20px" class="icon-20" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M4,11V13H16L10.5,18.5L11.92,19.92L19.84,12L11.92,4.08L10.5,5.5L16,11H4Z" />
                </svg>
                </i>
            </div>
            <form method="GET" action="payment.php">
    <div class="input-group search-input">
        <span class="input-group-text" id="search-input">
            <svg class="icon-18" width="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="11.7669" cy="11.7666" r="8.98856" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></circle>
                <path d="M18.0186 18.4851L21.5426 22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
        </span>
        <input type="search" class="form-control" name="search" placeholder="Search..." value="<?= htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </div>
</form>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
              <span class="navbar-toggler-icon">
                  <span class="mt-2 navbar-toggler-bar bar1"></span>
                  <span class="navbar-toggler-bar bar2"></span>
                  <span class="navbar-toggler-bar bar3"></span>
                </span>
            </button>
            
          </div>
        </nav>          <!-- Nav Header Component Start -->
        <div class="container-fluid content-inner mt-n2 py-0">
    <div class="row">
        <div class="col-12">
            <br><br>
                          <!-- Payments List -->
        <?php if ($action !== 'add' && $action !== 'edit'): ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Payments</h2>
                <?php if ($role === 'admin'): ?>
                <a href="payment.php?action=add" class="btn btn-primary">Add Payment</a>
                <?php endif; ?>
            </div>
            <div class="card mt--1">
                <div class="card-body table-responsive p-0">
                    <?php if (count($payments) > 0): ?>
                        <table class="table table-bordered">
    <thead>
        <tr>
            <th>#</th>
            <th>Actions</th>
            <th>Print</th>
            <th>Payment Id</th>
            <th>Tenant Name</th>
            <th>Tenant Tin No</th>
            <th>Room</th>
            <th>Rent Amount</th>
            <th>Price</th>
            <th>VAT</th>
            <th>Withhold</th>
            <th>Discount</th>
            <th>Penality</th>
            <th>Total</th>
            <th>Fs Number</th>
            <th>Paid Months</th>
            <th>Payment Method</th>
            <th>Rent Due Date</th>
            <th>Payment Date</th>
            <th>Paid Days</th>
            <th>Bank Name</th>
            <th>Cheque Ref No</th>
            <th>Transaction No</th>
            <th>Deposited Amount</th>
            <th>Received By</th>
        </tr>
    </thead>
    <tbody>
        <?php 
            $i = $start_from + 1;
            foreach ($payments as $payment): ?>
                <tr>
                    <td data-label="#"><?= $i ?></td>
                    <td data-label="Actions">
                        <?php if ($role === 'admin'): ?>
                        <a href="payment.php?action=edit&id=<?= htmlspecialchars($payment['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i></a>
                        <a href="delete_payment.php?id=<?= htmlspecialchars($payment['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this payment?');"><i class="fas fa-trash"></i></a>
                    <?php endif; ?>
                    </td>
                    <td data-label="Print">
                        <button class="btn btn-secondary btn-sm" onclick="printPayment(<?= htmlspecialchars($payment['id'], ENT_QUOTES, 'UTF-8') ?>)"><i class="fas fa-print"></i> Print</button>
                    </td>
                    <td data-label="Payment Id"><?= htmlspecialchars($payment['id'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td data-label="Tenant Name"><?= htmlspecialchars($payment['tenant_full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td data-label="Tenant Tin No"><?= htmlspecialchars($payment['tenant_tin'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td data-label="Room"><?= htmlspecialchars($payment['room'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td data-label="Rent Amount"><?= htmlspecialchars(number_format($payment['rent_amount'] ?? 0, 2), ENT_QUOTES, 'UTF-8') ?></td>
                    <td data-label="Price"><?= htmlspecialchars(number_format($payment['price'] ?? 0, 2), ENT_QUOTES, 'UTF-8') ?></td>
                    <td data-label="VAT"><?= htmlspecialchars(number_format($payment['vat'] ?? 0, 2), ENT_QUOTES, 'UTF-8') ?></td>
                    <td data-label="Withhold"><?= htmlspecialchars(number_format($payment['withhold'] ?? 0, 2), ENT_QUOTES, 'UTF-8') ?></td>
                    <td data-label="Discount"><?= htmlspecialchars(number_format($payment['discount'] ?? 0, 2), ENT_QUOTES, 'UTF-8') ?></td>
                    <td data-label="Penality"><?= htmlspecialchars(number_format($payment['penality'] ?? 0, 2), ENT_QUOTES, 'UTF-8') ?></td>
                    <td data-label="Total"><?= htmlspecialchars(number_format($payment['total'] ?? 0, 2), ENT_QUOTES, 'UTF-8') ?></td>
                    <td data-label="Fs Number"><?= htmlspecialchars($payment['fs_number'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td data-label="Paid Months"><?= htmlspecialchars($payment['paid_months'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td data-label="Payment Method"><?= htmlspecialchars($payment['payment_method'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td data-label="Rent Due Date"><?= htmlspecialchars($payment['rent_due_date'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td data-label="Payment Date"><?= htmlspecialchars($payment['payment_date'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td data-label="Paid Days"><?= htmlspecialchars($payment['paid_days'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td data-label="Bank Name"><?= htmlspecialchars($payment['bank_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td data-label="Cheque Ref No"><?= htmlspecialchars($payment['cheque_ref_no'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td data-label="Transaction No"><?= htmlspecialchars($payment['transaction_no'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td data-label="Deposited Amount"><?= htmlspecialchars(number_format($payment['deposited_amount'] ?? 0, 2), ENT_QUOTES, 'UTF-8') ?></td>
                    <td data-label="Received By"><?= htmlspecialchars($payment['received_by'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php $i++; ?>
        <?php endforeach; ?>
    </tbody>
</table>
                    <?php else: ?>
                        <p class="text-center">No payments found.</p>
                    <?php endif; ?>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav class="mt-3 d-flex justify-content-center flex-wrap" aria-label="Payment pagination">
        <ul class="pagination">
            <!-- Previous Button -->
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="payment.php?page=<?= max(1, $page - 1) ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                    <span class="visually-hidden">Previous</span>
                </a>
            </li>

            <!-- Page Numbers -->
            <?php
                // Define the range of pages to display
                $range = 2; // Number of pages to show on either side of the current page
                $start = max(1, $page - $range);
                $end = min($total_pages, $page + $range);

                if ($start > 1) {
                    echo '<li class="page-item"><a class="page-link" href="payment.php?page=1">1</a></li>';
                    if ($start > 2) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }

                for ($i = $start; $i <= $end; $i++):
            ?>
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                    <a class="page-link" href="payment.php?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>

            <?php
                if ($end < $total_pages) {
                    if ($end < $total_pages - 1) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    echo '<li class="page-item"><a class="page-link" href="payment.php?page=' . $total_pages . '">' . $total_pages . '</a></li>';
                }
            ?>

            <!-- Next Button -->
            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                <a class="page-link" href="payment.php?page=<?= min($total_pages, $page + 1) ?>" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                    <span class="visually-hidden">Next</span>
                </a>
            </li>
        </ul>
    </nav>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>   

<!-- Add Payment Form -->
<?php if ($action === 'add'): ?>
    <h2 class="mb-3">Add New Payment</h2>
    <form id="addPaymentForm">
        <!-- Payment Information Section -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white p-2">Payment Information</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="tenant" class="form-label">Tenant Name <span style="color:red;">*</span></label>
                        <div class="input-group">
                            <select class="form-control" id="tenant" name="tenant" required>
                                <option value="">Please select</option>
                                <?php foreach ($tenants as $tenant): ?>
                                    <option value="<?= htmlspecialchars($tenant['tenant_id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($tenant['full_name'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <a href="#" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addTenantModal">Add New</a>                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="tenant_tin_no" class="form-label">Tenant Tin No</label>
                        <input type="text" class="form-control" id="tenant_tin_no" name="tenant_tin_no" readonly style="background-color: #dcdcdc; opacity: 1; border-color: #dcdcdc;" >
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="room" class="form-label">Room <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="room" name="room" required readonly style="background-color: #dcdcdc; opacity: 1; border-color: #dcdcdc;">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="fs_number" class="form-label">Fs Number<span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="fs_number" name="fs_number" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="rent_amount" class="form-label">Rent Amount</label>
                        <input type="number" class="form-control" id="rent_amount" name="rent_amount" readonly style="background-color: #dcdcdc; opacity: 1; border-color: #dcdcdc;">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="rent_due_date" class="form-label">Rent Due Date</label>
                        <input type="date" class="form-control" id="rent_due_date" name="rent_due_date" readonly style="background-color: #dcdcdc; opacity: 1; border-color: #dcdcdc;">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="received_by" class="form-label">Received By</label>
                        <input type="text" class="form-control" id="received_by" name="received_by" value="<?= $username ?>" readonly style="background-color: #dcdcdc; opacity: 1; border-color: #dcdcdc;">
                    </div>
                    <div class="col-md-6 mb-3">
    <label for="payment_method" class="form-label">Payment Method <span style="color:red;">*</span></label>
    <select class="form-control" id="payment_method" name="payment_method" required>
        <option value="">Select Payment Method</option>
        <option value="bank">Bank</option>
        <option value="cheque">Cheque</option>
        <option value="cash">Cash</option>
    </select>
</div>
                    <div class="col-md-6 mb-3">
                        <label for="bank_name" class="form-label">Bank Name</label>
                        <select class="form-control" id="bank_name" name="bank_name">
                            <option value="">Select Bank</option>
                            <?php foreach ($banks as $bank): ?>
                                <option value="<?= htmlspecialchars($bank['id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($bank['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="transaction_no" class="form-label">Transaction No</label>
                        <input type="text" class="form-control" id="transaction_no" name="transaction_no">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="cheque_ref_no" class="form-label">Cheque Ref No</label>
                        <input type="text" class="form-control" id="cheque_ref_no" name="cheque_ref_no">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="payment_date" class="form-label">Payment Date <span style="color:red;">*</span></label>
                        <input type="date" class="form-control" id="payment_date" name="payment_date" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="deposited_amount" class="form-label">Deposited Amount</label>
                        <input type="number" step="0.01" class="form-control" id="deposited_amount" name="deposited_amount">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="sub_total" class="form-label">Sub Total <span style="color:red;">*</span></label>
                        <input type="number" step="0.01" class="form-control" id="sub_totals" name="sub_total" required readonly style="background-color: #dcdcdc; opacity: 1; border-color: #dcdcdc;">
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Description Section -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white p-2">Payment Description</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="withheldCheckbox" name="withheldCheckbox">
                            <label class="form-check-label" for="withheldCheckbox">Withhold</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="discountedCheckbox" name="discountedCheckbox">
                            <label class="form-check-label" for="discountedCheckbox">Discounted</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="penalityCheckbox" name="penalityCheckbox">
                            <label class="form-check-label" for="penalityCheckbox">Penality</label>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="discount" class="form-label">Discount</label>
                        <input type="number" step="0.01" class="form-control readonly-input" id="discount" name="discount" disabled>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="sub_total" class="form-label">Sub Total <span style="color:red;">*</span></label>
                        <input type="number" step="0.01" class="form-control readonly-input" id="sub_total" name="sub_total" required readonly style="background-color: #dcdcdc; opacity: 1; border-color: #dcdcdc;">
                    </div>
                   
                    <div class="col-md-6 mb-3">
                        <label for="withhold" class="form-label">Withhold</label>
                        <input type="number" step="0.01" class="form-control readonly-input" id="withhold" name="withhold" disabled>
                    </div>
                   
                    <div class="col-md-6 mb-3">
                        <label for="vat" class="form-label">VAT</label>
                        <input type="number" step="0.01" class="form-control" id="vat" name="vat" readonly style="background-color: #dcdcdc; opacity: 1; border-color: #dcdcdc;">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="penality" class="form-label">Penality</label>
                        <input type="number" step="0.01" class="form-control readonly-input" id="penality" name="penality" disabled>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="total" class="form-label">Total <span style="color:red;">*</span></label>
                        <input type="number" step="0.01" class="form-control" id="total" name="total" required readonly style="background-color: #dcdcdc; opacity: 1; border-color: #dcdcdc;">
                    </div>
                </div>
            </div>
        </div>

<!-- Additional Fee Section -->
<div class="card mb-3">
    <div class="card-header bg-primary text-white p-2">Additional Fee</div>
    <div class="card-body">
        <div class="d-flex justify-content-between mb-3">
            <button type="button" class="btn btn-secondary" id="inlineAdd">Inline Add</button>
            <!-- <button type="button" class="btn btn-danger" id="cancelAdd">Cancel</button> -->
        </div>
        <table class="table table-bordered" id="additionalFeeTable">
            <thead>
                <tr>
                    <th>Remove</th>
                    <th>Title</th>
                    <th>Price</th>
                    <th>Description</th>
                    <th>Registration Date</th>
                </tr>
            </thead>
            <tbody>
                <!-- Default row -->
                <tr>
                    <td><button type="button" class="btn btn-danger btn-sm removeRow">X</button></td>
                    <td><input type="text" class="form-control" name="additional_fee_title[]"></td>
                    <td><input type="number" step="0.01" class="form-control" name="additional_fee_price[]"></td>
                    <td><input type="text" class="form-control" name="additional_fee_description[]"></td>
                    <td><input type="date" class="form-control" name="additional_fee_registration_date[]"></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

        <!-- Form Buttons -->
        <div >
            <button type="submit" class="btn btn-primary">Add Payment</button>
            <a href="payment.php" class="btn btn-secondary">Back to Payments List</a>
        </div>
    </form>
<?php endif; ?>

<!-- Add Tenant Modal -->
<div class="modal fade" id="addTenantModal" tabindex="-1" aria-labelledby="addTenantModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTenantModalLabel">Add New Tenant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addTenantForm">
                    <ul class="nav nav-tabs" id="tenantTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="about-tenant-tab" data-bs-toggle="tab" data-bs-target="#about-tenant" type="button" role="tab" aria-controls="about-tenant" aria-selected="true">About Tenant</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <!-- Disabled About Contract Tab -->
                            <button class="nav-link disabled" id="about-contract-tab" data-bs-toggle="tab" data-bs-target="#about-contract" type="button" role="tab" aria-controls="about-contract" aria-selected="false" tabindex="-1" aria-disabled="true">About Contract</button>
                        </li>
                    </ul>
                    <div class="tab-content" id="tenantTabContent">
                        <div class="tab-pane fade show active" id="about-tenant" role="tabpanel" aria-labelledby="about-tenant-tab">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="full_name" class="form-label">Full Name <span style="color:red;">*</span></label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="company_name" class="form-label">Company Name</label>
                                    <input type="text" class="form-control" id="company_name" name="company_name">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="tin_no" class="form-label">Tin No</label>
                                    <input type="text" class="form-control" id="tin_no" name="tin_no">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="mobile_no" class="form-label">Mobile No <span style="color:red;">*</span></label>
                                    <input type="text" class="form-control" id="mobile_no" name="mobile_no" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="address" name="address">
                                </div>
                            </div>
                            <!-- Next Button -->
                            <button type="button" class="btn btn-primary" id="nextToAboutContract" disabled>
                                Next <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                        <div class="tab-pane fade" id="about-contract" role="tabpanel" aria-labelledby="about-contract-tab">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="building" class="form-label">Building <span style="color:red;">*</span></label>
                                    <select class="form-control" id="building" name="building" required>
                                        <option value="">Select Building</option>
                                        <?php foreach ($buildings as $building): ?>
                                            <option value="<?= htmlspecialchars($building['building_id']) ?>"><?= htmlspecialchars($building['building_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="floor" class="form-label">Floor <span style="color:red;">*</span></label>
                                    <select class="form-control" id="floor" name="floor" required>
                                        <option value="">Select Floor</option>
                                        <?php foreach ($floors as $floor): ?>
                                            <option value="<?= htmlspecialchars($floor['floor_id']) ?>"><?= htmlspecialchars($floor['number']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                              
                                
                                <div class="col-md-4 mb-3">
                                <label for="room" class="form-label">Room <span style="color:red;">*</span></label>
                                <select class="form-control" id="room" name="room" required>
                                <option value="">Select Room</option>
                                <?php foreach ($rooms as $room): ?>
                                            <option value="<?= htmlspecialchars($room['room_no']) ?>"><?= htmlspecialchars($room['room_no']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="rent_amount" class="form-label">Rent Amount (ETB) <span style="color:red;">*</span></label>
                                    <input type="number" class="form-control" id="rent_amount" name="rent_amount" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="contract_duration_month" class="form-label">Contract Duration (Months) <span style="color:red;">*</span></label>
                                    <select class="form-control" id="contract_duration_month" name="contract_duration_month" required>
                                        <option value="">Select Duration</option>
                                        <option value="6">6 Months</option>
                                        <option value="12">12 Months</option>
                                        <option value="24">24 Months</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="rent_due_date" class="form-label">Rent Due Date <span style="color:red;">*</span></label>
                                    <select class="form-control" id="rent_due_date" name="rent_due_date" required>

<option value="">Select Due Date</option>

<?php for ($i = 1; $i <= 30; $i++): ?>

    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>

<?php endfor; ?>
</select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="contract_period_starts" class="form-label">Contract Period Starts <span style="color:red;">*</span></label>
                                    <input type="date" class="form-control" id="contract_period_starts" name="contract_period_starts" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="term_of_payment" class="form-label">Term Of Payment (Months) <span style="color:red;">*</span></label>
                                    <input type="number" class="form-control" id="term_of_payment" name="term_of_payment" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="initial_deposit" class="form-label">Initial Deposit <span style="color:red;">*</span></label>
                                    <input type="number" class="form-control" id="initial_deposit" name="initial_deposit" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="contract_date_in_ethiopian_calendar" class="form-label">Contract Date In Ethiopian Calendar <span style="color:red;">*</span></label>
                                    <input type="text" class="form-control" id="contract_date_in_ethiopian_calendar" name="contract_date_in_ethiopian_calendar" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="status" class="form-label">Status <span style="color:red;">*</span></label>
                                    <select class="form-control" id="status" name="status" required>
                                        <option value="">Select Status</option>
                                        <option value="Moved In">Moved In</option>
                                        <option value="Moved Out">Moved Out</option>
                                    </select>
                                </div>
                            </div>
                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary">Add Tenant</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Font Awesome for Icons -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>


   <!-- Edit Payment Form -->
   <?php if ($action === 'edit' && $payment_id > 0 && $editing_payment): ?>
    <h2 class="mb-3">Edit Payment</h2>
    <!-- Edit Payment Form -->
    <form id="editPaymentForm" action="edit_payment.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="payment_id" value="<?= htmlspecialchars($editing_payment['id'], ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <!-- Payment Information Section -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white p-2">Payment Information</div>
            <div class="card-body">
                <div class="row">
                    <!-- Tenant Selection -->
                    <div class="col-md-6 mb-3">
                        <label for="edit_tenant" class="form-label">Tenant Name <span style="color:red;">*</span></label>
                        <div class="input-group">
                            <select class="form-control" id="edit_tenant" name="tenant_id" required>
                                <option value="">Select Tenant</option>
                                <?php foreach ($tenants as $tenant): ?>
                                    <option value="<?= htmlspecialchars($tenant['tenant_id'], ENT_QUOTES, 'UTF-8') ?>" <?= $tenant['tenant_id'] == $editing_payment['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tenant['full_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <a href="#" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addTenantModal">Add New</a>
                        </div>
                    </div>

                    <!-- Tenant Tin No -->
                    <div class="col-md-6 mb-3">
                        <label for="edit_tenant_tin_no" class="form-label">Tenant Tin No</label>
                        <input type="text" class="form-control" id="edit_tenant_tin_no" name="tenant_tin" value="<?= htmlspecialchars($editing_payment['tenant_tin'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <!-- Room -->
                    <div class="col-md-6 mb-3">
                        <label for="edit_room" class="form-label">Room <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="edit_room" name="room" value="<?= htmlspecialchars($editing_payment['room'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <!-- Fs Number -->
                    <div class="col-md-6 mb-3">
                        <label for="edit_fs_number" class="form-label">Fs Number</label>
                        <input type="text" class="form-control" id="edit_fs_number" name="fs_number" value="<?= htmlspecialchars($editing_payment['fs_number'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <!-- Rent Due Date -->
                    <div class="col-md-6 mb-3">
                        <label for="edit_rent_due_date" class="form-label">Rent Due Date</label>
                        <input type="number" class="form-control" id="edit_rent_due_date" name="rent_due_date" value="<?= htmlspecialchars($editing_payment['rent_due_date'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <!-- Received By -->
                    <div class="col-md-6 mb-3">
                        <label for="edit_received_by" class="form-label">Received By</label>
                        <input type="text" class="form-control" id="edit_received_by" name="received_by" value="<?= htmlspecialchars($editing_payment['received_by'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <!-- Payment Method -->
                    <div class="col-md-6 mb-3">
                        <label for="edit_payment_method" class="form-label">Payment Method <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="edit_payment_method" name="payment_method" value="<?= htmlspecialchars($editing_payment['payment_method'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <!-- Bank Name -->
                    <div class="col-md-6 mb-3">
                        <label for="edit_bank_name" class="form-label">Bank Name</label>
                        <input type="text" class="form-control" id="edit_bank_name" name="bank_name" value="<?= htmlspecialchars($editing_payment['bank_name'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <!-- Transaction Number -->
                    <div class="col-md-6 mb-3">
                        <label for="edit_transaction_no" class="form-label">Transaction No.</label>
                        <input type="text" class="form-control" id="edit_transaction_no" name="transaction_no" value="<?= htmlspecialchars($editing_payment['transaction_no'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <!-- Payment Date -->
                    <div class="col-md-6 mb-3">
                        <label for="edit_payment_date" class="form-label">Payment Date</label>
                        <input type="date" class="form-control" id="edit_payment_date" name="payment_date" value="<?= htmlspecialchars($editing_payment['payment_date'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <!-- Deposited Amount -->
                    <div class="col-md-6 mb-3">
                        <label for="edit_deposited_amount" class="form-label">Deposited Amount</label>
                        <input type="number" step="0.01" class="form-control" id="edit_deposited_amount" name="deposited_amount" value="<?= htmlspecialchars($editing_payment['deposited_amount'] ?? "", ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <!-- Total -->
                    <div class="col-md-6 mb-3">
                        <label for="edit_total" class="form-label">Total</label>
                        <input type="number" step="0.01" class="form-control" id="edit_total" name="total" value="<?= htmlspecialchars($editing_payment['total'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <!-- Withhold -->
                    <div class="col-md-6 mb-3">
                        <label for="edit_withhold" class="form-label">Withhold</label>
                        <input type="number" step="0.01" class="form-control" id="edit_withhold" name="withhold" value="<?= htmlspecialchars($editing_payment['withhold'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <!-- Discount -->
                    <div class="col-md-6 mb-3">
                        <label for="edit_discount" class="form-label">Discount</label>
                        <input type="number" step="0.01" class="form-control" id="edit_discount" name="discount" value="<?= htmlspecialchars($editing_payment['discount'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <!-- Penality -->
                    <div class="col-md-6 mb-3">
                        <label for="edit_penality" class="form-label">Penality</label>
                        <input type="number" step="0.01" class="form-control" id="edit_penality" name="penality" value="<?= htmlspecialchars($editing_payment['penality'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Fees Section -->
        <div class="card mb-3">
            <div class="card-header bg-secondary text-white p-2">Additional Fees</div>
            <div class="card-body">
                <table class="table table-bordered" id="additionalFeeTable">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Title</th>
                            <th>Price</th>
                            <th>Description</th>
                            <th>Registration Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($additional_fees)): ?>
                            <?php foreach ($additional_fees as $fee): ?>
                                <tr>
                                    <td><button type="button" class="btn btn-danger btn-sm removeRow">X</button></td>
                                    <td><input type="text" class="form-control" name="additional_fee_title[]" value="<?= htmlspecialchars($fee['title'], ENT_QUOTES, 'UTF-8') ?>"></td>
                                    <td><input type="number" step="0.01" class="form-control" name="additional_fee_price[]" value="<?= htmlspecialchars($fee['price'], ENT_QUOTES, 'UTF-8') ?>"></td>
                                    <td><input type="text" class="form-control" name="additional_fee_description[]" value="<?= htmlspecialchars($fee['description'], ENT_QUOTES, 'UTF-8') ?>"></td>
                                    <td><input type="date" class="form-control" name="additional_fee_registration_date[]" value="<?= htmlspecialchars($fee['registration_date'], ENT_QUOTES, 'UTF-8') ?>"></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No additional fees added.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </form>
<?php endif; ?>
     
         
         
      </div>
      <div class="offcanvas offcanvas-bottom share-offcanvas" tabindex="-1" id="share-btn" aria-labelledby="shareBottomLabel">
         <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="shareBottomLabel">Share</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
         </div>
         <div class="offcanvas-body small">
            <div class="d-flex flex-wrap align-items-center">
               <div class="text-center me-3 mb-3">
                  <img src="../../assets/images/brands/08.png" class="img-fluid rounded mb-2" alt="">
                  <h6>Facebook</h6>
               </div>
               <div class="text-center me-3 mb-3">
                  <img src="../../assets/images/brands/09.png" class="img-fluid rounded mb-2" alt="">
                  <h6>Twitter</h6>
               </div>
               <div class="text-center me-3 mb-3">
                  <img src="../../assets/images/brands/10.png" class="img-fluid rounded mb-2" alt="">
                  <h6>Instagram</h6>
               </div>
               <div class="text-center me-3 mb-3">
                  <img src="../../assets/images/brands/11.png" class="img-fluid rounded mb-2" alt="">
                  <h6>Google Plus</h6>
               </div>
               <div class="text-center me-3 mb-3">
                  <img src="../../assets/images/brands/13.png" class="img-fluid rounded mb-2" alt="">
                  <h6>In</h6>
               </div>
               <div class="text-center me-3 mb-3">
                  <img src="../../assets/images/brands/12.png" class="img-fluid rounded mb-2" alt="">
                  <h6>YouTube</h6>
               </div>
            </div>
         </div>
      </div>      </div>
      
      <!-- Footer Section Start -->
      <footer class="footer">
          <!-- <div class="footer-body">
              <ul class="left-panel list-inline mb-0 p-0">
                  <li class="list-inline-item"><a href="../../dashboard/extra/privacy-policy.html">Privacy Policy</a></li>
                  <li class="list-inline-item"><a href="../../dashboard/extra/terms-of-service.html">Terms of Use</a></li>
              </ul>
              <div class="right-panel">
                  ©<script>document.write(new Date().getFullYear())</script> Nest-Netth
                  <span class="">
                      <svg class="icon-15" width="15" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <path fill-rule="evenodd" clip-rule="evenodd" d="M15.85 2.50065C16.481 2.50065 17.111 2.58965 17.71 2.79065C21.401 3.99065 22.731 8.04065 21.62 11.5806C20.99 13.3896 19.96 15.0406 18.611 16.3896C16.68 18.2596 14.561 19.9196 12.28 21.3496L12.03 21.5006L11.77 21.3396C9.48102 19.9196 7.35002 18.2596 5.40102 16.3796C4.06102 15.0306 3.03002 13.3896 2.39002 11.5806C1.26002 8.04065 2.59002 3.99065 6.32102 2.76965C6.61102 2.66965 6.91002 2.59965 7.21002 2.56065H7.33002C7.61102 2.51965 7.89002 2.50065 8.17002 2.50065H8.28002C8.91002 2.51965 9.52002 2.62965 10.111 2.83065H10.17C10.21 2.84965 10.24 2.87065 10.26 2.88965C10.481 2.96065 10.69 3.04065 10.89 3.15065L11.27 3.32065C11.3618 3.36962 11.4649 3.44445 11.554 3.50912C11.6104 3.55009 11.6612 3.58699 11.7 3.61065C11.7163 3.62028 11.7329 3.62996 11.7496 3.63972C11.8354 3.68977 11.9247 3.74191 12 3.79965C13.111 2.95065 14.46 2.49065 15.85 2.50065ZM18.51 9.70065C18.92 9.68965 19.27 9.36065 19.3 8.93965V8.82065C19.33 7.41965 18.481 6.15065 17.19 5.66065C16.78 5.51965 16.33 5.74065 16.18 6.16065C16.04 6.58065 16.26 7.04065 16.68 7.18965C17.321 7.42965 17.75 8.06065 17.75 8.75965V8.79065C17.731 9.01965 17.8 9.24065 17.94 9.41065C18.08 9.58065 18.29 9.67965 18.51 9.70065Z" fill="currentColor"></path>
                      </svg>
                  </span> by <a href="https://iqonic.design/">IQONIC Design</a>.
              </div>
          </div> -->
      </footer>
      <!-- Footer Section End -->    </main>
    <a class="btn btn-fixed-end btn-warning btn-icon btn-setting" data-bs-toggle="offcanvas" data-bs-target="#offcanvasExample" role="button" aria-controls="offcanvasExample">
      <svg width="24" viewBox="0 0 24 24" class="animated-rotate icon-24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path fill-rule="evenodd" clip-rule="evenodd" d="M20.8064 7.62361L20.184 6.54352C19.6574 5.6296 18.4905 5.31432 17.5753 5.83872V5.83872C17.1397 6.09534 16.6198 6.16815 16.1305 6.04109C15.6411 5.91402 15.2224 5.59752 14.9666 5.16137C14.8021 4.88415 14.7137 4.56839 14.7103 4.24604V4.24604C14.7251 3.72922 14.5302 3.2284 14.1698 2.85767C13.8094 2.48694 13.3143 2.27786 12.7973 2.27808H11.5433C11.0367 2.27807 10.5511 2.47991 10.1938 2.83895C9.83644 3.19798 9.63693 3.68459 9.63937 4.19112V4.19112C9.62435 5.23693 8.77224 6.07681 7.72632 6.0767C7.40397 6.07336 7.08821 5.98494 6.81099 5.82041V5.82041C5.89582 5.29601 4.72887 5.61129 4.20229 6.52522L3.5341 7.62361C3.00817 8.53639 3.31916 9.70261 4.22975 10.2323V10.2323C4.82166 10.574 5.18629 11.2056 5.18629 11.8891C5.18629 12.5725 4.82166 13.2041 4.22975 13.5458V13.5458C3.32031 14.0719 3.00898 15.2353 3.5341 16.1454V16.1454L4.16568 17.2346C4.4124 17.6798 4.82636 18.0083 5.31595 18.1474C5.80554 18.2866 6.3304 18.2249 6.77438 17.976V17.976C7.21084 17.7213 7.73094 17.6516 8.2191 17.7822C8.70725 17.9128 9.12299 18.233 9.37392 18.6717C9.53845 18.9489 9.62686 19.2646 9.63021 19.587V19.587C9.63021 20.6435 10.4867 21.5 11.5433 21.5H12.7973C13.8502 21.5001 14.7053 20.6491 14.7103 19.5962V19.5962C14.7079 19.088 14.9086 18.6 15.2679 18.2407C15.6272 17.8814 16.1152 17.6807 16.6233 17.6831C16.9449 17.6917 17.2594 17.7798 17.5387 17.9394V17.9394C18.4515 18.4653 19.6177 18.1544 20.1474 17.2438V17.2438L20.8064 16.1454C21.0615 15.7075 21.1315 15.186 21.001 14.6964C20.8704 14.2067 20.55 13.7894 20.1108 13.5367V13.5367C19.6715 13.284 19.3511 12.8666 19.2206 12.3769C19.09 11.8873 19.16 11.3658 19.4151 10.928C19.581 10.6383 19.8211 10.3982 20.1108 10.2323V10.2323C21.0159 9.70289 21.3262 8.54349 20.8064 7.63277V7.63277V7.62361Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
          <circle cx="12.1747" cy="11.8891" r="2.63616" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></circle>
      </svg>
    </a>
     
    <!-- Wrapper End-->
    <!-- offcanvas start -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasExample" data-bs-scroll="true" data-bs-backdrop="true"
      aria-labelledby="offcanvasExampleLabel">
      <div class="offcanvas-header">
        <div class="d-flex align-items-center">
          <h3 class="offcanvas-title me-3" id="offcanvasExampleLabel">Settings</h3>
        </div>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body data-scrollbar">
        <div class="row">
          <div class="col-lg-12">
            <h5 class="mb-3">Scheme</h5>
            <div class="d-grid gap-3 grid-cols-3 mb-4">
              <div class="btn btn-border" data-setting="color-mode" data-name="color" data-value="auto">
                <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path fill="currentColor" d="M7,2V13H10V22L17,10H13L17,2H7Z" />
                </svg>
                <span class="ms-2 "> Auto </span>
              </div>
    
              <div class="btn btn-border" data-setting="color-mode" data-name="color" data-value="dark">
                <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path fill="currentColor"
                    d="M9,2C7.95,2 6.95,2.16 6,2.46C10.06,3.73 13,7.5 13,12C13,16.5 10.06,20.27 6,21.54C6.95,21.84 7.95,22 9,22A10,10 0 0,0 19,12A10,10 0 0,0 9,2Z" />
                </svg>
                <span class="ms-2 "> Dark </span>
              </div>
              <div class="btn btn-border active" data-setting="color-mode" data-name="color" data-value="light">
                <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path fill="currentColor"
                    d="M12,8A4,4 0 0,0 8,12A4,4 0 0,0 12,16A4,4 0 0,0 16,12A4,4 0 0,0 12,8M12,18A6,6 0 0,1 6,12A6,6 0 0,1 12,6A6,6 0 0,1 18,12A6,6 0 0,1 12,18M20,8.69V4H15.31L12,0.69L8.69,4H4V8.69L0.69,12L4,15.31V20H8.69L12,23.31L15.31,20H20V15.31L23.31,12L20,8.69Z" />
                </svg>
                <span class="ms-2 "> Light</span>
              </div>
            </div>
            <hr class="hr-horizontal">
            <div class="d-flex align-items-center justify-content-between">
              <h5 class="mt-4 mb-3">Color Customizer</h5>
              <button class="btn btn-transparent p-0 border-0" data-value="theme-color-default" data-info="#001F4D"
                data-setting="color-mode1" data-name="color" data-bs-toggle="tooltip" data-bs-placement="top" title=""
                data-bs-original-title="Default">
                <svg class="icon-18" width="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path
                    d="M21.4799 12.2424C21.7557 12.2326 21.9886 12.4482 21.9852 12.7241C21.9595 14.8075 21.2975 16.8392 20.0799 18.5506C18.7652 20.3986 16.8748 21.7718 14.6964 22.4612C12.518 23.1505 10.1711 23.1183 8.01299 22.3694C5.85488 21.6205 4.00382 20.196 2.74167 18.3126C1.47952 16.4293 0.875433 14.1905 1.02139 11.937C1.16734 9.68346 2.05534 7.53876 3.55018 5.82945C5.04501 4.12014 7.06478 2.93987 9.30193 2.46835C11.5391 1.99683 13.8711 2.2599 15.9428 3.2175L16.7558 1.91838C16.9822 1.55679 17.5282 1.62643 17.6565 2.03324L18.8635 5.85986C18.945 6.11851 18.8055 6.39505 18.549 6.48314L14.6564 7.82007C14.2314 7.96603 13.8445 7.52091 14.0483 7.12042L14.6828 5.87345C13.1977 5.18699 11.526 4.9984 9.92231 5.33642C8.31859 5.67443 6.8707 6.52052 5.79911 7.74586C4.72753 8.97119 4.09095 10.5086 3.98633 12.1241C3.8817 13.7395 4.31474 15.3445 5.21953 16.6945C6.12431 18.0446 7.45126 19.0658 8.99832 19.6027C10.5454 20.1395 12.2278 20.1626 13.7894 19.6684C15.351 19.1743 16.7062 18.1899 17.6486 16.8652C18.4937 15.6773 18.9654 14.2742 19.0113 12.8307C19.0201 12.5545 19.2341 12.3223 19.5103 12.3125L21.4799 12.2424Z"
                    fill="#31BAF1" />
                  <path
                    d="M20.0941 18.5594C21.3117 16.848 21.9736 14.8163 21.9993 12.7329C22.0027 12.4569 21.7699 12.2413 21.4941 12.2512L19.5244 12.3213C19.2482 12.3311 19.0342 12.5633 19.0254 12.8395C18.9796 14.283 18.5078 15.6861 17.6628 16.8739C16.7203 18.1986 15.3651 19.183 13.8035 19.6772C12.2419 20.1714 10.5595 20.1483 9.01246 19.6114C7.4654 19.0746 6.13845 18.0534 5.23367 16.7033C4.66562 15.8557 4.28352 14.9076 4.10367 13.9196C4.00935 18.0934 6.49194 21.37 10.008 22.6416C10.697 22.8908 11.4336 22.9852 12.1652 22.9465C13.075 22.8983 13.8508 22.742 14.7105 22.4699C16.8889 21.7805 18.7794 20.4073 20.0941 18.5594Z"
                    fill="#0169CA" />
                </svg>
              </button>
            </div>
            <div class="grid-cols-5 mb-4 d-grid gap-x-2">
              <div class="btn btn-border bg-transparent" data-value="theme-color-blue" data-info="#573BFF"
                data-setting="color-mode1" data-name="color" data-bs-toggle="tooltip" data-bs-placement="top" title=""
                data-bs-original-title="Theme-1">
                <svg class="customizer-btn icon-32" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32">
                  <circle cx="12" cy="12" r="10" fill="#00C3F9" />
                  <path d="M2,12 a1,1 1 1,0 20,0" fill="#573BFF" />
                </svg>
              </div>
              <div class="btn btn-border bg-transparent" data-value="theme-color-gray" data-info="#FD8D00"
                data-setting="color-mode1" data-name="color" data-bs-toggle="tooltip" data-bs-placement="top" title=""
                data-bs-original-title="Theme-2">
                <svg class="customizer-btn icon-32" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32">
                  <circle cx="12" cy="12" r="10" fill="#91969E" />
                  <path d="M2,12 a1,1 1 1,0 20,0" fill="#FD8D00" />
                </svg>
              </div>
              <div class="btn btn-border bg-transparent" data-value="theme-color-red" data-info="#366AF0"
                data-setting="color-mode1" data-name="color" data-bs-toggle="tooltip" data-bs-placement="top" title=""
                data-bs-original-title="Theme-3">
                <svg class="customizer-btn icon-32" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32">
                  <circle cx="12" cy="12" r="10" fill="#DB5363" />
                  <path d="M2,12 a1,1 1 1,0 20,0" fill="#366AF0" />
                </svg>
              </div>
              <div class="btn btn-border bg-transparent" data-value="theme-color-yellow" data-info="#6410F1"
                data-setting="color-mode1" data-name="color" data-bs-toggle="tooltip" data-bs-placement="top" title=""
                data-bs-original-title="Theme-4">
                <svg class="customizer-btn icon-32" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32">
                  <circle cx="12" cy="12" r="10" fill="#EA6A12" />
                  <path d="M2,12 a1,1 1 1,0 20,0" fill="#6410F1" />
                </svg>
              </div>
              <div class="btn btn-border bg-transparent" data-value="theme-color-pink" data-info="#25C799"
                data-setting="color-mode1" data-name="color" data-bs-toggle="tooltip" data-bs-placement="top" title=""
                data-bs-original-title="Theme-5">
                <svg class="customizer-btn icon-32" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32">
                  <circle cx="12" cy="12" r="10" fill="#E586B3" />
                  <path d="M2,12 a1,1 1 1,0 20,0" fill="#25C799" />
                </svg>
              </div>
            </div>
            <hr class="hr-horizontal">
            <h5 class="mb-3 mt-4">Scheme Direction</h5>
            <div class="d-grid gap-3 grid-cols-2 mb-4">
              <div class="text-center">
                <img src="../../assets/images/settings/dark/01.png" alt="ltr"
                  class="mode dark-img img-fluid btn-border p-0 flex-column active mb-2" data-setting="dir-mode"
                  data-name="dir" data-value="ltr">
                <img src="../../assets/images/settings/light/01.png" alt="ltr"
                  class="mode light-img img-fluid btn-border p-0 flex-column active mb-2" data-setting="dir-mode"
                  data-name="dir" data-value="ltr">
                <span class=" mt-2"> LTR </span>
              </div>
              <div class="text-center">
                <img src="../../assets/images/settings/dark/02.png" alt=""
                  class="mode dark-img img-fluid btn-border p-0 flex-column mb-2" data-setting="dir-mode" data-name="dir"
                  data-value="rtl">
                <img src="../../assets/images/settings/light/02.png" alt=""
                  class="mode light-img img-fluid btn-border p-0 flex-column mb-2" data-setting="dir-mode" data-name="dir"
                  data-value="rtl">
                <span class="mt-2 "> RTL </span>
              </div>
            </div>
            <hr class="hr-horizontal">
            <h5 class="mt-4 mb-3">Sidebar Color</h5>
            <div class="d-grid gap-3 grid-cols-2 mb-4">
              <div class="btn btn-border d-block" data-setting="sidebar" data-name="sidebar-color"
                data-value="sidebar-white">
                <span class=""> Default </span>
              </div>
              <div class="btn btn-border d-block" data-setting="sidebar" data-name="sidebar-color"
                data-value="sidebar-dark">
                <span class=""> Dark </span>
              </div>
              <div class="btn btn-border d-block" data-setting="sidebar" data-name="sidebar-color"
                data-value="sidebar-color">
                <span class=""> Color </span>
              </div>
    
              <div class="btn btn-border d-block" data-setting="sidebar" data-name="sidebar-color"
                data-value="sidebar-transparent">
                <span class=""> Transparent </span>
              </div>
            </div>
            <hr class="hr-horizontal">
            <h5 class="mt-4 mb-3">Sidebar Types</h5>
            <div class="d-grid gap-3 grid-cols-3 mb-4">
              <div class="text-center">
                <img src="../../assets/images/settings/dark/03.png" alt="mini"
                  class="mode dark-img img-fluid btn-border p-0 flex-column mb-2" data-setting="sidebar"
                  data-name="sidebar-type" data-value="sidebar-mini">
                <img src="../../assets/images/settings/light/03.png" alt="mini"
                  class="mode light-img img-fluid btn-border p-0 flex-column mb-2" data-setting="sidebar"
                  data-name="sidebar-type" data-value="sidebar-mini">
                <span class="mt-2">Mini</span>
              </div>
              <div class="text-center">
                <img src="../../assets/images/settings/dark/04.png" alt="hover"
                  class="mode dark-img img-fluid btn-border p-0 flex-column mb-2" data-setting="sidebar"
                  data-name="sidebar-type" data-value="sidebar-hover" data-extra-value="sidebar-mini">
                <img src="../../assets/images/settings/light/04.png" alt="hover"
                  class="mode light-img img-fluid btn-border p-0 flex-column mb-2" data-setting="sidebar"
                  data-name="sidebar-type" data-value="sidebar-hover" data-extra-value="sidebar-mini">
                <span class="mt-2">Hover</span>
              </div>
              <div class="text-center">
                <img src="../../assets/images/settings/dark/05.png" alt="boxed"
                  class="mode dark-img img-fluid btn-border p-0 flex-column mb-2" data-setting="sidebar"
                  data-name="sidebar-type" data-value="sidebar-boxed">
                <img src="../../assets/images/settings/light/05.png" alt="boxed"
                  class="mode light-img img-fluid btn-border p-0 flex-column mb-2" data-setting="sidebar"
                  data-name="sidebar-type" data-value="sidebar-boxed">
                <span class="mt-2">Boxed</span>
              </div>
            </div>
            <hr class="hr-horizontal">
            <h5 class="mt-4 mb-3">Sidebar Active Style</h5>
            <div class="d-grid gap-3 grid-cols-2 mb-4">
              <div class="text-center">
                <img src="../../assets/images/settings/dark/06.png" alt="rounded-one-side"
                  class="mode dark-img img-fluid btn-border p-0 flex-column mb-2" data-setting="sidebar"
                  data-name="sidebar-item" data-value="navs-rounded">
                <img src="../../assets/images/settings/light/06.png" alt="rounded-one-side"
                  class="mode light-img img-fluid btn-border p-0 flex-column mb-2" data-setting="sidebar"
                  data-name="sidebar-item" data-value="navs-rounded">
                <span class="mt-2">Rounded One Side</span>
              </div>
              <div class="text-center">
                <img src="../../assets/images/settings/dark/07.png" alt="rounded-all"
                  class="mode dark-img img-fluid btn-border p-0 flex-column active mb-2" data-setting="sidebar"
                  data-name="sidebar-item" data-value="navs-rounded-all">
                <img src="../../assets/images/settings/light/07.png" alt="rounded-all"
                  class="mode light-img img-fluid btn-border p-0 flex-column active mb-2" data-setting="sidebar"
                  data-name="sidebar-item" data-value="navs-rounded-all">
                <span class="mt-2">Rounded All</span>
              </div>
              <div class="text-center">
                <img src="../../assets/images/settings/dark/08.png" alt="pill-one-side"
                  class="mode dark-img img-fluid btn-border p-0 flex-column mb-2" data-setting="sidebar"
                  data-name="sidebar-item" data-value="navs-pill">
                <img src="../../assets/images/settings/light/09.png" alt="pill-one-side"
                  class="mode light-img img-fluid btn-border p-0 flex-column mb-2" data-setting="sidebar"
                  data-name="sidebar-item" data-value="navs-pill">
                <span class="mt-2">Pill One Side</span>
              </div>
              <div class="text-center">
                <img src="../../assets/images/settings/dark/09.png" alt="pill-all"
                  class="mode dark-img img-fluid btn-border p-0 flex-column" data-setting="sidebar" data-name="sidebar-item"
                  data-value="navs-pill-all">
                <img src="../../assets/images/settings/light/08.png" alt="pill-all"
                  class="mode light-img img-fluid btn-border p-0 flex-column mb-2" data-setting="sidebar"
                  data-name="sidebar-item" data-value="navs-pill-all">
                <span class="mt-2">Pill All</span>
              </div>
            </div>
            <hr class="hr-horizontal">
            <h5 class="mt-4 mb-3">Navbar Style</h5>
            <div class="d-grid gap-3 grid-cols-2 ">
              <div class=" text-center">
                <img src="../../assets/images/settings/dark/11.png" alt="image"
                  class="mode dark-img img-fluid btn-border p-0 flex-column mb-2" data-setting="navbar"
                  data-target=".iq-navbar" data-name="navbar-type" data-value="nav-glass">
                <img src="../../assets/images/settings/light/10.png" alt="image"
                  class="mode light-img img-fluid btn-border p-0 flex-column mb-2" data-setting="navbar"
                  data-target=".iq-navbar" data-name="navbar-type" data-value="nav-glass">
                <span class="mt-2">Glass</span>
              </div>
              <div class=" text-center">
                <img src="../../assets/images/settings/dark/12.png" alt="sticky"
                  class="mode dark-img img-fluid btn-border p-0 flex-column mb-2" data-setting="navbar"
                  data-target=".iq-navbar" data-name="navbar-type" data-value="navs-sticky">
                <img src="../../assets/images/settings/light/12.png" alt="sticky"
                  class="mode light-img img-fluid btn-border p-0 flex-column mb-2" data-setting="navbar"
                  data-target=".iq-navbar" data-name="navbar-type" data-value="navs-sticky">
                <span class="mt-2">Sticky</span>
              </div>
              <div class="text-center">
                <img src="../../assets/images/settings/dark/13.png" alt="transparent"
                  class="mode dark-img img-fluid btn-border p-0 flex-column mb-2" data-setting="navbar"
                  data-target=".iq-navbar" data-name="navbar-type" data-value="navs-transparent">
                <img src="../../assets/images/settings/light/13.png" alt="transparent"
                  class="mode light-img img-fluid btn-border p-0 flex-column mb-2" data-setting="navbar"
                  data-target=".iq-navbar" data-name="navbar-type" data-value="navs-transparent">
                <span class="mt-2">Transparent</span>
              </div>
              <div class="text-center">
                <img src="../../assets/images/settings/dark/10.png" alt="color"
                  class="mode dark-img img-fluid btn-border p-0 flex-column mb-2" data-setting="navbar"
                  data-target=".iq-navbar" data-name="navbar-type" data-value="default">
                <img src="../../assets/images/settings/light/01.png" alt="color"
                  class="mode light-img img-fluid btn-border p-0 flex-column mb-2" data-setting="navbar"
                  data-name="navbar-default" data-value="default">
                <span class="mt-2">Default</span>
              </div>
              <div class="btn btn-border active col-span-full mt-4 d-block" data-setting="navbar" data-name="navbar-default"
                data-value="default">
                <span class=""> Default Navbar</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Library Bundle Script -->
    <script src="../../assets/js/core/libs.min.js"></script>
    
    <!-- External Library Bundle Script -->
    <script src="../../assets/js/core/external.min.js"></script>
    
    <!-- Widgetchart Script -->
    <script src="../../assets/js/charts/widgetcharts.js"></script>
    
    <!-- mapchart Script -->
    <script src="../../assets/js/charts/vectore-chart.js"></script>
    <script src="../../assets/js/charts/dashboard.js" ></script>
    
    <!-- fslightbox Script -->
    <script src="../../assets/js/plugins/fslightbox.js"></script>
    
    <!-- Settings Script -->
    <script src="../../assets/js/plugins/setting.js"></script>
    
    <!-- Slider-tab Script -->
    <script src="../../assets/js/plugins/slider-tabs.js"></script>
    
    <!-- Form Wizard Script -->
    <script src="../../assets/js/plugins/form-wizard.js"></script>
    
    <!-- AOS Animation Plugin-->
    
    <!-- App Script -->
    <script src="../../assets/js/hope-ui.js" defer></script>
    <!-- Custom JavaScript for Form Navigation and Validation -->
<!-- Custom JavaScript for Form Navigation and Validation -->
<!-- Custom JavaScript for Form Navigation and Validation -->
 <!-- JavaScript for dynamic calculations -->
 <script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethodSelect = document.getElementById('payment_method');
    const bankNameField = document.getElementById('bank_name').closest('.col-md-6');
    const chequeRefNoField = document.getElementById('cheque_ref_no').closest('.col-md-6');
    const transactionNoField = document.getElementById('transaction_no').closest('.col-md-6');

    function toggleFields() {
        const selectedMethod = paymentMethodSelect.value;
        bankNameField.style.display = 'none';
        chequeRefNoField.style.display = 'none';
        transactionNoField.style.display = 'none';

        if (selectedMethod === 'bank') {
            bankNameField.style.display = 'block';
            transactionNoField.style.display = 'block';
        } else if (selectedMethod === 'cheque') {
            chequeRefNoField.style.display = 'block';
        }
    }

    paymentMethodSelect.addEventListener('change', toggleFields);
    toggleFields(); // Initial call to set the correct visibility on page load
});
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Fetch necessary DOM elements
        const tenantDropdown = document.getElementById('tenant');
        const subTotalsField = document.getElementById('sub_totals'); // From Payment Information
        const subTotalField = document.getElementById('sub_total'); // From Payment Description
        const discountField = document.getElementById('discount');
        const vatField = document.getElementById('vat');
        const withholdField = document.getElementById('withhold');
        const totalField = document.getElementById('total');
        const withholdCheckbox = document.getElementById('withheldCheckbox');
        const discountedCheckbox = document.getElementById('discountedCheckbox');
        const penalityCheckbox = document.getElementById('penalityCheckbox');
        const paymentDateField = document.getElementById('payment_date'); // Assuming there's a payment date field
        const penalityField = document.getElementById('penality');
        const paymentColorField = document.getElementById('paymentColor'); // Assuming there's an element to display color

        // Fetch Penality Data from the Server
        let penalities = [];

        fetch('get_penalities.php') // Create a PHP endpoint that returns penality data as JSON
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    penalities = data.penalities;
                } else {
                    console.error('Failed to fetch penalities:', data.message);
                }
            })
            .catch(error => {
                console.error('Error fetching penalities:', error);
            });

        /**
         * Function to calculate VAT, Withhold, Discount, Penality, and Total
         */
        function calculateFields() {
            const subTotal = parseFloat(subTotalField.value) || 0;
            const discount = discountedCheckbox.checked ? (parseFloat(discountField.value) || 0) : 0;
            const vat = subTotal * 0.15; // 15% VAT
            const withhold = withholdCheckbox.checked ? subTotal * 0.02 : 0; // 2% Withhold
            const penality = penalityCheckbox.checked ? (parseFloat(penalityField.value) || 0) : 0; // Penality if applicable
            const total = (subTotal - discount) + vat + penality;

            // Update the fields with calculated values
            vatField.value = vat.toFixed(2);
            withholdField.value = withhold.toFixed(2);
            penalityField.value = penality.toFixed(2);
            totalField.value = total.toFixed(2);
        }

        /**
         * Function to update tenant details based on selected tenant
         */
        function updateTenantDetails() {
            const selectedTenantId = tenantDropdown.value;

            if (!selectedTenantId) {
                // Clear form fields if no tenant is selected
                document.getElementById('tenant_tin_no').value = '';
                document.getElementById('room').value = '';
                document.getElementById('rent_due_date').value = '';
                document.getElementById('rent_amount').value = '';
                subTotalsField.value = '';
                subTotalField.value = '';
                calculateFields();
                return;
            }

            // Fetch tenant details using Fetch API
            const url = `get_tenants.php?tenant_id=${selectedTenantId}`;
            console.log(`Fetching tenant data from URL: ${url}`);

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    console.log('Raw response:', data);
                    if (data.success && data.tenant) {
                        const tenant = data.tenant;

                        // Populate form fields with tenant data
                        console.log('Populating form fields with tenant data:', tenant);
                        document.getElementById('tenant_tin_no').value = tenant.tin_no || '';
                        document.getElementById('room').value = tenant.room || '';
                        document.getElementById('rent_due_date').value = tenant.rent_due_date ? new Date(tenant.rent_due_date).toISOString().split('T')[0] : '';
                        document.getElementById('rent_amount').value = tenant.rent_amount || 0;

                        // Parse and multiply rent amount and term of payment
                        const rentAmount = parseFloat(tenant.rent_amount) || 0;
                        const termOfPayment = parseFloat(tenant.term_of_payment) || 0;
                        console.log(`Rent Amount: ${rentAmount}, Term of Payment: ${termOfPayment}`);
                        const calculatedSubTotal = rentAmount * termOfPayment;
                        console.log(`Calculated SubTotal: ${calculatedSubTotal}`);

                        // Update sub_totals and sub_total fields
                        subTotalsField.value = calculatedSubTotal.toFixed(2) || '0.00';
                        subTotalField.value = calculatedSubTotal.toFixed(2) || '0.00';

                        // Perform calculations after updating sub_total
                        calculateFields();
                        console.log('Form fields after population:', {
                            tenant_tin_no: document.getElementById('tenant_tin_no').value,
                            room: document.getElementById('room').value,
                            rent_due_date: document.getElementById('rent_due_date').value,
                            rent_amount: document.getElementById('rent_amount').value,
                            sub_totals: subTotalsField.value,
                            sub_total: subTotalField.value
                        });
                    } else {
                        // Use a default message if data.message is undefined
                        const errorMessage = data.message || 'Failed to fetch tenant details due to an unknown error.';
                        alert(`Failed to fetch tenant details: ${errorMessage}`);

                        // Clear form fields if fetching tenant details fails
                        document.getElementById('tenant_tin_no').value = '';
                        document.getElementById('room').value = '';
                        document.getElementById('rent_due_date').value = '';
                        subTotalsField.value = '';
                        subTotalField.value = '';
                        calculateFields();
                    }
                })
                .catch(error => {
                    console.error('Error fetching tenant details:', error);
                    alert('An error occurred while fetching tenant details.');
                });
        }

        /**
         * Event listener for tenant selection change
         */
        if (tenantDropdown) {
            tenantDropdown.addEventListener('change', updateTenantDetails);
        }

        /**
         * Event listeners for checkboxes to enable/disable corresponding fields
         */
        if (withholdCheckbox) {
            withholdCheckbox.addEventListener('change', function () {
                withholdField.disabled = !this.checked;
                if (!this.checked) {
                    withholdField.value = '0.00';
                }
                calculateFields();
            });
        }

        if (discountedCheckbox) {
            discountedCheckbox.addEventListener('change', function () {
                discountField.disabled = !this.checked;
                if (!this.checked) {
                    discountField.value = '0.00';
                }
                calculateFields();
            });
        }

        if (penalityCheckbox) {
            penalityCheckbox.addEventListener('change', function () {
                penalityField.disabled = !this.checked;
                if (!this.checked) {
                    penalityField.value = '0.00';
                    paymentColorField.style.backgroundColor = ''; // Reset color
                }
                calculateFields();
            });
        }

        /**
         * Event listener for discount and penality input fields
         */
        if (discountField) {
            discountField.addEventListener('input', calculateFields);
        }

        if (penalityField) {
            penalityField.addEventListener('input', calculateFields);
        }

        /**
         * Function to calculate penality based on due days and update color
         */
        function applyPenality() {
            const isPenalityChecked = penalityCheckbox.checked;
            const rentDueDate = new Date(document.getElementById('rent_due_date').value);
            const paymentDate = new Date(paymentDateField.value) || new Date(); // Current date if not set
            const subtotal = parseFloat(subTotalField.value) || 0;

            if (isPenalityChecked) {
                // Calculate overdue days
                const timeDiff = paymentDate - rentDueDate;
                const overdueDays = Math.floor(timeDiff / (1000 * 60 * 60 * 24));

                if (overdueDays > 0) {
                    // Determine applicable penality based on overdue days
                    let applicablePenality = penalities.find(penalty => {
                        if (penalty.measure.includes('-')) {
                            const [min, max] = penalty.measure.split('-').map(Number);
                            return overdueDays >= min && overdueDays <= max;
                        } else if (penalty.measure.startsWith('>')) {
                            const min = Number(penalty.measure.replace('>', ''));
                            return overdueDays > min;
                        }
                        return false;
                    });

                    if (applicablePenality) {
                        const penalityAmount = subtotal * parseFloat(applicablePenality.percent);
                        penalityField.value = penalityAmount.toFixed(2);
                        // Update color in the list
                        paymentColorField.style.backgroundColor = applicablePenality.color;
                    } else {
                        alert('No applicable penality for the due days.');
                        penalityCheckbox.checked = false;
                        penalityField.value = '0.00';
                        paymentColorField.style.backgroundColor = '';
                    }
                } else {
                    alert('Payment is not overdue.');
                    penalityCheckbox.checked = false;
                    penalityField.value = '0.00';
                    paymentColorField.style.backgroundColor = '';
                }
            } else {
                // Reset penality and color if checkbox is unchecked
                penalityField.value = '0.00';
                paymentColorField.style.backgroundColor = '';
            }

            // Recalculate total after applying penality
            calculateFields();
        }

        /**
         * Attach the applyPenality function to relevant events
         */
        if (penalityCheckbox && paymentDateField) {
            penalityCheckbox.addEventListener('change', applyPenality);
            paymentDateField.addEventListener('change', function () {
                if (penalityCheckbox.checked) {
                    applyPenality();
                }
            });
        }

        /**
         * Initial calculation on page load
         */
        calculateFields();
    });
</script>
<script>
document.getElementById('inlineAdd').addEventListener('click', function() {
    const table = document.getElementById('additionalFeeTable').getElementsByTagName('tbody')[0];
    const newRow = table.insertRow();

    newRow.innerHTML = `
        <td><button type="button" class="btn btn-danger btn-sm removeRow">X</button></td>
        <td><input type="text" class="form-control" name="additional_fee_title[]"></td>
        <td><input type="number" step="0.01" class="form-control" name="additional_fee_price[]"></td>
        <td><input type="text" class="form-control" name="additional_fee_description[]"></td>
        <td><input type="date" class="form-control" name="additional_fee_registration_date[]"></td>
    `;

    // Add event listener to the remove button
    newRow.querySelector('.removeRow').addEventListener('click', function() {
        table.deleteRow(newRow.rowIndex - 1);
        ensureAtLeastOneRow();
    });
});

document.getElementById('cancelAdd').addEventListener('click', function() {
    const table = document.getElementById('additionalFeeTable').getElementsByTagName('tbody')[0];
    table.innerHTML = ''; // Clear all rows
    addDefaultRow();
});

function addDefaultRow() {
    const table = document.getElementById('additionalFeeTable').getElementsByTagName('tbody')[0];
    const newRow = table.insertRow();

    newRow.innerHTML = `
        <td><button type="button" class="btn btn-danger btn-sm removeRow">X</button></td>
        <td><input type="text" class="form-control" name="additional_fee_title[]"></td>
        <td><input type="number" step="0.01" class="form-control" name="additional_fee_price[]"></td>
        <td><input type="text" class="form-control" name="additional_fee_description[]"></td>
        <td><input type="date" class="form-control" name="additional_fee_registration_date[]"></td>
    `;

    // Add event listener to the remove button
    newRow.querySelector('.removeRow').addEventListener('click', function() {
        table.deleteRow(newRow.rowIndex - 1);
        ensureAtLeastOneRow();
    });
}

function ensureAtLeastOneRow() {
    const table = document.getElementById('additionalFeeTable').getElementsByTagName('tbody')[0];
    if (table.rows.length === 0) {
        addDefaultRow();
    }
}

// Ensure at least one row is present on page load
document.addEventListener('DOMContentLoaded', function() {
    ensureAtLeastOneRow();
});
</script>
<script>
        // Handle Add Payment Form Tabs Navigation
        document.getElementById('nextToPaymentDescription').addEventListener('click', function() {
            var paymentTab = new bootstrap.Tab(document.querySelector('#payment-description-tab'));
            paymentTab.show();
        });

        document.getElementById('nextToAdditionalFee').addEventListener('click', function() {
            var additionalFeeTab = new bootstrap.Tab(document.querySelector('#additional-fee-tab'));
            additionalFeeTab.show();
        });

        // Handle Edit Payment Form Tabs Navigation
        document.getElementById('editNextToPaymentDescription').addEventListener('click', function() {
            var editPaymentDescriptionTab = new bootstrap.Tab(document.querySelector('#edit-payment-description-tab'));
            editPaymentDescriptionTab.show();
        });

        document.getElementById('editNextToAdditionalFee').addEventListener('click', function() {
            var editAdditionalFeeTab = new bootstrap.Tab(document.querySelector('#edit-additional-fee-tab'));
            editAdditionalFeeTab.show();
        });
    </script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const addPaymentForm = document.getElementById('addPaymentForm');
        const nextToPaymentDescriptionBtn = document.getElementById('nextToPaymentDescription');
        const nextToAdditionalFeeBtn = document.getElementById('nextToAdditionalFee');
        const paymentDescriptionTab = document.getElementById('payment-description-tab');
        const additionalFeeTab = document.getElementById('additional-fee-tab');

        // Function to check if all required fields in Payment Information tab are filled
        function validatePaymentInfo() {
            const requiredFields = document.querySelectorAll('#payment-info [required]');
            let allFilled = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    allFilled = false;
                }
            });

            // Enable or disable the Next button based on validation
            nextToPaymentDescriptionBtn.disabled = !allFilled;
        }

        // Function to check if all required fields in Payment Description tab are filled
        function validatePaymentDescription() {
            const requiredFields = document.querySelectorAll('#payment-description [required]');
            let allFilled = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    allFilled = false;
                }
            });

            // Enable or disable the Next button based on validation
            nextToAdditionalFeeBtn.disabled = !allFilled;
        }

        // Function to enable the Payment Description tab
        function enablePaymentDescriptionTab() {
            if (!paymentDescriptionTab.classList.contains('disabled')) return;

            paymentDescriptionTab.classList.remove('disabled');
            paymentDescriptionTab.setAttribute('aria-disabled', 'false');
            paymentDescriptionTab.removeAttribute('tabindex');

            // Initialize Bootstrap Tab instance and show the Payment Description tab
            const paymentDescriptionTabInstance = new bootstrap.Tab(paymentDescriptionTab);
            paymentDescriptionTabInstance.show();

            // Disable the Next button after moving to the next tab
            nextToPaymentDescriptionBtn.disabled = true;
        }

        // Function to enable the Additional Fee tab
        function enableAdditionalFeeTab() {
            if (!additionalFeeTab.classList.contains('disabled')) return;

            additionalFeeTab.classList.remove('disabled');
            additionalFeeTab.setAttribute('aria-disabled', 'false');
            additionalFeeTab.removeAttribute('tabindex');

            // Initialize Bootstrap Tab instance and show the Additional Fee tab
            const additionalFeeTabInstance = new bootstrap.Tab(additionalFeeTab);
            additionalFeeTabInstance.show();

            // Disable the Next button after moving to the next tab
            nextToAdditionalFeeBtn.disabled = true;
        }

        // Event listener for input changes in Payment Information tab
        const paymentInfoInputs = document.querySelectorAll('#payment-info [required]');
        paymentInfoInputs.forEach(input => {
            input.addEventListener('input', validatePaymentInfo);
        });

        // Event listener for input changes in Payment Description tab
        const paymentDescriptionInputs = document.querySelectorAll('#payment-description [required]');
        paymentDescriptionInputs.forEach(input => {
            input.addEventListener('input', validatePaymentDescription);
        });

        // Event listener for Next button click in Payment Information tab
        nextToPaymentDescriptionBtn.addEventListener('click', function() {
            enablePaymentDescriptionTab();
        });

        // Event listener for Next button click in Payment Description tab
        nextToAdditionalFeeBtn.addEventListener('click', function() {
            enableAdditionalFeeTab();
        });

        // Prevent navigating to the Payment Description tab via clicking if not enabled
        paymentDescriptionTab.addEventListener('click', function(event) {
            if (paymentDescriptionTab.classList.contains('disabled')) {
                event.preventDefault();
            }
        });

        // Prevent navigating to the Additional Fee tab via clicking if not enabled
        additionalFeeTab.addEventListener('click', function(event) {
            if (additionalFeeTab.classList.contains('disabled')) {
                event.preventDefault();
            }
        });

        // Initial validation check
        validatePaymentInfo();
        validatePaymentDescription();
    });
</script>
<script>
 function printPayment(paymentId) {
    fetch(`get_payment_details.php?id=${paymentId}`)
        .then(response => response.text())
        .then(text => {
            console.log('Raw response from server:', text);
            try {
                const data = JSON.parse(text);
                if (data.error) {
                    console.error('Error from server:', data.error);
                    alert(`Failed to fetch payment details: ${data.error}`);
                    return;
                }
                console.log('Payment Data:', data);

                // Create a new window for printing
                const printWindow = window.open('', '', 'height=600,width=800');
                const currentDate = new Date();
                const formattedDate = currentDate.toLocaleDateString('en-GB', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });

                // ----------------------------------------
                // Calculate the Date Range
                // ----------------------------------------

                // Extract and log the rent_due_date and next_payment_date
                const rentDueDate = new Date(data.rent_due_date);
                const nextPaymentDate = new Date(data.next_payment_date);

                console.log('Rent Due Date (Raw):', data.rent_due_date);
                console.log('Rent Due Date (Date Object):', rentDueDate);

                console.log('Next Payment Date (Raw):', data.next_payment_date);
                console.log('Next Payment Date (Date Object):', nextPaymentDate);

                // Format the "From" date (rent_due_date)
                const fromDateFormatted = rentDueDate.toLocaleDateString('en-GB');
                console.log('From Date (Formatted):', fromDateFormatted);

                // Format the "To" date (next_payment_date)
                const toDateFormatted = nextPaymentDate.toLocaleDateString('en-GB');
                console.log('To Date (Formatted):', toDateFormatted);

                // Construct the date range
                const dateRange = `${fromDateFormatted} - ${toDateFormatted}`;
                console.log('Date Range:', dateRange);

                // ----------------------------------------
                // Calculate Months and Days Between From and To Dates
                // ----------------------------------------

                /**
                 * Calculates the difference between two dates in months and days.
                 * @param {Date} fromDateObj - The start date.
                 * @param {Date} toDateObj - The end date.
                 * @returns {Object} An object containing the number of months and days.
                 */
                function calculateMonthsAndDays(fromDateObj, toDateObj) {
                    let start = new Date(fromDateObj);
                    let end = new Date(toDateObj);

                    let years = end.getFullYear() - start.getFullYear();
                    let months = end.getMonth() - start.getMonth() + (years * 12);
                    let days = end.getDate() - start.getDate();

                    if (days < 0) {
                        months -= 1;
                        // Get the number of days in the previous month
                        let previousMonth = new Date(end.getFullYear(), end.getMonth(), 0);
                        days += previousMonth.getDate();
                    }

                    return { months: months, days: days };
                }

                // Define fromDate and toDate for calculation
                const fromDate = rentDueDate; // Correctly define fromDate
                const toDate = nextPaymentDate; // Correctly define toDate

                // Calculate the duration
                const duration = calculateMonthsAndDays(fromDate, toDate);
                console.log('Calculated Duration:', duration.months + ' Months & ' + duration.days + ' Days');

                // ----------------------------------------
                // Prepare Content for Printing
                // ----------------------------------------

            printWindow.document.write('<html><head><title>Print Payment</title>');
            printWindow.document.write('<style>');
            printWindow.document.write('body { font-family: "Times New Roman", Times, serif; font-size: 12px; margin: 0; padding: 0; }');
            printWindow.document.write('.invoice-container { width: 100%; padding: 5px; box-sizing: border-box; position: relative; }');
            printWindow.document.write('.image-container { display: flex; align-items: center; position: relative; padding: 2px 5px; margin-bottom: 5px; }');
            printWindow.document.write('.header-wrapper { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 9px; line-height: 1.0; }');
            printWindow.document.write('.header-wrapperr { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; border-bottom: 2px solid #ddd; padding-bottom: 1px; }');
            printWindow.document.write('.info-column { display: flex; flex-direction: column; width: 50%; box-sizing: border-box; }');
            printWindow.document.write('.company-info { margin-bottom: 3px; }');
            printWindow.document.write('.company-logo { max-width: 150px; height: 120px; display: block; margin-bottom: 1px; }');
            printWindow.document.write('.company-details p, .receipt-info p { margin: 1px 0; }');
            printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-top: 5px; }');
            printWindow.document.write('th, td { border: 1px solid black; padding: 3px; text-align: left; }');
            printWindow.document.write('th { background-color: #f2f2f2; }');
            printWindow.document.write('.invoice-details { margin: 3px auto; padding: 3px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); line-height: 1.0; font-size: 9px; }');
            printWindow.document.write('.details-container { display: flex; justify-content: space-between; line-height: 1.0; font-size: 9px; }');
            printWindow.document.write('.invoice-summary { margin-top: 3px; }');
            printWindow.document.write('.button-container { display: flex; gap: 10px; position: absolute; right: 20px; top: 50%; transform: translateY(-50%); }');
            printWindow.document.write('.btn { display: inline-block; padding: 10px 20px; font-size: 16px; color: #fff; border: none; border-radius: 4px; text-align: center; cursor: pointer; text-decoration: none; }');
            printWindow.document.write('.btn-primary { background-color: #007bff; }');
            printWindow.document.write('.btn-primary:hover { background-color: #0056b3; }');
            printWindow.document.write('.btn-secondary { background-color: #6c757d; }');
            printWindow.document.write('.btn-secondary:hover { background-color: #5a6268; }');
            printWindow.document.write('.header-info { display: flex; flex-direction: column; width: 80%; }');
            printWindow.document.write('.header-info p { margin: 5px 0; }');
            printWindow.document.write('.header-in { margin-left: 10px; }');
            printWindow.document.write('.reduce-line-space p { margin: 0; line-height: 1.0; font-size: 9px; }');
            printWindow.document.write('.no-border { border: none !important; }');
            printWindow.document.write('@media print { .button-container { display: none; } @page { margin: 0; } body { margin: 0; padding: 0; } .print-hide { display: none; } }');
            printWindow.document.write('.additional-info { display: flex; justify-content: space-between; margin-top: 20px; font-size: 12px; }');
            printWindow.document.write('.additional-info div { flex: 1; }');
            printWindow.document.write('</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write('<div class="invoice-container">');
            printWindow.document.write('<div class="image-container">');
            
            // **Embedded SVG Logo**
            printWindow.document.write(`
<svg fill="#000000" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="80px" height="80px" viewBox="0 0 980 980" xml:space="preserve"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <g> <path d="M970.001,868.211h-25.659V210.402c0-5.523-4.477-10-10-10H43.662c-5.523,0-10,4.477-10,10v657.809H10 c-5.523,0-10,4.479-10,10v26.984c0,5.521,4.477,10,10,10h960c5.523,0,10-4.479,10-10v-26.984 C980.001,872.688,975.523,868.211,970.001,868.211z M121.265,798.182c0,5.521-4.477,10-10,10H70.816c-5.523,0-10-4.479-10-10 v-40.449c0-5.523,4.477-10,10-10h40.449c5.523,0,10,4.477,10,10V798.182z M121.265,712.543c0,5.523-4.477,10-10,10H70.816 c-5.523,0-10-4.477-10-10v-40.449c0-5.523,4.477-10,10-10h40.449c5.523,0,10,4.477,10,10V712.543z M121.265,626.906 c0,5.523-4.477,10-10,10H70.816c-5.523,0-10-4.477-10-10v-40.449c0-5.523,4.477-10,10-10h40.449c5.523,0,10,4.477,10,10V626.906z M121.265,541.271c0,5.521-4.477,10-10,10H70.816c-5.523,0-10-4.479-10-10V500.82c0-5.523,4.477-10,10-10h40.449 c5.523,0,10,4.477,10,10V541.271z M121.265,455.633c0,5.522-4.477,10-10,10H70.816c-5.523,0-10-4.478-10-10v-40.449 c0-5.523,4.477-10,10-10h40.449c5.523,0,10,4.477,10,10V455.633z M121.265,369.997c0,5.523-4.477,10-10,10H70.816 c-5.523,0-10-4.477-10-10v-40.449c0-5.523,4.477-10,10-10h40.449c5.523,0,10,4.477,10,10V369.997z M121.265,284.36 c0,5.523-4.477,10-10,10H70.816c-5.523,0-10-4.477-10-10v-40.45c0-5.523,4.477-10,10-10h40.449c5.523,0,10,4.477,10,10V284.36z M209.925,798.182c0,5.521-4.477,10-10,10h-40.45c-5.523,0-10-4.479-10-10v-40.449c0-5.523,4.477-10,10-10h40.45 c5.523,0,10,4.477,10,10V798.182z M209.925,712.543c0,5.523-4.477,10-10,10h-40.45c-5.523,0-10-4.477-10-10v-40.449 c0-5.523,4.477-10,10-10h40.45c5.523,0,10,4.477,10,10V712.543z M209.925,626.906c0,5.523-4.477,10-10,10h-40.45 c-5.523,0-10-4.477-10-10v-40.449c0-5.523,4.477-10,10-10h40.45c5.523,0,10,4.477,10,10V626.906z M209.925,541.271 c0,5.521-4.477,10-10,10h-40.45c-5.523,0-10-4.479-10-10V500.82c0-5.523,4.477-10,10-10h40.45c5.523,0,10,4.477,10,10V541.271z M209.925,455.633c0,5.522-4.477,10-10,10h-40.45c-5.523,0-10-4.478-10-10v-40.449c0-5.523,4.477-10,10-10h40.45 c5.523,0,10,4.477,10,10V455.633z M209.925,369.997c0,5.523-4.477,10-10,10h-40.45c-5.523,0-10-4.477-10-10v-40.449 c0-5.523,4.477-10,10-10h40.45c5.523,0,10,4.477,10,10V369.997z M209.925,284.36c0,5.523-4.477,10-10,10h-40.45 c-5.523,0-10-4.477-10-10v-40.45c0-5.523,4.477-10,10-10h40.45c5.523,0,10,4.477,10,10V284.36z M298.584,798.182 c0,5.521-4.477,10-10,10h-40.449c-5.523,0-10-4.479-10-10v-40.449c0-5.523,4.477-10,10-10h40.449c5.523,0,10,4.477,10,10V798.182z M298.584,712.543c0,5.523-4.477,10-10,10h-40.449c-5.523,0-10-4.477-10-10v-40.449c0-5.523,4.477-10,10-10h40.449 c5.523,0,10,4.477,10,10V712.543z M298.584,626.906c0,5.523-4.477,10-10,10h-40.449c-5.523,0-10-4.477-10-10v-40.449 c0-5.523,4.477-10,10-10h40.449c5.523,0,10,4.477,10,10V626.906z M298.584,541.271c0,5.521-4.477,10-10,10h-40.449 c-5.523,0-10-4.479-10-10V500.82c0-5.523,4.477-10,10-10h40.449c5.523,0,10,4.477,10,10V541.271z M298.584,455.633 c0,5.522-4.477,10-10,10h-40.449c-5.523,0-10-4.478-10-10v-40.449c0-5.523,4.477-10,10-10h40.449c5.523,0,10,4.477,10,10V455.633z M298.584,369.997c0,5.523-4.477,10-10,10h-40.449c-5.523,0-10-4.477-10-10v-40.449c0-5.523,4.477-10,10-10h40.449 c5.523,0,10,4.477,10,10V369.997z M298.584,284.36c0,5.523-4.477,10-10,10h-40.449c-5.523,0-10-4.477-10-10v-40.45 c0-5.523,4.477-10,10-10h40.449c5.523,0,10,4.477,10,10V284.36z M387.243,798.182c0,5.521-4.478,10-10,10h-40.449 c-5.523,0-10-4.479-10-10v-40.449c0-5.523,4.477-10,10-10h40.449c5.522,0,10,4.477,10,10V798.182z M387.243,712.543 c0,5.523-4.478,10-10,10h-40.449c-5.523,0-10-4.477-10-10v-40.449c0-5.523,4.477-10,10-10h40.449c5.522,0,10,4.477,10,10V712.543z M387.243,626.906c0,5.523-4.478,10-10,10h-40.449c-5.523,0-10-4.477-10-10v-40.449c0-5.523,4.477-10,10-10h40.449 c5.522,0,10,4.477,10,10V626.906z M387.243,541.271c0,5.521-4.478,10-10,10h-40.449c-5.523,0-10-4.479-10-10V500.82 c0-5.523,4.477-10,10-10h40.449c5.522,0,10,4.477,10,10V541.271z M387.243,455.633c0,5.522-4.478,10-10,10h-40.449 c-5.523,0-10-4.478-10-10v-40.449c0-5.523,4.477-10,10-10h40.449c5.522,0,10,4.477,10,10V455.633z M387.243,369.997 c0,5.523-4.478,10-10,10h-40.449c-5.523,0-10-4.477-10-10v-40.449c0-5.523,4.477-10,10-10h40.449c5.522,0,10,4.477,10,10V369.997z M387.243,284.36c0,5.523-4.478,10-10,10h-40.449c-5.523,0-10-4.477-10-10v-40.45c0-5.523,4.477-10,10-10h40.449 c5.522,0,10,4.477,10,10V284.36z M530.309,849.57c0,5.523-4.477,10-10,10h-60.603c-5.523,0-10-4.477-10-10v-90.838 c0-5.523,4.477-10,10-10h60.603c5.523,0,10,4.477,10,10V849.57z M564.562,712.543c0,5.523-4.477,10-10,10H425.453 c-5.523,0-10-4.477-10-10v-40.449c0-5.523,4.477-10,10-10h129.108c5.523,0,10,4.477,10,10V712.543z M564.562,626.906 c0,5.523-4.477,10-10,10H425.453c-5.523,0-10-4.477-10-10v-40.449c0-5.523,4.477-10,10-10h129.108c5.523,0,10,4.477,10,10V626.906 z M564.562,541.271c0,5.521-4.477,10-10,10H425.453c-5.523,0-10-4.479-10-10V500.82c0-5.523,4.477-10,10-10h129.108 c5.523,0,10,4.477,10,10V541.271z M564.562,455.633c0,5.522-4.477,10-10,10H425.453c-5.523,0-10-4.478-10-10v-40.449 c0-5.523,4.477-10,10-10h129.108c5.523,0,10,4.477,10,10V455.633z M564.562,369.997c0,5.523-4.477,10-10,10H425.453 c-5.523,0-10-4.477-10-10v-40.449c0-5.523,4.477-10,10-10h129.108c5.523,0,10,4.477,10,10V369.997z M564.562,284.36 c0,5.523-4.477,10-10,10H425.453c-5.523,0-10-4.477-10-10v-40.45c0-5.523,4.477-10,10-10h129.108c5.523,0,10,4.477,10,10V284.36z M653.221,798.182c0,5.521-4.477,10-10,10h-40.449c-5.522,0-10-4.479-10-10v-40.449c0-5.523,4.478-10,10-10h40.449 c5.523,0,10,4.477,10,10V798.182z M653.221,712.543c0,5.523-4.477,10-10,10h-40.449c-5.522,0-10-4.477-10-10v-40.449 c0-5.523,4.478-10,10-10h40.449c5.523,0,10,4.477,10,10V712.543z M653.221,626.906c0,5.523-4.477,10-10,10h-40.449 c-5.522,0-10-4.477-10-10v-40.449c0-5.523,4.478-10,10-10h40.449c5.523,0,10,4.477,10,10V626.906z M653.221,541.271 c0,5.521-4.477,10-10,10h-40.449c-5.522,0-10-4.479-10-10V500.82c0-5.523,4.478-10,10-10h40.449c5.523,0,10,4.477,10,10V541.271z M653.221,455.633c0,5.522-4.477,10-10,10h-40.449c-5.522,0-10-4.478-10-10v-40.449c0-5.523,4.478-10,10-10h40.449 c5.523,0,10,4.477,10,10V455.633z M653.221,369.997c0,5.523-4.477,10-10,10h-40.449c-5.522,0-10-4.477-10-10v-40.449 c0-5.523,4.478-10,10-10h40.449c5.523,0,10,4.477,10,10V369.997z M653.221,284.36c0,5.523-4.477,10-10,10h-40.449 c-5.522,0-10-4.477-10-10v-40.45c0-5.523,4.478-10,10-10h40.449c5.523,0,10,4.477,10,10V284.36z M741.88,798.182 c0,5.521-4.477,10-10,10h-40.449c-5.522,0-10-4.479-10-10v-40.449c0-5.523,4.478-10,10-10h40.449c5.523,0,10,4.477,10,10V798.182z M741.88,712.543c0,5.523-4.477,10-10,10h-40.449c-5.522,0-10-4.477-10-10v-40.449c0-5.523,4.478-10,10-10h40.449 c5.523,0,10,4.477,10,10V712.543z M741.88,626.906c0,5.523-4.477,10-10,10h-40.449c-5.522,0-10-4.477-10-10v-40.449 c0-5.523,4.478-10,10-10h40.449c5.523,0,10,4.477,10,10V626.906z M741.88,541.271c0,5.521-4.477,10-10,10h-40.449 c-5.522,0-10-4.479-10-10V500.82c0-5.523,4.478-10,10-10h40.449c5.523,0,10,4.477,10,10V541.271z M741.88,455.633 c0,5.522-4.477,10-10,10h-40.449c-5.522,0-10-4.478-10-10v-40.449c0-5.523,4.478-10,10-10h40.449c5.523,0,10,4.477,10,10V455.633z M741.88,369.997c0,5.523-4.477,10-10,10h-40.449c-5.522,0-10-4.477-10-10v-40.449c0-5.523,4.478-10,10-10h40.449 c5.523,0,10,4.477,10,10V369.997z M741.88,284.36c0,5.523-4.477,10-10,10h-40.449c-5.522,0-10-4.477-10-10v-40.45 c0-5.523,4.478-10,10-10h40.449c5.523,0,10,4.477,10,10V284.36z M830.54,798.182c0,5.521-4.478,10-10,10h-40.449 c-5.522,0-10-4.479-10-10v-40.449c0-5.523,4.478-10,10-10h40.449c5.522,0,10,4.477,10,10V798.182z M830.54,712.543 c0,5.523-4.478,10-10,10h-40.449c-5.522,0-10-4.477-10-10v-40.449c0-5.523,4.478-10,10-10h40.449c5.522,0,10,4.477,10,10V712.543z M830.54,626.906c0,5.523-4.478,10-10,10h-40.449c-5.522,0-10-4.477-10-10v-40.449c0-5.523,4.478-10,10-10h40.449 c5.522,0,10,4.477,10,10V626.906z M830.54,541.271c0,5.521-4.478,10-10,10h-40.449c-5.522,0-10-4.479-10-10V500.82 c0-5.523,4.478-10,10-10h40.449c5.522,0,10,4.477,10,10V541.271z M830.54,455.633c0,5.522-4.478,10-10,10h-40.449 c-5.522,0-10-4.478-10-10v-40.449c0-5.523,4.478-10,10-10h40.449c5.522,0,10,4.477,10,10V455.633z M830.54,369.997 c0,5.523-4.478,10-10,10h-40.449c-5.522,0-10-4.477-10-10v-40.449c0-5.523,4.478-10,10-10h40.449c5.522,0,10,4.477,10,10V369.997z M830.54,284.36c0,5.523-4.478,10-10,10h-40.449c-5.522,0-10-4.477-10-10v-40.45c0-5.523,4.478-10,10-10h40.449 c5.522,0,10,4.477,10,10V284.36z M919.199,798.182c0,5.521-4.478,10-10,10H868.75c-5.523,0-10-4.479-10-10v-40.449 c0-5.523,4.477-10,10-10h40.449c5.522,0,10,4.477,10,10V798.182z M919.199,712.543c0,5.523-4.478,10-10,10H868.75 c-5.523,0-10-4.477-10-10v-40.449c0-5.523,4.477-10,10-10h40.449c5.522,0,10,4.477,10,10V712.543z M919.199,626.906 c0,5.523-4.478,10-10,10H868.75c-5.523,0-10-4.477-10-10v-40.449c0-5.523,4.477-10,10-10h40.449c5.522,0,10,4.477,10,10V626.906z M919.199,541.271c0,5.521-4.478,10-10,10H868.75c-5.523,0-10-4.479-10-10V500.82c0-5.523,4.477-10,10-10h40.449 c5.522,0,10,4.477,10,10V541.271z M919.199,455.633c0,5.522-4.478,10-10,10H868.75c-5.523,0-10-4.478-10-10v-40.449 c0-5.523,4.477-10,10-10h40.449c5.522,0,10,4.477,10,10V455.633z M919.199,369.997c0,5.523-4.478,10-10,10H868.75 c-5.523,0-10-4.477-10-10v-40.449c0-5.523,4.477-10,10-10h40.449c5.522,0,10,4.477,10,10V369.997z M919.199,284.36 c0,5.523-4.478,10-10,10H868.75c-5.523,0-10-4.477-10-10v-40.45c0-5.523,4.477-10,10-10h40.449c5.522,0,10,4.477,10,10V284.36z"></path> <path d="M19.143,179.663h939.719c4.286,0,6.585-5.04,3.775-8.277l-38.512-44.374c-1.861-2.745-4.961-4.389-8.277-4.389h-65.233 c-5.523,0-10-4.477-10-10V74.805c0-5.523-4.478-10-10-10h-32.39c-5.522,0-10,4.477-10,10v37.818c0,5.523-4.477,10-10,10H201.79 c-5.523,0-10-4.477-10-10V74.805c0-5.523-4.477-10-10-10H149.4c-5.523,0-10,4.477-10,10v37.818c0,5.523-4.477,10-10,10H62.156 c-3.316,0-6.416,1.645-8.277,4.389l-38.512,44.374C12.557,174.623,14.856,179.663,19.143,179.663z"></path> </g> </g> </g></svg>            `);
            
            printWindow.document.write('<h1 style="margin: 0; font-size: 15px; font-weight: bold;">Nest-Net1>');
            printWindow.document.write('<div class="button-container">');
            printWindow.document.write('<a href="v_invoice.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>');
            printWindow.document.write('<button type="button" onclick="window.print()" class="btn btn-primary"><i class="fas fa-file-pdf"></i> Print</button>');
            printWindow.document.write('</div></div>');
            printWindow.document.write('<div class="header-wrapperr">');
            printWindow.document.write('<div class="header-in"><p style="font-size: 15px; font-weight: bold;">Cash Sales Attachment</p></div>');
            printWindow.document.write('<div class="reduce-line-space">');
            printWindow.document.write('<p>Record No#: ' + data.id + '</p>');
            printWindow.document.write('<p>Z No#: ' + data.fs_number + '</p>');
            printWindow.document.write('<p>Date: ' + formattedDate + '</p>');
            printWindow.document.write('</div></div>');
            printWindow.document.write('<div class="header-wrapper">');
            printWindow.document.write('<div class="header-info">');
            printWindow.document.write('<div class="company-info">');
            printWindow.document.write('<div class="company-details">');
            printWindow.document.write('<p>From: Nest-Net>');
            printWindow.document.write('<p>TIN No: 0016231421</p>');
            printWindow.document.write('<p>Phone No: +2510113727910</p>');
            printWindow.document.write('<p>Email: info@rightTech.com</p>');
            printWindow.document.write('<p>P.O.Box: 50063</p>');
            printWindow.document.write('<p>Address: Sub City: Arada, Kebele: 1</p>');
            printWindow.document.write('<p>H.No: 1, Addis Ababa, Ethiopia</p>');
            printWindow.document.write('</div></div></div>');
            printWindow.document.write('<div class="header-info">');
            printWindow.document.write('<div class="receipt-info">');
            printWindow.document.write('<p>To: ' + data.tenant + '</p>');
            printWindow.document.write('<p>Customer TIN: ' + data.tenant_tin + '</p>');
            printWindow.document.write('<p>Room: ' + data.room + '</p>');
            printWindow.document.write('<p>FS Number: ' + data.fs_number + '</p>');
            printWindow.document.write('<p>Payment Date: ' + data.payment_date + '</p>');
            printWindow.document.write('</div></div></div>');
            printWindow.document.write('<div class="details-container">');
            printWindow.document.write('<div class="invoice-details" style="width: 100%;">');
            printWindow.document.write('<h2 style="text-align: center; font-size: 15px; font-weight: bold;">Invoice Details</h2>');
            printWindow.document.write('<table>');
            printWindow.document.write('<tr><th style="text-align: center;">Description</th><th style="text-align: center;">Date from - to</th><th style="text-align: center;">Months</th><th style="text-align: center;">Amount</th></tr>');
            printWindow.document.write('<tr><td>Rent</td><td>' + dateRange + '</td><td>' + duration.months + ' Month' + (duration.months !== 1 ? 's' : '') + ' & ' + duration.days + ' Day' + (duration.days !== 1 ? 's' : '') + '</td><td>' + data.price.toFixed(2) + '</td></tr>');            printWindow.document.write('<tr><td colspan="3" class="no-border" style="text-align: right;">Sub Total</td><td style="text-align: center;">' + data.price.toFixed(2) + '</td></tr>');
            printWindow.document.write('<tr><td colspan="3" class="no-border" style="text-align: right;">VAT (15%)</td><td style="text-align: center;">' + data.vat.toFixed(2) + '</td></tr>');
            printWindow.document.write('<tr><td colspan="3" class="no-border" style="text-align: right;">Withhold (2%)</td><td style="text-align: center;">' + data.withhold.toFixed(2) + '</td></tr>');
            //add penality
            printWindow.document.write('<tr><td colspan="3" class="no-border" style="text-align: right;">Penality</td><td style="text-align: center;">' + data.penality.toFixed(2) + '</td></tr>');
            printWindow.document.write('<tr><td colspan="3" class="no-border" style="text-align: right;">Total</td><td style="text-align: center;">' + data.total.toFixed(2) + '</td></tr>');
            printWindow.document.write('</table></div></div>');
            printWindow.document.write('<div class="additional-info" style="margin-top: 20px;">');
            printWindow.document.write('<div>Payment Method: ' + data.payment_method + '</div>');
            printWindow.document.write('<div>Transaction No: ' + data.transaction_no + '</div>');
            printWindow.document.write('<div>User: ' + data.received_by + '</div>');
            printWindow.document.write('</div></div>');
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        } catch (error) {
                console.error('Error parsing JSON:', error);
                alert(`An error occurred while parsing payment details: ${error.message}`);
            }
        })
        .catch(error => {
            console.error('Error fetching payment details:', error);
            alert(`An error occurred while fetching payment details: ${error.message}`);
        });
}
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle Add Payment Form Submission
    document.getElementById('addPaymentForm').addEventListener('submit', function (e) {
        e.preventDefault();
        console.log('Form submission started');

        // Disable the submit button to prevent multiple submissions
        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Adding...';

        const formData = new FormData(this);
        console.log('FormData created:', formData);

        fetch('add_payment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response received from add_payment.php:', response);
            // Check if the response is JSON
            const contentType = response.headers.get('Content-Type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => { throw new Error('Invalid JSON response: ' + text); });
            }
            return response.json();
        })
        .then(data => {
            console.log('Parsed JSON data from add_payment.php:', data);
            if (data.success) {
                const paymentId = data.payment_id;
                console.log('Payment ID:', paymentId);

                // Collect all additional fee entries
                const additionalFees = [];
                const additionalFeeRows = document.querySelectorAll('#additionalFeeTable tbody tr');

                additionalFeeRows.forEach((row, index) => {
                    const title = row.querySelector('input[name="additional_fee_title[]"]').value.trim();
                    const price = row.querySelector('input[name="additional_fee_price[]"]').value.trim();
                    const description = row.querySelector('input[name="additional_fee_description[]"]').value.trim();
                    const registrationDate = row.querySelector('input[name="additional_fee_registration_date[]"]').value.trim();

                    console.log(`Row ${index + 1} - Title: ${title}, Price: ${price}, Description: ${description}, Registration Date: ${registrationDate}`);

                    if (title || price || description || registrationDate) {
                        additionalFees.push({
                            title: title,
                            price: price,
                            description: description,
                            registration_date: registrationDate
                        });
                    }
                });

                console.log('Additional Fees:', additionalFees);

                if (additionalFees.length > 0) {
                    // Send additional fees as JSON
                    fetch('add_additional_fee.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ payment_id: paymentId, additional_fees: additionalFees })
                    })
                    .then(response => {
                        console.log('Response received from add_additional_fee.php:', response);
                        return response.text().then(text => {
                            console.log('Raw response from add_additional_fee.php:', text);
                            // Attempt to parse JSON
                            try {
                                const json = JSON.parse(text);
                                if (!json || typeof json !== 'object') {
                                    throw new Error('Invalid JSON structure.');
                                }
                                return json;
                            } catch (err) {
                                throw new Error('Invalid JSON response from add_additional_fee.php: ' + err.message + ' -- Response text: ' + text);
                            }
                        });
                    })
                    .then(data => {
                        console.log('Parsed JSON data from add_additional_fee.php:', data);
                        submitButton.disabled = false;
                        submitButton.textContent = 'Add Payment';
                        if (data.success) {
                            alert('Payment and additional fees added successfully.');
                            // Reload the page to refresh the payment list
                            window.location.href = 'payment.php';
                        } else {
                            alert('Failed to add additional fees: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error adding additional fees:', error);
                        submitButton.disabled = false;
                        submitButton.textContent = 'Add Payment';
                        alert('An error occurred while adding the additional fees: ' + error.message);
                    });
                } else {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Add Payment';
                    alert('Payment added successfully.');
                    // Reload the page to refresh the payment list
                    window.location.href = 'payment.php';
                }
            } else {
                submitButton.disabled = false;
                submitButton.textContent = 'Add Payment';
                alert('Failed to add payment. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error adding payment:', error);
            submitButton.disabled = false;
            submitButton.textContent = 'Add Payment';
            alert('An error occurred while adding the payment: ' + error.message);
        });
    });
});
</script>
<script>
        const existingPayment = <?php
            // Fetch additional fees associated with this payment
            $additional_fees = [];
            $fees_result = $conn->query("SELECT title, price, description, registration_date FROM additional_fee WHERE payment_id = " . intval($editing_payment['id']));
            while ($fee = $fees_result->fetch_assoc()) {
                $additional_fees[] = [
                    'title' => $fee['title'],
                    'price' => $fee['price'],
                    'description' => $fee['description'],
                    'registration_date' => $fee['registration_date']
                ];
            }
            // Prepare payment data
            $payment_data = [
                'id' => $editing_payment['id'],
                'tenant_id' => $editing_payment['tenant_id'],
                'payment_method' => $editing_payment['payment_method'],
                'bank_name' => $editing_payment['bank_name'],
                'transaction_no' => $editing_payment['transaction_no'],
                'payment_date' => $editing_payment['payment_date'],
                'deposited_amount' => $editing_payment['deposited_amount'],
                'total' => $editing_payment['total'],
                'withhold' => $editing_payment['withhold'],
                'discount' => $editing_payment['discount'],
                'penality' => $editing_payment['penality'],
                'additional_fees' => $additional_fees
            ];
            echo json_encode($payment_data);
        ?>;
    </script>

    <!-- JavaScript to handle form population and submission -->
    
   <script>
    // Handle Delete Payment
    function deletePayment(paymentId) {
        // Delete payment logic here
        if (confirm('Are you sure you want to delete this payment?')) {
            fetch(`delete_payment.php?id=${paymentId}`, { method: 'DELETE' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Payment deleted successfully.');
                        // Reload the page to refresh the payment list
                        location.reload();
                    } else {
                        alert('Failed to delete payment.');
                    }
                })
                .catch(error => {
                    console.error('Error deleting payment:', error);
                    alert('An error occurred while deleting the payment.');
                });


        }
    }
  
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const aboutTenantForm = document.getElementById('addTenantForm');
        const nextToAboutContractBtn = document.getElementById('nextToAboutContract');
        const aboutContractTab = document.getElementById('about-contract-tab');
        const tenantTabContent = document.getElementById('tenantTabContent');

        // Function to check if all required fields in About Tenant tab are filled
        function validateAboutTenant() {
            const requiredFields = document.querySelectorAll('#about-tenant [required]');
            let allFilled = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    allFilled = false;
                }
            });

            // Enable or disable the Next button based on validation
            nextToAboutContractBtn.disabled = !allFilled;
        }

        // Function to enable the About Contract tab
        function enableAboutContractTab() {
            if (!aboutContractTab.classList.contains('disabled')) return;

            aboutContractTab.classList.remove('disabled');
            aboutContractTab.setAttribute('aria-disabled', 'false');
            aboutContractTab.removeAttribute('tabindex');

            // Initialize Bootstrap Tab instance and show the About Contract tab
            const aboutContractTabInstance = new bootstrap.Tab(aboutContractTab);
            aboutContractTabInstance.show();

            // Disable the Next button after moving to the next tab
            nextToAboutContractBtn.disabled = true;
        }

        // Event listener for input changes in About Tenant tab
        const aboutTenantInputs = document.querySelectorAll('#about-tenant [required]');
        aboutTenantInputs.forEach(input => {
            input.addEventListener('input', validateAboutTenant);
        });

        // Event listener for Next button click
        nextToAboutContractBtn.addEventListener('click', function() {
            enableAboutContractTab();
            // Enable the Documents tab if necessary
            // documentsTab.classList.remove('disabled');
            // documentsTab.setAttribute('aria-disabled', 'false');
            // documentsTab.removeAttribute('tabindex');
        });

        // Disable clicking on About Contract and Documents tabs unless enabled
        const allTabs = document.querySelectorAll('.nav-link');
        allTabs.forEach(tab => {
            tab.addEventListener('click', function(event) {
                if (tab.classList.contains('disabled')) {
                    event.preventDefault();
                    return false;
                }
            });
        });

        // Initial validation check
        validateAboutTenant();
    });
</script>

    <script>

document.getElementById('nextToAboutContract').addEventListener('click', function() {
    var aboutContractTab = new bootstrap.Tab(document.getElementById('about-contract-tab'));
    aboutContractTab.show();
});

   const rooms = <?= json_encode($rooms) ?>;
    const buildings = <?= json_encode($buildings) ?>;
    const floors = <?= json_encode($floors) ?>;



    function populateDropdown(selectElement, items, valueKey, textKey) {
        selectElement.innerHTML = '<option value="">Select</option>';
        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item[valueKey];
            option.textContent = item[textKey];
            selectElement.appendChild(option);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        populateDropdown(document.getElementById('building'), buildings, 'building_name', 'building_name');
        populateDropdown(document.getElementById('floor'), floors, 'number', 'number');
        populateDropdown(document.getElementById('room'), rooms, 'room_no', 'room_no');
    });
</script>
<script>
// JavaScript Code for Add Tenant Functionality

// JavaScript Code for Add Tenant Functionality

document.addEventListener('DOMContentLoaded', function() {
    // Fetch tenants and populate the dropdown
    fetch('get_tenants.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.tenants) {
                const tenantDropdown = document.getElementById('tenant');
                tenantDropdown.innerHTML = '<option value="">Select Tenant</option>'; // Clear existing options
                data.tenants.forEach(tenant => {
                    const option = document.createElement('option');
                    option.value = tenant.id;
                    option.textContent = tenant.name;
                    tenantDropdown.appendChild(option);
                });
            } else {
                console.error('Failed to fetch tenants:', data.message);
                alert('Failed to fetch tenants: ' + (data.message || 'Unknown error.'));
            }
        })
        .catch(error => {
            console.error('Error fetching tenants:', error);
        });

    // Handle Add Tenant Form Submission
    document.getElementById('addTenantForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent the default form submission

        const form = this;
        const formData = new FormData(form);

        fetch('add_tenant.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.tenant) {
                // Add the new tenant to the dropdown
                const tenantDropdown = document.getElementById('tenant');
                const option = document.createElement('option');
                option.value = data.tenant.id;
                option.textContent = data.tenant.name;
                tenantDropdown.appendChild(option);
                
                // Select the new tenant
                tenantDropdown.value = data.tenant.id;

                // Close the Add Tenant Modal using Bootstrap's API
                const addTenantModalElement = document.getElementById('addTenantModal');
                
                // Use getOrCreateInstance for a streamlined approach
                const modal = bootstrap.Modal.getOrCreateInstance(addTenantModalElement);
                modal.hide();

                // Remove the modal backdrop manually if it persists
                const modalBackdrop = document.querySelector('.modal-backdrop');
                if (modalBackdrop) {
                    modalBackdrop.remove();
                }

                // Reset the Add Tenant Form
                form.reset();

                // Optionally, display a success message
                alert('Tenant added successfully!');
            } else {
                // Display the error message from the server
                alert('Failed to add tenant: ' + (data.message || 'Unknown error.'));
                console.error('Add Tenant Error:', data.message);
            }
        })
        .catch(error => {
            console.error('Error adding tenant:', error);
            alert('An error occurred while adding the tenant.');
        });
    });

    // Enable/disable input fields based on checkbox state
    const checkboxes = [
        { checkboxId: 'withheldCheckbox', targetId: 'withhold' },
        { checkboxId: 'discountedCheckbox', targetId: 'discount' },
        { checkboxId: 'penalityCheckbox', targetId: 'penality' }
    ];

    checkboxes.forEach(({ checkboxId, targetId }) => {
        const checkbox = document.getElementById(checkboxId);
        const targetInput = document.getElementById(targetId);
        if (checkbox && targetInput) {
            checkbox.addEventListener('change', function() {
                targetInput.disabled = !this.checked;
            });
        }
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tenantSelect = document.getElementById('tenant');
    const tenantTinNo = document.getElementById('tenant_tin_no');
    const roomSelect = document.getElementById('room');
    const rentDueDate = document.getElementById('rent_due_date');
    const subTotal = document.getElementById('sub_total');
    const subTotals = document.getElementById('sub_totals');
    const rentAmountField = document.getElementById('rent_amount'); // Ensure this element exists

    // Validate the presence of required form fields
    if (!tenantTinNo) console.error('Form field tenant_tin_no is missing in the DOM.');
    if (!roomSelect) console.error('Form field room is missing in the DOM.');
    if (!rentDueDate) console.error('Form field rent_due_date is missing in the DOM.');
    if (!subTotal) console.error('Form field sub_total is missing in the DOM.');
    if (!subTotals) console.error('Form field sub_totals is missing in the DOM.');
    if (!rentAmountField) console.error('Form field rent_amount is missing in the DOM.');

    // If any required elements are missing, exit the function to prevent errors
    if (!tenantTinNo || !roomSelect || !rentDueDate || !subTotal || !subTotals || !rentAmountField) {
        console.error('One or more required form fields are missing. Aborting tenant detail population.');
        return;
    }

    // Event listener for tenant selection changes
    tenantSelect.addEventListener('change', function() {
        const tenantId = this.value;

        if (tenantId) {
            const url = `get_tenants.php?tenant_id=${tenantId}`;
            console.log('Fetching tenant data from URL:', url);

            fetch(url)
                .then(response => {
                    const contentType = response.headers.get('Content-Type');
                    if (!response.ok) {
                        throw new Error(`Network response was not ok (Status: ${response.status})`);
                    }
                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    } else {
                        throw new Error(`Unexpected content type: ${contentType}`);
                    }
                })
                .then(data => {
                    console.log('Raw response:', data);
                    if (data.success === true && data.tenant) {
                        const tenant = data.tenant;

                        // Populate form fields with tenant data
                        tenantTinNo.value = tenant.tin_no || '';
                        roomSelect.value = tenant.room || '';
                        rentDueDate.value = tenant.rent_due_date || '';

                        // Ensure rent_amount is correctly handled
                        const rentAmount = parseFloat(tenant.rent_amount) || 0;
                        const termOfPayment = parseFloat(tenant.term_of_payment) || 0;
                        const calculatedSubTotal = rentAmount * termOfPayment;

                        rentAmountField.value = rentAmount.toFixed(2);
                        subTotal.value = calculatedSubTotal.toFixed(2);
                        subTotals.value = calculatedSubTotal.toFixed(2);

                        console.log('Form fields populated successfully:', {
                            tenantTinNo: tenantTinNo.value,
                            roomSelect: roomSelect.value,
                            rentDueDate: rentDueDate.value,
                            rentAmount: rentAmountField.value,
                            subTotal: subTotal.value,
                            subTotals: subTotals.value
                        });

                        // Optionally, trigger any additional calculations or UI updates here
                        calculateFields();
                    } else {
                        // Handle server-side errors with detailed messages
                        const errorMessage = data.message || 'Failed to retrieve tenant details due to an unknown error.';
                        console.warn('Failed to retrieve tenant details:', errorMessage);
                        alert(`Failed to retrieve tenant details: ${errorMessage}`);
                        clearTenantDetails();
                    }
                })
                .catch(error => {
                    // Handle network or parsing errors
                    console.error('Error fetching tenant data:', error);
                    alert(`An error occurred while fetching tenant details: ${error.message}`);
                    clearTenantDetails();
                });
        } else {
            // If no tenant is selected, clear the form fields
            clearTenantDetails();
        }
    });

    /**
     * Clears tenant-related form fields.
     */
    function clearTenantDetails() {
        tenantTinNo.value = '';
        roomSelect.value = '';
        rentDueDate.value = '';
        rentAmountField.value = '0.00';
        subTotal.value = '0.00';
        subTotals.value = '0.00';
        calculateFields(); // Recalculate totals if necessary
    }

    /**
     * Optional: Function to perform additional calculations or UI updates.
     * Implement as needed based on your application's requirements.
     */
    function calculateFields() {
        // Example calculation logic
        // Update other fields or perform validations here
    }
});
</script>
  </body>
</html>