<?php
// Include header
include 'headers.php';

// Include database connection
include '../config/db_connection.php';

// Initialize message variable
$message = "";

// Get the current action (view or list)
$current_action = isset($_GET['action']) ? $_GET['action'] : 'list';
$purchase_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : null;

// Get purchase details if viewing
$purchase_details = [];
if($current_action == 'view' && $purchase_id) {
    // Get purchase order information
    $purchase_query = "SELECT po.*, sp.supplierID, s.supplierName 
                      FROM PurchaseOrder po 
                      JOIN Supplier_PurchaseOrder sp ON po.poID = sp.poID 
                      JOIN Supplier s ON sp.supplierID = s.supplierID 
                      WHERE po.poID = '$purchase_id'";
    $purchase_result = mysqli_query($conn, $purchase_query);
    
    if($purchase_result && mysqli_num_rows($purchase_result) > 0) {
        $purchase_info = mysqli_fetch_assoc($purchase_result);
        
        // Get items for this purchase (based on stock code)
        $stock_code = $purchase_info['stockCode'];
        $items_query = "SELECT i.itemID, i.itemName, i.sellPrice 
                       FROM Item i 
                       WHERE i.stockCode = '$stock_code'";
        $items_result = mysqli_query($conn, $items_query);
        
        if($items_result) {
            // Distribute total quantity evenly among items
            $total_quantity = $purchase_info['quantity'];
            $item_count = mysqli_num_rows($items_result);
            
            if($item_count > 0) {
                $base_quantity = floor($total_quantity / $item_count);
                $remainder = $total_quantity % $item_count;
                
                $index = 0;
                while($item = mysqli_fetch_assoc($items_result)) {
                    $item_quantity = $base_quantity;
                    if($index < $remainder) {
                        $item_quantity++;
                    }
                    
                    // Calculate cost based on proportion
                    $proportion = $item_quantity / $total_quantity;
                    $item_cost = $purchase_info['totalCost'] * $proportion;
                    $unit_price = $item_quantity > 0 ? $item_cost / $item_quantity : 0;
                    
                    $purchase_details[] = [
                        'itemID' => $item['itemID'],
                        'itemName' => $item['itemName'],
                        'quantity' => $item_quantity,
                        'price' => $unit_price,
                        'cost' => $item_cost
                    ];
                    
                    $index++;
                }
            }
        }
    } else {
        // Purchase not found
        $message = "<div class='alert alert-danger'>Purchase order not found.</div>";
        $current_action = 'list';
    }
}

// Fetch all purchase orders
$purchases_query = "SELECT po.*, sp.supplierID, s.supplierName 
                   FROM PurchaseOrder po 
                   JOIN Supplier_PurchaseOrder sp ON po.poID = sp.poID 
                   JOIN Supplier s ON sp.supplierID = s.supplierID 
                   ORDER BY po.poID DESC";
$purchases_result = mysqli_query($conn, $purchases_query);
?>

<!-- Main content area -->
<div class="purchases-content">
   
    
    <?php if(!empty($message)): ?>
        <?php echo $message; ?>
    <?php endif; ?>
    
    <?php if($current_action == 'view' && !empty($purchase_details)): ?>
        <!-- View Purchase Order Details -->
        <div class="purchase-details-container">
            <h3>Purchase Details</h3>
            <h4>Purchase Order <?php echo $purchase_id; ?></h4>
            
            <div class="purchase-table">
                <div class="purchase-table-header">
                    <div class="purchase-column-item">Item</div>
                    <div class="purchase-column-price">Price per unit</div>
                    <div class="purchase-column-quantity">Quantity</div>
                    <div class="purchase-column-cost">Cost</div>
                </div>
                
                <?php foreach($purchase_details as $detail): ?>
                    <div class="purchase-table-row">
                        <div class="purchase-column-item"><?php echo $detail['itemName']; ?></div>
                        <div class="purchase-column-price"><?php echo number_format($detail['price'], 0); ?></div>
                        <div class="purchase-column-quantity"><?php echo $detail['quantity']; ?></div>
                        <div class="purchase-column-cost">RM<?php echo number_format($detail['cost'], 2); ?></div>
                    </div>
                <?php endforeach; ?>
                
                <div class="purchase-table-footer">
                    <div class="purchase-column-total">Total</div>
                    <div class="purchase-column-total-cost">RM<?php echo number_format($purchase_info['totalCost'], 2); ?></div>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="purchases.php" class="back-button">Back to List</a>
            </div>
        </div>
    
    <?php else: ?>
        <!-- Purchase Orders List -->
        <div class="purchases-list-container">
            <!-- Purchase Orders Table -->
            <div class="purchases-list-table">
                <div class="purchases-list-header">
                    <div class="purchases-column-id">ID</div>
                    <div class="purchases-column-date">Date</div>
                    <div class="purchases-column-by">By</div>
                    <div class="purchases-column-quantity">Quantity</div>
                    <div class="purchases-column-total">Total Purchase</div>
                    <div class="purchases-column-actions"></div>
                </div>
                
                <?php if(mysqli_num_rows($purchases_result) > 0): ?>
                    <?php $counter = 1; ?>
                    <?php while($purchase = mysqli_fetch_assoc($purchases_result)): ?>
                        <div class="purchases-list-row">
                            <div class="purchases-column-id"><?php echo $counter++; ?></div>
                            <div class="purchases-column-date"><?php echo date('Y/m/d', strtotime($purchase['date'])); ?></div>
                            <div class="purchases-column-by"><?php echo $purchase['supplierName']; ?></div>
                            <div class="purchases-column-quantity"><?php echo $purchase['quantity']; ?></div>
                            <div class="purchases-column-total">RM<?php echo number_format($purchase['totalCost'], 2); ?></div>
                            <div class="purchases-column-actions">
                                <a href="purchases.php?action=view&id=<?php echo $purchase['poID']; ?>" class="view-receipt-icon">
                                    <i class="fas fa-receipt"></i>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="purchases-list-row">
                        <div class="no-purchases">No purchase orders found</div>
                    </div>
                <?php endif; ?>
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