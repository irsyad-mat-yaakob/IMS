<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['userID']) || (strtolower($_SESSION['usertype']) != 'administrator' && strtolower($_SESSION['usertype']) != 'admin')) {
    header("Location: ../login.php");
    exit();
}

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Inventory Management System</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <!-- Top Navigation Bar -->
    <div class="top-nav">
        <div class="user-name">
            <?php echo $_SESSION['name']; ?>
        </div>
        <div class="system-name">
            Inventory Management System
        </div>
        <div class="top-nav-right">
            <div class="top-notification">
                <a href="notifications.php"><i class="fas fa-bell"></i></a>
            </div>
            <div class="logout-btn">
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="main-container">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <ul class="nav-menu">
                <li class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                    <a href="index.php"><i class="fas fa-home"></i> Home</a>
                </li>
                <li class="<?php echo ($current_page == 'inventory.php') ? 'active' : ''; ?>">
                    <a href="inventory.php"><i class="fas fa-box"></i> Inventory</a>
                </li>
                <li class="<?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">
                    <a href="users.php"><i class="fas fa-users"></i> Users</a>
                </li>
                <li class="<?php echo ($current_page == 'sales.php') ? 'active' : ''; ?>">
                    <a href="sales.php"><i class="fas fa-dollar-sign"></i> Sales</a>
                </li>
                <li class="<?php echo ($current_page == 'purchases.php') ? 'active' : ''; ?>">
                    <a href="purchases.php"><i class="fas fa-shopping-cart"></i> Purchases</a>
                </li>
                <li class="<?php echo ($current_page == 'supplier.php') ? 'active' : ''; ?>">
                    <a href="supplier.php"><i class="fas fa-truck"></i> Supplier</a>
                </li>
                <li class="<?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">
                    <a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a>
                </li>
                <li class="<?php echo ($current_page == 'report.php') ? 'active' : ''; ?>">
                    <a href="report.php"><i class="fas fa-chart-bar"></i> Report</a>
                </li>
            </ul>
            
        
        </div>

        <!-- Main Content Area -->
        <div class="content">
            <!-- Page header -->
            <div class="page-header">
                <h2><?php 
                    // Display page title based on current page
                    switch($current_page) {
                        case 'index.php':
                            echo 'Home';
                            break;
                        case 'inventory.php':
                            echo 'Inventory';
                            break;
                        case 'users.php':
                            echo 'Users';
                            break;
                        case 'sales.php':
                            echo 'Sales';
                            break;
                        case 'purchases.php':
                            echo 'Purchases';
                            break;
                        case 'supplier.php':
                            echo 'Supplier';
                            break;
                        case 'notifications.php':
                            echo 'Notifications';
                            break;
                        case 'report.php':
                            echo 'Report';
                            break;
                        default:
                            echo 'Dashboard';
                    }
                ?></h2>
            </div>
            
            <!-- Content will be added here from individual pages -->