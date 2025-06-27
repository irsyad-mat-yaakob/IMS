<?php
// Include header
include 'headers.php';

// Include database connection
include '../config/db_connection.php';

// Initialize message variable
$message = "";

// Get the current action (view or list)
$current_action = isset($_GET['action']) ? $_GET['action'] : 'list';
$report_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : null;

// Handle Generate Report
if(isset($_POST['generate_report'])) {
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);
    
    // Validate dates
    if(empty($start_date) || empty($end_date)) {
        $message = "<div class='alert alert-danger'>Please select both start and end dates.</div>";
    } else if(strtotime($end_date) < strtotime($start_date)) {
        $message = "<div class='alert alert-danger'>End date cannot be earlier than start date.</div>";
    } else {
        // Get total sales for the period
        $sales_query = "SELECT SUM(revenue) as total_sales FROM Sales WHERE date BETWEEN '$start_date' AND '$end_date'";
        $sales_result = mysqli_query($conn, $sales_query);
        $sales_data = mysqli_fetch_assoc($sales_result);
        $total_sales = $sales_data['total_sales'] ? $sales_data['total_sales'] : 0;
        
        // Get total purchases for the period
        $purchases_query = "SELECT SUM(totalCost) as total_purchases FROM PurchaseOrder WHERE date BETWEEN '$start_date' AND '$end_date'";
        $purchases_result = mysqli_query($conn, $purchases_query);
        $purchases_data = mysqli_fetch_assoc($purchases_result);
        $total_purchases = $purchases_data['total_purchases'] ? $purchases_data['total_purchases'] : 0;
        
        // Calculate profit
        $profit = $total_sales - $total_purchases;
        
        // Get a random item ID for the report (this is a simplification)
        $item_query = "SELECT itemID FROM Item LIMIT 1";
        $item_result = mysqli_query($conn, $item_query);
        $item_data = mysqli_fetch_assoc($item_result);
        $item_id = $item_data ? $item_data['itemID'] : 1;
        
        // Get a random stock code for the report (this is a simplification)
        $stock_query = "SELECT stockCode FROM Stock LIMIT 1";
        $stock_result = mysqli_query($conn, $stock_query);
        $stock_data = mysqli_fetch_assoc($stock_result);
        $stock_code = $stock_data ? $stock_data['stockCode'] : 'STK-001';
        
        // Get a random purchase order ID for the report (this is a simplification)
        $po_query = "SELECT poID FROM PurchaseOrder LIMIT 1";
        $po_result = mysqli_query($conn, $po_query);
        $po_data = mysqli_fetch_assoc($po_result);
        $po_id = $po_data ? $po_data['poID'] : 1;
        
        // Create report in database
        $insert_query = "INSERT INTO Report (startDate, endDate, totalPurchase, totalSales, profitfromSales, itemID, stockCode, poID, userID) 
                         VALUES ('$start_date', '$end_date', '$total_purchases', '$total_sales', '$profit', '$item_id', '$stock_code', '$po_id', '{$_SESSION['userID']}')";
        
        if(mysqli_query($conn, $insert_query)) {
            $report_id = mysqli_insert_id($conn);
            $message = "<div class='alert alert-success'>Report generated successfully.</div>";
            
            // Redirect to view the generated report
            header("Location: report.php?action=view&id=$report_id");
            exit();
        } else {
            $message = "<div class='alert alert-danger'>Error generating report: " . mysqli_error($conn) . "</div>";
        }
    }
}

// Get report details if viewing
$report_details = null;
if($current_action == 'view' && $report_id) {
    $report_query = "SELECT r.*, u.name as username 
                    FROM Report r 
                    JOIN User u ON r.userID = u.userID 
                    WHERE r.reportID = '$report_id'";
    $report_result = mysqli_query($conn, $report_query);
    
    if($report_result && mysqli_num_rows($report_result) > 0) {
        $report_details = mysqli_fetch_assoc($report_result);
    } else {
        // Report not found
        $message = "<div class='alert alert-danger'>Report not found.</div>";
        $current_action = 'list';
    }
}

// Fetch all reports
$reports_query = "SELECT r.*, u.name as username 
                 FROM Report r 
                 JOIN User u ON r.userID = u.userID 
                 ORDER BY r.reportID DESC";
$reports_result = mysqli_query($conn, $reports_query);
?>

<!-- Main content area -->
<div class="report-content">
   
    
    <?php if(!empty($message)): ?>
        <?php echo $message; ?>
    <?php endif; ?>
    
    <?php if($current_action == 'view' && $report_details): ?>
        <!-- View Report Details -->
        <div class="report-details-container">
            <h3>View Report</h3>
            <h4>Report <?php echo $report_id; ?></h4>
            
            <div class="report-table">
                <div class="report-table-row header">
                    <div class="report-column-detail">Details</div>
                    <div class="report-column-value"></div>
                </div>
                
                <div class="report-table-row">
                    <div class="report-column-detail">Total Sales</div>
                    <div class="report-column-value">RM<?php echo number_format($report_details['totalSales'], 2); ?></div>
                </div>
                
                <div class="report-table-row">
                    <div class="report-column-detail">Total Purchases</div>
                    <div class="report-column-value">RM<?php echo number_format($report_details['totalPurchase'], 2); ?></div>
                </div>
                
                <div class="report-table-row">
                    <div class="report-column-detail">Profit from Sales</div>
                    <div class="report-column-value"><?php echo ($report_details['profitfromSales'] >= 0 ? 'RM' : 'RM-') . number_format(abs($report_details['profitfromSales']), 2); ?></div>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="report.php" class="back-button">Back to Reports</a>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Report Generation Form and Reports List -->
        <div class="report-form-container">
            <!-- Generate Report Form -->
            <form method="post" action="report.php" class="report-form">
                <div class="date-inputs">
                    <input type="date" name="start_date" class="form-control date-input" placeholder="Start Date" required>
                    <input type="date" name="end_date" class="form-control date-input" placeholder="End Date" required>
                </div>
                
                <div class="generate-btn-container">
                    <button type="submit" name="generate_report" class="generate-btn">Generate Report</button>
                </div>
            </form>
            
            <!-- Reports List -->
            <div class="reports-list-container">
                <div class="reports-list-table">
                    <div class="reports-list-header">
                        <div class="reports-column-id">ID</div>
                        <div class="reports-column-start-date">Start Date</div>
                        <div class="reports-column-end-date">Start Date</div>
                        <div class="reports-column-by">By</div>
                        <div class="reports-column-actions"></div>
                    </div>
                    
                    <?php if(mysqli_num_rows($reports_result) > 0): ?>
                        <?php $counter = 1; ?>
                        <?php while($report = mysqli_fetch_assoc($reports_result)): ?>
                            <div class="reports-list-row">
                                <div class="reports-column-id"><?php echo $counter++; ?></div>
                                <div class="reports-column-start-date"><?php echo date('Y/m/d', strtotime($report['startDate'])); ?></div>
                                <div class="reports-column-end-date"><?php echo date('Y/m/d', strtotime($report['endDate'])); ?></div>
                                <div class="reports-column-by"><?php echo $report['username']; ?></div>
                                <div class="reports-column-actions">
                                    <a href="report.php?action=view&id=<?php echo $report['reportID']; ?>" class="view-report-icon">
                                        <i class="fas fa-receipt"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="reports-list-row">
                            <div class="no-reports">No reports found</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Close the content div and body/html tags
echo '</div>';
echo '</div>';
echo '</body>';
echo '</html>';
?>