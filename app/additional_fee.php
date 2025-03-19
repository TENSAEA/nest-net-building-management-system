<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: auth/sign-in.php");
    exit();
}
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user'; // Default to 'user' if role is not set
 include '../db.php'; // Updated path to the database connection file
 
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
 $limit = 10; // Number of entries to show in a page.
 if (isset($_GET["page"])) {
     $page = $_GET["page"];
 } else {
     $page = 1;
 }
 $start_from = ($page - 1) * $limit;
 
// Search logic
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_param = '%' . $search . '%';

// Updated query to fetch additional fees with search functionality and pagination
$query = "
    SELECT 
        *
    FROM 
        additional_fee
    WHERE 
        title LIKE ? OR
        description LIKE ? OR
        price LIKE ? OR
        registration_date LIKE ? OR
        payment_id LIKE ?
    ORDER BY 
        id ASC 
    LIMIT ?, ?
";

if ($stmt = $conn->prepare($query)) {
    // Bind parameters: 'ssssi' => 5 strings for search, 2 integers for LIMIT
    $stmt->bind_param("sssssii", $search_param, $search_param, $search_param, $search_param, $search_param, $start_from, $limit);
    
    // Execute the statement
    if ($stmt->execute()) {
        // Get the result
        $result = $stmt->get_result();
        $additional_fees = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        // Handle execution errors
        echo "Error executing query: " . htmlspecialchars($stmt->error, ENT_QUOTES, 'UTF-8');
        exit();
    }
    // Close the statement
    $stmt->close();
} else {
    // Handle preparation errors
    echo "Error preparing statement: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8');
    exit();
}

 // Get total number of records
 $sql = "SELECT COUNT(id) FROM additional_fee";
 $result = $conn->query($sql);
 $row = $result->fetch_row();
 $total_records = $row[0];
 $total_pages = ceil($total_records / $limit);

 // Fetch payment IDs from the payment table
$payment_ids = [];
$query = "SELECT id FROM payment";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $payment_ids[] = $row['id'];
    }
} else {
    echo "Error fetching payment IDs: " . mysqli_error($conn);
}
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
.card{
  margin-top:2em;
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
                        <a class="nav-link" aria-current="page" href="./payment.php">
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
                        <a class="nav-link active"  href="#">
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
            <form method="GET" action="additional_fee.php" class="input-group search-input">
    <span class="input-group-text" id="search-input">
        <svg class="icon-18" width="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="11.7669" cy="11.7666" r="8.98856" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></circle>
            <path d="M18.0186 18.4851L21.5426 22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
        </svg>
    </span>
    <input type="search" class="form-control" name="search" placeholder="Search..." value="<?= htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit" class="btn btn-primary">Search</button>
</form>
        </nav>          <!-- Nav Header Component Start -->
     
         
           
          
                    <div class="container-fluid">
            <div class="card">
           <div class="card-header d-flex justify-content-between">
                       <h4 class="mt-4">Additional Fee</h4>
                       <?php if ($role === 'admin'): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdditionalFeeModal">Add Additional Fee</button>
                    <?php endif; ?>
                    </div>
               <div class="card-body">
                   <div class="table-responsive">
                   <table class="table table-striped">
    <thead>
        <tr>
            <th>No</th>
            <th>Actions</th>
            <th>Print</th>
            <th>Title</th>
            <th>Price</th>
            <th>Description</th>
            <th>Registration Date</th>
            <th>Payment ID</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $i = $start_from + 1;
        foreach ($additional_fees as $fee) {
            echo "<tr>";
            echo "<td data-label='No'>{$i}</td>"; // Incremental number
            echo "<td data-label='Actions'>";

            if ($role === 'admin') {
                echo "<button class='btn btn-sm btn-primary' data-bs-toggle='modal' data-bs-target='#editAdditionalFeeModal' data-id='" . htmlspecialchars($fee['id'] ?? '', ENT_QUOTES, 'UTF-8') . "'>
                        <i class='fas fa-edit'></i>
                      </button>
                      <button class='btn btn-sm btn-danger' onclick='deleteAdditionalFee(" . htmlspecialchars($fee['id'] ?? '', ENT_QUOTES, 'UTF-8') . ")'>
                        <i class='fas fa-trash'></i>
                      </button>";
            }
            
            echo "</td>";
            
            echo "<td>
            <!-- Other action buttons like Edit/Delete -->
            <button class='btn btn-primary' onclick='printAdditionalFee(" . htmlspecialchars($fee['id'], ENT_QUOTES, 'UTF-8') . ")'>Print</button>
          </td>";
            echo "<td data-label='Title'>" . htmlspecialchars($fee['title'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td data-label='Price'>" . htmlspecialchars($fee['price'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td data-label='Description'>" . htmlspecialchars($fee['description'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td data-label='Registration Date'>" . htmlspecialchars($fee['registration_date'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td data-label='Payment ID'>" . htmlspecialchars($fee['payment_id'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>";
            echo "</tr>";
            $i++;
        }
        ?>
    </tbody>
</table>
                   </div>
                   <nav aria-label="Page navigation example">
                       <ul class="pagination justify-content-center flex-wrap">
                           <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                               <a class="page-link" href="<?= ($page <= 1) ? '#' : '?page=' . ($page - 1) ?>">Previous</a>
                           </li>
                           <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                               <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                   <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                               </li>
                           <?php endfor; ?>
                           <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                               <a class="page-link" href="<?= ($page >= $total_pages) ? '#' : '?page=' . ($page + 1) ?>">Next</a>
                           </li>
                       </ul>
                   </nav>
               </div>
           </div>
       </div>
 <!-- Add Additional Fee Modal -->
<div class="modal fade" id="addAdditionalFeeModal" tabindex="-1" aria-labelledby="addAdditionalFeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAdditionalFeeModalLabel">Add Additional Fee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addAdditionalFeeForm">
                    <!-- Title -->
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <!-- Price -->
                    <div class="mb-3">
                        <label for="price" class="form-label">Price</label>
                        <input type="number" class="form-control" id="price" name="price" required>
                    </div>
                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" required></textarea>
                    </div>
                    <!-- Registration Date -->
                    <div class="mb-3">
                        <label for="registration_date" class="form-label">Registration Date</label>
                        <input type="date" class="form-control" id="registration_date" name="registration_date" required>
                    </div>
                    <!-- Payment ID -->
                    <div class="mb-3">
                        <label for="payment_id" class="form-label">Payment ID</label>
                        <select class="form-select" id="payment_id" name="payment_id" required>
                            <option value="">Select Payment ID</option>
                            <?php foreach ($payment_ids as $payment_id): ?>
                                <option value="<?= htmlspecialchars($payment_id, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($payment_id, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary">Add Additional Fee</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Additional Fee Modal -->
<div class="modal fade" id="editAdditionalFeeModal" tabindex="-1" aria-labelledby="editAdditionalFeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAdditionalFeeModalLabel">Edit Additional Fee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editAdditionalFeeForm">
                    <input type="hidden" id="edit_additional_fee_id" name="id">
                    <!-- Title -->
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>
                    <!-- Price -->
                    <div class="mb-3">
                        <label for="edit_price" class="form-label">Price</label>
                        <input type="number" class="form-control" id="edit_price" name="price" required>
                    </div>
                    <!-- Description -->
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" required></textarea>
                    </div>
                    <!-- Registration Date -->
                    <div class="mb-3">
                        <label for="edit_registration_date" class="form-label">Registration Date</label>
                        <input type="date" class="form-control" id="edit_registration_date" name="registration_date" required>
                    </div>
                    <!-- Payment ID -->
                    <div class="mb-3">
                        <label for="edit_payment_id" class="form-label">Payment ID</label>
                        <select class="form-select" id="edit_payment_id" name="payment_id" required>
                            <option value="">Select Payment ID</option>
                            <?php foreach ($payment_ids as $payment_id): ?>
                                <option value="<?= htmlspecialchars($payment_id, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($payment_id, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary">Update Additional Fee</button>
                </form>
            </div>
        </div>
    </div>
</div>

     
         
         
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
    <script>
      // Handle Add Additional Fee Form Submission
document.getElementById('addAdditionalFeeForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('add_additional_fee.php', {
        method: 'POST',
        body: JSON.stringify({
            payment_id: formData.get('payment_id'),
            additional_fees: [{
                title: formData.get('title'),
                price: formData.get('price'),
                description: formData.get('description'),
                registration_date: formData.get('registration_date')
            }]
        }),
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Additional Fee added successfully.');
            location.reload();
        } else {
            console.error('Error adding additional fee:', data.message);
            alert('Failed to add additional fee: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error adding additional fee:', error);
        alert('An error occurred while adding the additional fee.');
    });
});
 
       // Handle Edit Additional Fee Modal Show Event
       document.getElementById('editAdditionalFeeModal').addEventListener('show.bs.modal', function (event) {
           var button = event.relatedTarget;
           var feeId = button.getAttribute('data-id');
           // Fetch additional fee data and populate the form fields
           fetchAdditionalFeeData(feeId);
       });
 
       // Handle Edit Additional Fee Form Submission
       document.getElementById('editAdditionalFeeForm').addEventListener('submit', function (e) {
           e.preventDefault();
           const formData = new FormData(this);
           fetch('edit_additional_fee.php', {
               method: 'POST',
               body: formData
           })
           .then(response => response.json())
           .then(data => {
               if (data.success) {
                   alert('Additional Fee updated successfully.');
                   location.reload();
               } else {
                   alert('Failed to update additional fee. Please try again.');
               }
           })
           .catch(error => {
               console.error('Error updating additional fee:', error);
               alert('An error occurred while updating the additional fee.');
           });
       });
 
       // Function to Fetch Additional Fee Data and Populate the Edit Form
       function fetchAdditionalFeeData(feeId) {
           fetch(`get_additional_fee.php?id=${feeId}`)
               .then(response => response.json())
               .then(data => {
                   if (data) {
                       document.getElementById('edit_additional_fee_id').value = data.id;
                       document.getElementById('edit_title').value = data.title;
                       document.getElementById('edit_price').value = data.price;
                       document.getElementById('edit_description').value = data.description;
                       document.getElementById('edit_registration_date').value = data.registration_date;
                       document.getElementById('edit_payment_id').value = data.payment_id;
                   } else {
                       alert('Failed to fetch additional fee data.');
                   }
               })
               .catch(error => {
                   console.error('Error fetching additional fee data:', error);
                   alert('An error occurred while fetching the additional fee data.');
               });
       }
 
       // Handle Delete Additional Fee
       function deleteAdditionalFee(feeId) {
           if (confirm('Are you sure you want to delete this additional fee?')) {
               fetch(`delete_additional_fee.php`, {
                   method: 'DELETE',
                   headers: {
                       'Content-Type': 'application/x-www-form-urlencoded',
                   },
                   body: `id=${feeId}`
               })
               .then(response => response.json())
               .then(data => {
                   if (data.success) {
                       alert('Additional Fee deleted successfully.');
                       location.reload();
                   } else {
                       alert('Failed to delete additional fee.');
                       console.error('Error:', data.error);
                   }
               })
               .catch(error => {
                   console.error('Error deleting additional fee:', error);
                   alert('An error occurred while deleting the additional fee.');
               });
           }
       }
       </script>
     <script>
    /**
     * Function to print additional fee details.
     * @param {number} feeId - The ID of the additional fee to print.
     */
    function printAdditionalFee(feeId) {
        // Fetch additional fee details based on feeId
        fetch(`get_additional_fee_details.php?id=${feeId}`)
            .then(response => response.text())
            .then(text => {
                console.log('Raw response from server:', text);
                try {
                    const data = JSON.parse(text);
                    
                    if (data.error) {
                        console.error('Error from server:', data.error);
                        alert(`Failed to fetch additional fee details: ${data.error}`);
                        return;
                    }
                    
                    console.log('Additional Fee Data:', data);
                    
                    const additionalFees = data.additional_fees;
                    const paymentDetails = data.payment_details;
                    
                    if (!Array.isArray(additionalFees) || additionalFees.length === 0) {
                        alert('No additional fees found for the selected record.');
                        return;
                    }
                    
                    // Calculate the sum of prices
                    const totalPrice = additionalFees.reduce((sum, fee) => sum + parseFloat(fee.price), 0);
                    
                    // Create a new window for printing
                    const printWindow = window.open('', '', 'height=800,width=800');
                    const currentDate = new Date();
                    const formattedDate = currentDate.toLocaleDateString('en-GB', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric'
                    });
                    
                    // Prepare content for printing
                    printWindow.document.write('<html><head><title>Print Additional Fee</title>');
                    printWindow.document.write('<style>');
                    // Add your CSS styles here (same as in printPayment function)
                    printWindow.document.write(`
                        body { font-family: "Times New Roman", Times, serif; font-size: 12px; margin: 0; padding: 0; }
                        .invoice-container { width: 100%; padding: 20px; box-sizing: border-box; }
                        .image-container { display: flex; align-items: center; padding: 10px 0; }
                        .header-wrapper { display: flex; justify-content: space-between; margin-bottom: 10px; }
                        .header-wrapperr { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; border-bottom: 2px solid #ddd; padding-bottom: 5px; }
                        .company-info, .receipt-info { margin-bottom: 5px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                        th, td { border: 1px solid black; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        .no-border { border: none !important; }
                        .additional-info { display: flex; justify-content: space-between; margin-top: 20px; font-size: 12px; }
                        @media print {
                            .button-container { display: none; }
                            @page { margin: 20mm; }
                        }
                    `);
                    printWindow.document.write('</style>');
                    printWindow.document.write('</head><body>');
                    
                    printWindow.document.write('<div class="invoice-container">');
                    printWindow.document.write('<div class="image-container">');
                    
                    // **Embedded SVG Logo**
                    printWindow.document.write(`
                        <!-- Replace this SVG with your actual SVG content -->
                        <svg fill="#000000" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" 
                             xmlns:xlink="http://www.w3.org/1999/xlink" width="80px" height="80px" 
                             viewBox="0 0 980 980" xml:space="preserve">
                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                            <g id="SVGRepo_iconCarrier">
                                <!-- Your SVG paths go here -->
                                <path d="M970.001,868.211h-25.659V210.402c0-5.523-4.477-10-10-10H43.662c-5.523,0-10,4.477-10,10v657.809H10 
                                    c-5.523,0-10,4.479-10,10v26.984c0,5.521,4.477,10,10,10h960c5.523,0,10-4.479,10-10v-26.984 
                                    C980.001,872.688,975.523,868.211,970.001,868.211z M121.265,798.182c0,5.521-4.477,10-10,10H70.816 
                                    c-5.523,0-10-4.479-10-10 v-40.449c0-5.523,4.477-10,10-10h40.449c5.523,0,10,4.477,10,10V798.182z 
                                    ..."></path>
                            </g>
                        </svg>
                    `);
                    
                    printWindow.document.write('<h1 style="margin: 0; font-size: 15px; font-weight: bold;">Nest-Net1>');
                    
                    // Button Container (Hidden in Print)
                    printWindow.document.write('<div class="button-container">');
                    printWindow.document.write('<a href="v_invoice.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>');
                    printWindow.document.write('<button type="button" onclick="window.print()" class="btn btn-primary"><i class="fas fa-file-pdf"></i> Print</button>');
                    printWindow.document.write('</div>');
                    
                    printWindow.document.write('</div>'); // Close image-container
                    
                    // Header Wrapperr
                    printWindow.document.write('<div class="header-wrapperr">');
                    printWindow.document.write('<div class="header-in"><p style="font-size: 15px; font-weight: bold;">Additional Fee Attachment</p></div>');
                    printWindow.document.write('<div class="reduce-line-space">');
                    printWindow.document.write('<p>Record No#: ' + feeId + '</p>');
                    // Assuming you might have fs_number or other fields; adjust as necessary
                    printWindow.document.write('<p>FS No#: ' + paymentDetails.fs_number + '</p>');
                    printWindow.document.write('<p>Date: ' + formattedDate + '</p>');
                    printWindow.document.write('</div></div>');
                    
                    // Header Wrapper
                    printWindow.document.write('<div class="header-wrapper">');
                    
                    // Company Info
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
                    
                    // Receipt Info
                    printWindow.document.write('<div class="header-info">');
                    printWindow.document.write('<div class="receipt-info">');
                    printWindow.document.write('<p>To: ' + paymentDetails.tenant + '</p>');
                    printWindow.document.write('<p>Customer TIN: ' + paymentDetails.tenant_tin + '</p>');
                    printWindow.document.write('<p>Room: ' + paymentDetails.room + '</p>');
                    printWindow.document.write('<p>FS Number: ' + paymentDetails.fs_number + '</p>');
                    printWindow.document.write('<p>Payment Date: ' + paymentDetails.payment_date + '</p>');
                    printWindow.document.write('</div></div></div>');
                    
                    // Details Container
                    printWindow.document.write('<div class="details-container">');
                    
                    // Invoice Details
                    printWindow.document.write('<div class="invoice-details" style="width: 100%;">');
                    printWindow.document.write('<h2 style="text-align: center; font-size: 15px; font-weight: bold;">Invoice Details</h2>');
                    printWindow.document.write('<table>');
                    printWindow.document.write('<tr><th style="text-align: center;">Description</th><th style="text-align: center;">Registration Date</th><th style="text-align: center;">Price</th></tr>');
                    
                    additionalFees.forEach(fee => {
                        printWindow.document.write('<tr><td>' + fee.title + '</td><td>' + fee.registration_date + '</td><td>' + parseFloat(fee.price).toFixed(2) + '</td></tr>');
                    });
                    
                    // Total Price
                    printWindow.document.write('<tr><td colspan="2" class="no-border" style="text-align: right;">Total</td><td style="text-align: center;">' + totalPrice.toFixed(2) + '</td></tr>');
                    
                    printWindow.document.write('</table></div></div>');
                    
                    // Additional Information
                    printWindow.document.write('<div class="additional-info" style="margin-top: 20px;">');
                    printWindow.document.write('<div>Payment Method: ' + paymentDetails.payment_method + '</div>');
                    printWindow.document.write('<div>Transaction No: ' + paymentDetails.transaction_no + '</div>');
                    printWindow.document.write('<div>User: ' + paymentDetails.received_by + '</div>');
                    printWindow.document.write('</div></div>');
                    
                    printWindow.document.write('</body></html>');
                    printWindow.document.close();
                    printWindow.focus();
                    printWindow.print();
                    printWindow.close();
                    
                } catch (error) {
                    console.error('Error parsing JSON:', error);
                    alert(`An error occurred while processing additional fee details: ${error.message}`);
                }
            })
            .catch(error => {
                console.error('Error fetching additional fee details:', error);
                alert(`An error occurred while fetching additional fee details: ${error.message}`);
            });
    }
</script>
  </body>
</html>