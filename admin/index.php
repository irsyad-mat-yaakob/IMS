<?php
// Include header
include 'headers.php';
?>

<!-- Main content -->
<div class="content-card">
    <h3>Welcome to Inventory Management System</h3>
    <p>This is the administrator dashboard. You can manage inventory, users, sales, purchases, suppliers, and reports from the navigation menu.</p>
</div>

<!-- Dashboard summary cards -->
<div class="dashboard-summary">
    <div class="summary-card">
        <i class="fas fa-box"></i>
        <div class="summary-info">
            <h4>Total Items</h4>
            <p>0</p>
        </div>
    </div>
    
    <div class="summary-card">
        <i class="fas fa-users"></i>
        <div class="summary-info">
            <h4>Total Users</h4>
            <p>1</p>
        </div>
    </div>
    
    <div class="summary-card">
        <i class="fas fa-shopping-cart"></i>
        <div class="summary-info">
            <h4>Total Sales</h4>
            <p>0</p>
        </div>
    </div>
    
    <div class="summary-card">
        <i class="fas fa-truck"></i>
        <div class="summary-info">
            <h4>Total Suppliers</h4>
            <p>0</p>
        </div>
    </div>
</div>

<?php
// Close the content div and body/html tags
echo '</div>';
echo '</div>';
echo '</body>';
echo '</html>';
?>