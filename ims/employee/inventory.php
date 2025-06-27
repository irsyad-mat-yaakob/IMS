<?php
// Include header
include 'headers.php';

// Include database connection
include '../config/db_connection.php';

// Initialize message variable
$message = "";

// Get the current action (view or list)
$current_action = isset($_GET['action']) ? $_GET['action'] : 'list';
$item_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : null;

// Fetch all inventory items
$inventory_query = "SELECT i.*, s.quantity, s.expiryDate 
                   FROM Item i 
                   JOIN Stock s ON i.stockCode = s.stockCode 
                   ORDER BY i.itemID DESC";
$inventory_result = mysqli_query($conn, $inventory_query);

// Get item details if viewing
$item_details = null;
if(($current_action == 'view') && $item_id) {
    $item_query = "SELECT i.*, s.quantity, s.reorderLevel, s.expiryDate 
                  FROM Item i 
                  JOIN Stock s ON i.stockCode = s.stockCode 
                  WHERE i.itemID = '$item_id'";
    $item_result = mysqli_query($conn, $item_query);
    
    if($item_result && mysqli_num_rows($item_result) > 0) {
        $item_details = mysqli_fetch_assoc($item_result);
    } else {
        // Item not found
        $message = "<div class='alert alert-danger'>Item not found.</div>";
        $current_action = 'list';
    }
}
?>

<!-- Main content area -->
<div class="inventory-content">

    
    <?php if(!empty($message)): ?>
        <?php echo $message; ?>
    <?php endif; ?>
    
    <?php if($current_action == 'view' && $item_details): ?>
        <!-- View Item Details -->
        <div class="inventory-details-container">
            <h3>Item Details</h3>
            
            <div class="item-details">
                <div class="detail-row">
                    <div class="detail-label">Item Name:</div>
                    <div class="detail-value"><?php echo $item_details['itemName']; ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Category:</div>
                    <div class="detail-value"><?php echo $item_details['itemCategory']; ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Price:</div>
                    <div class="detail-value">RM<?php echo number_format($item_details['sellPrice'], 2); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Unit:</div>
                    <div class="detail-value"><?php echo $item_details['unitType']; ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Quantity in Stock:</div>
                    <div class="detail-value"><?php echo $item_details['quantity']; ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Reorder Level:</div>
                    <div class="detail-value"><?php echo $item_details['reorderLevel']; ?></div>
                </div>
                
                <?php if($item_details['expiryDate']): ?>
                <div class="detail-row">
                    <div class="detail-label">Expiration Date:</div>
                    <div class="detail-value"><?php echo date('Y/m/d', strtotime($item_details['expiryDate'])); ?></div>
                </div>
                <?php endif; ?>
                
                <div class="detail-row">
                    <div class="detail-label">Description:</div>
                    <div class="detail-value"><?php echo $item_details['itemDescription']; ?></div>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="inventory.php" class="back-button">Back to List</a>
            </div>
        </div>
    
    <?php else: ?>
        <!-- Inventory List -->
        <div class="inventory-list-container">
            <!-- Inventory Table -->
            <div class="inventory-table">
                <div class="inventory-table-header">
                    <div class="inventory-column-item">Item</div>
                    <div class="inventory-column-quantity">Quantity</div>
                    <div class="inventory-column-unit">Unit</div>
                    <div class="inventory-column-category">Category</div>
                    <div class="inventory-column-expdate">Exp. date</div>
                    <div class="inventory-column-price">Price per unit</div>
                    <div class="inventory-column-actions"></div>
                </div>
                
                <?php if(mysqli_num_rows($inventory_result) > 0): ?>
                    <?php while($item = mysqli_fetch_assoc($inventory_result)): ?>
                        <div class="inventory-table-row">
                            <div class="inventory-column-item"><?php echo $item['itemName']; ?></div>
                            <div class="inventory-column-quantity"><?php echo $item['quantity']; ?></div>
                            <div class="inventory-column-unit"><?php echo $item['unitType']; ?></div>
                            <div class="inventory-column-category"><?php echo $item['itemCategory']; ?></div>
                            <div class="inventory-column-expdate">
                                <?php echo ($item['expiryDate'] ? date('Y/m/d', strtotime($item['expiryDate'])) : ''); ?>
                            </div>
                            <div class="inventory-column-price">RM<?php echo number_format($item['sellPrice'], 2); ?></div>
                            <div class="inventory-column-actions">
                                <a href="inventory.php?action=view&id=<?php echo $item['itemID']; ?>" class="view-icon">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="inventory-table-row">
                        <div class="no-inventory">No inventory items found</div>
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