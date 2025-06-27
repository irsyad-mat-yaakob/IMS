<?php
// Include header
include 'headers.php';

// Include database connection
include '../config/db_connection.php';

// Initialize message variable
$message = "";

// Handle Add Item
if(isset($_POST['add_item'])) {
    $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $unit = mysqli_real_escape_string($conn, $_POST['unit']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $reorder_level = mysqli_real_escape_string($conn, $_POST['reorder_level']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $exp_date = !empty($_POST['exp_date']) ? mysqli_real_escape_string($conn, $_POST['exp_date']) : null;
    $quantity = mysqli_real_escape_string($conn, $_POST['quantity']);
    
    // Generate a unique stock code
    $stock_code = 'STK-' . time() . '-' . rand(1000, 9999);
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    try {
        // First, insert into Stock table
        $stock_query = "INSERT INTO Stock (stockCode, quantity, reorderLevel, expiryDate, notificationSent) 
                       VALUES ('$stock_code', '$quantity', '$reorder_level', " . 
                       ($exp_date ? "'$exp_date'" : "NULL") . ", 'No')";
                       
        if(!mysqli_query($conn, $stock_query)) {
            throw new Exception("Error inserting stock: " . mysqli_error($conn));
        }
        
        // Then, insert into Item table
        $item_query = "INSERT INTO Item (itemName, itemCategory, sellPrice, unitType, itemDescription, stockCode) 
                      VALUES ('$item_name', '$category', '$price', '$unit', '$description', '$stock_code')";
                      
        if(!mysqli_query($conn, $item_query)) {
            throw new Exception("Error inserting item: " . mysqli_error($conn));
        }
        
        // If we get here, both queries succeeded, so commit the transaction
        mysqli_commit($conn);
        $message = "<div class='alert alert-success'>Item added successfully.</div>";
    } catch (Exception $e) {
        // An error occurred, so rollback the transaction
        mysqli_rollback($conn);
        $message = "<div class='alert alert-danger'>" . $e->getMessage() . "</div>";
    }
}

// Handle Update Item
if(isset($_POST['update_item'])) {
    $item_id = mysqli_real_escape_string($conn, $_POST['item_id']);
    $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $unit = mysqli_real_escape_string($conn, $_POST['unit']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $reorder_level = mysqli_real_escape_string($conn, $_POST['reorder_level']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $exp_date = !empty($_POST['exp_date']) ? mysqli_real_escape_string($conn, $_POST['exp_date']) : null;
    $quantity = isset($_POST['quantity']) ? mysqli_real_escape_string($conn, $_POST['quantity']) : null;
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    try {
        // First, get the stockCode for this item
        $stock_query = "SELECT stockCode FROM Item WHERE itemID = '$item_id'";
        $stock_result = mysqli_query($conn, $stock_query);
        
        if(!$stock_result) {
            throw new Exception("Error getting stock code: " . mysqli_error($conn));
        }
        
        if(mysqli_num_rows($stock_result) === 0) {
            throw new Exception("Item not found.");
        }
        
        $stock_data = mysqli_fetch_assoc($stock_result);
        $stock_code = $stock_data['stockCode'];
        
        // Update Item table
        $item_update = "UPDATE Item SET 
                        itemName = '$item_name', 
                        itemCategory = '$category', 
                        sellPrice = '$price', 
                        unitType = '$unit', 
                        itemDescription = '$description' 
                        WHERE itemID = '$item_id'";
                        
        if(!mysqli_query($conn, $item_update)) {
            throw new Exception("Error updating item: " . mysqli_error($conn));
        }
        
        // Update Stock table
        $stock_update = "UPDATE Stock SET ";
        $updates = [];
        
        if($reorder_level !== null) {
            $updates[] = "reorderLevel = '$reorder_level'";
        }
        
        if($exp_date !== null) {
            $updates[] = "expiryDate = " . ($exp_date ? "'$exp_date'" : "NULL");
        }
        
        if($quantity !== null) {
            $updates[] = "quantity = '$quantity'";
        }
        
        if(!empty($updates)) {
            $stock_update .= implode(", ", $updates);
            $stock_update .= " WHERE stockCode = '$stock_code'";
            
            if(!mysqli_query($conn, $stock_update)) {
                throw new Exception("Error updating stock: " . mysqli_error($conn));
            }
        }
        
        // If we get here, both queries succeeded, so commit the transaction
        mysqli_commit($conn);
        $message = "<div class='alert alert-success'>Item updated successfully.</div>";
    } catch (Exception $e) {
        // An error occurred, so rollback the transaction
        mysqli_rollback($conn);
        $message = "<div class='alert alert-danger'>" . $e->getMessage() . "</div>";
    }
}

// Handle Delete Item
if(isset($_GET['delete'])) {
    $item_id = mysqli_real_escape_string($conn, $_GET['delete']);
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    try {
        // First, get the stockCode for this item
        $stock_query = "SELECT stockCode FROM Item WHERE itemID = '$item_id'";
        $stock_result = mysqli_query($conn, $stock_query);
        
        if(!$stock_result) {
            throw new Exception("Error getting stock code: " . mysqli_error($conn));
        }
        
        if(mysqli_num_rows($stock_result) === 0) {
            throw new Exception("Item not found.");
        }
        
        $stock_data = mysqli_fetch_assoc($stock_result);
        $stock_code = $stock_data['stockCode'];
        
        // Check if the item is used in any sales
        $sales_check = "SELECT COUNT(*) as count FROM SalesDetails WHERE itemID = '$item_id'";
        $sales_result = mysqli_query($conn, $sales_check);
        $sales_data = mysqli_fetch_assoc($sales_result);
        
        if($sales_data['count'] > 0) {
            throw new Exception("Cannot delete item that has sales records.");
        }
        
        // Delete from Item table
        $delete_item = "DELETE FROM Item WHERE itemID = '$item_id'";
        if(!mysqli_query($conn, $delete_item)) {
            throw new Exception("Error deleting item: " . mysqli_error($conn));
        }
        
        // Check if stock is used by other items
        $stock_check = "SELECT COUNT(*) as count FROM Item WHERE stockCode = '$stock_code'";
        $stock_check_result = mysqli_query($conn, $stock_check);
        $stock_check_data = mysqli_fetch_assoc($stock_check_result);
        
        if($stock_check_data['count'] === 0) {
            // Delete from Stock table
            $delete_stock = "DELETE FROM Stock WHERE stockCode = '$stock_code'";
            if(!mysqli_query($conn, $delete_stock)) {
                throw new Exception("Error deleting stock: " . mysqli_error($conn));
            }
        }
        
        // If we get here, everything succeeded, so commit the transaction
        mysqli_commit($conn);
        $message = "<div class='alert alert-success'>Item deleted successfully.</div>";
    } catch (Exception $e) {
        // An error occurred, so rollback the transaction
        mysqli_rollback($conn);
        $message = "<div class='alert alert-danger'>" . $e->getMessage() . "</div>";
    }
}

// Get item details if editing
$edit_item = null;
if(isset($_GET['edit'])) {
    $item_id = mysqli_real_escape_string($conn, $_GET['edit']);
    $edit_query = "SELECT i.*, s.quantity, s.reorderLevel, s.expiryDate 
                  FROM Item i 
                  JOIN Stock s ON i.stockCode = s.stockCode 
                  WHERE i.itemID = '$item_id'";
    $edit_result = mysqli_query($conn, $edit_query);
    
    if($edit_result && mysqli_num_rows($edit_result) > 0) {
        $edit_item = mysqli_fetch_assoc($edit_result);
    }
}

// Fetch all inventory items
$inventory_query = "SELECT i.*, s.quantity, s.expiryDate 
                   FROM Item i 
                   JOIN Stock s ON i.stockCode = s.stockCode 
                   ORDER BY i.itemID DESC";
$inventory_result = mysqli_query($conn, $inventory_query);

// Determine current action (add, edit, or list)
$current_action = "list"; // Default view
if(isset($_GET['action'])) {
    $current_action = $_GET['action'];
}
if(isset($_GET['edit'])) {
    $current_action = "edit";
}
?>

<!-- Main content area -->
<div class="inventory-content">
    
    
    <?php if(!empty($message)): ?>
        <?php echo $message; ?>
    <?php endif; ?>
    
    <?php if($current_action == "add"): ?>
        <!-- Add Item Form -->
        <div class="inventory-form-container">
            <h3>Add Item</h3>
            <div class="divider"></div>
            
            <div class="inventory-form-layout">
                <form method="post" action="inventory.php" class="inventory-form">
                    <div class="form-row">
                        <div class="form-label">Item Name</div>
                        <div class="form-input">
                            <input type="text" class="form-control" id="item_name" name="item_name" placeholder="Name" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-label">Price</div>
                        <div class="form-input">
                            <input type="text" class="form-control" id="price" name="price" placeholder="RM" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-label">Unit</div>
                        <div class="form-input">
                            <input type="text" class="form-control" id="unit" name="unit" placeholder="kg/liters/etc...">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-label">Category</div>
                        <div class="form-input">
                            <input type="text" class="form-control" id="category" name="category" placeholder="Etc..." required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-label">Quantity</div>
                        <div class="form-input">
                            <input type="number" class="form-control" id="quantity" name="quantity" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-label">Reorder Level</div>
                        <div class="form-input">
                            <input type="number" class="form-control" id="reorder_level" name="reorder_level" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-label">Expiration Date</div>
                        <div class="form-input">
                            <input type="date" class="form-control" id="exp_date" name="exp_date">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-label">Description</div>
                        <div class="form-input">
                            <textarea class="form-control" id="description" name="description" placeholder="Etc..." rows="4"></textarea>
                        </div>
                    </div>
                    
                    <div class="form-submit">
                        <button type="submit" name="add_item" class="add-button">Add</button>
                    </div>
                </form>
            </div>
        </div>
    <?php elseif($current_action == "edit" && $edit_item): ?>
        <!-- Edit Item Form -->
        <div class="inventory-form-container">
            <h3>Update Item</h3>
            <div class="divider"></div>
            
            <div class="inventory-form-layout">
                <form method="post" action="inventory.php" class="inventory-form">
                    <input type="hidden" name="item_id" value="<?php echo $edit_item['itemID']; ?>">
                    
                    <div class="form-row">
                        <div class="form-label">Item Name</div>
                        <div class="form-input">
                            <input type="text" class="form-control" id="item_name" name="item_name" placeholder="Name" value="<?php echo $edit_item['itemName']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-label">Price</div>
                        <div class="form-input">
                            <input type="text" class="form-control" id="price" name="price" placeholder="RM" value="<?php echo $edit_item['sellPrice']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-label">Unit</div>
                        <div class="form-input">
                            <input type="text" class="form-control" id="unit" name="unit" placeholder="kg/liters/etc..." value="<?php echo $edit_item['unitType']; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-label">Category</div>
                        <div class="form-input">
                            <input type="text" class="form-control" id="category" name="category" placeholder="Etc..." value="<?php echo $edit_item['itemCategory']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-label">Quantity</div>
                        <div class="form-input">
                            <input type="number" class="form-control" id="quantity" name="quantity" value="<?php echo $edit_item['quantity']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-label">Reorder Level</div>
                        <div class="form-input">
                            <input type="number" class="form-control" id="reorder_level" name="reorder_level" value="<?php echo $edit_item['reorderLevel']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-label">Expiration Date</div>
                        <div class="form-input">
                            <input type="date" class="form-control" id="exp_date" name="exp_date" value="<?php echo $edit_item['expiryDate']; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-label">Description</div>
                        <div class="form-input">
                            <textarea class="form-control" id="description" name="description" placeholder="Etc..." rows="4"><?php echo $edit_item['itemDescription']; ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-submit">
                        <button type="submit" name="update_item" class="update-button">Update</button>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Inventory List -->
        <div class="inventory-list-container">
            <!-- Add New Item Button -->
            <a href="inventory.php?action=add" class="add-inventory-btn">
                <i class="fas fa-plus"></i>
            </a>
            
            <!-- Inventory Table -->
            <div class="inventory-table">
                <div class="inventory-table-header">
                    <div class="inventory-column-blank"></div>
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
                            <div class="inventory-column-blank"></div>
                            <div class="inventory-column-item"><?php echo $item['itemName']; ?></div>
                            <div class="inventory-column-quantity"><?php echo $item['quantity']; ?></div>
                            <div class="inventory-column-unit"><?php echo $item['unitType']; ?></div>
                            <div class="inventory-column-category"><?php echo $item['itemCategory']; ?></div>
                            <div class="inventory-column-expdate">
                                <?php echo ($item['expiryDate'] ? date('Y/m/d', strtotime($item['expiryDate'])) : ''); ?>
                            </div>
                            <div class="inventory-column-price">RM<?php echo number_format($item['sellPrice'], 2); ?></div>
                            <div class="inventory-column-actions">
                                <a href="inventory.php?edit=<?php echo $item['itemID']; ?>" class="edit-icon">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                <a href="inventory.php?delete=<?php echo $item['itemID']; ?>" class="delete-icon" onclick="return confirm('Are you sure you want to delete this item?')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="inventory-table-row">
                        <div class="inventory-column-blank"></div>
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