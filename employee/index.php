<?php
// Include header
include 'headers.php';

// Include database connection
include '../config/db_connection.php';

// Get some summary stats
$total_items = 0;
$total_sales = 0;
$total_purchases = 0;

// Count total items
$items_query = "SELECT COUNT(*) as item_count FROM Item";
$items_result = mysqli_query($conn, $items_query);
if($items_result) {
    $items_data = mysqli_fetch_assoc($items_result);
    $total_items = $items_data['item_count'];
}

// Get total sales
$sales_query = "SELECT SUM(revenue) as total_revenue FROM Sales";
$sales_result = mysqli_query($conn, $sales_query);
if($sales_result) {
    $sales_data = mysqli_fetch_assoc($sales_result);
    $total_sales = $sales_data['total_revenue'] ? $sales_data['total_revenue'] : 0;
}

// Get total purchases
$purchases_query = "SELECT SUM(totalCost) as total_cost FROM PurchaseOrder";
$purchases_result = mysqli_query($conn, $purchases_query);
if($purchases_result) {
    $purchases_data = mysqli_fetch_assoc($purchases_result);
    $total_purchases = $purchases_data['total_cost'] ? $purchases_data['total_cost'] : 0;
}
?>

<!-- Main content -->
<div class="content-card">
    <h3>Welcome to Inventory Management System</h3>
    <p>This is the employee dashboard. You can manage inventory, sales, purchases, and reports from the navigation menu.</p>
</div>

<!-- Dashboard summary cards -->
<div class="dashboard-summary">
    <div class="summary-card">
        <i class="fas fa-box"></i>
        <div class="summary-info">
            <h4>Total Items</h4>
            <p><?php echo $total_items; ?></p>
        </div>
    </div>
    
    <div class="summary-card">
        <i class="fas fa-shopping-cart"></i>
        <div class="summary-info">
            <h4>Total Sales</h4>
            <p>RM <?php echo number_format($total_sales, 2); ?></p>
        </div>
    </div>
    
    <div class="summary-card">
        <i class="fas fa-truck"></i>
        <div class="summary-info">
            <h4>Total Purchases</h4>
            <p>RM <?php echo number_format($total_purchases, 2); ?></p>
        </div>
    </div>
</div>

<!-- Recent Sales Table -->
<div class="content-card mt-4">
    <h3><i class="fas fa-history"></i> Recent Sales</h3>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Date</th>
                <th>Revenue</th>
                <th>Created By</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Get recent sales
            $recent_sales_query = "SELECT s.*, u.name FROM Sales s 
                                  JOIN User u ON s.userID = u.userID 
                                  ORDER BY s.salesID DESC LIMIT 5";
            $recent_sales_result = mysqli_query($conn, $recent_sales_query);
            
            if(mysqli_num_rows($recent_sales_result) > 0) {
                while($sale = mysqli_fetch_assoc($recent_sales_result)) {
                    echo "<tr>";
                    echo "<td>" . date('d M Y', strtotime($sale['date'])) . "</td>";
                    echo "<td>RM " . number_format($sale['revenue'], 2) . "</td>";
                    echo "<td>" . $sale['name'] . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='3' class='text-center'>No sales records found</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<?php
// Close the content div and body/html tags
echo '</div>';
echo '</div>';
echo '</body>';
echo '</html>';
?>