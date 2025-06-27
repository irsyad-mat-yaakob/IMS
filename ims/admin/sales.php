<?php
// Include header
include 'headers.php';

// Include database connection
include '../config/db_connection.php';

// Initialize message variable
$message = "";

// Get the current action (view, add, edit, or list)
$current_action = isset($_GET['action']) ? $_GET['action'] : 'list';
$sales_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : null;

// Handle Add Sale
if(isset($_POST['add_sale'])) {
    $items = isset($_POST['items']) ? $_POST['items'] : [];
    $quantities = isset($_POST['quantities']) ? $_POST['quantities'] : [];
    $prices = isset($_POST['prices']) ? $_POST['prices'] : [];
    
    // Calculate total revenue
    $total_revenue = 0;
    for($i = 0; $i < count($items); $i++) {
        if(!empty($items[$i]) && !empty($quantities[$i]) && !empty($prices[$i])) {
            $total_revenue += $quantities[$i] * $prices[$i];
        }
    }
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Insert into Sales table
        $user_id = $_SESSION['userID'];
        $date = date('Y-m-d');
        
        $sales_query = "INSERT INTO Sales (userID, date, revenue) VALUES ('$user_id', '$date', '$total_revenue')";
        
        if(!mysqli_query($conn, $sales_query)) {
            throw new Exception("Error creating sale: " . mysqli_error($conn));
        }
        
        $sales_id = mysqli_insert_id($conn);
        
        // Insert sale details
        for($i = 0; $i < count($items); $i++) {
            if(!empty($items[$i]) && !empty($quantities[$i]) && !empty($prices[$i])) {
                $item_id = mysqli_real_escape_string($conn, $items[$i]);
                $quantity = mysqli_real_escape_string($conn, $quantities[$i]);
                $line_total = $quantity * $prices[$i];
                
                // Check if we have enough stock
                $stock_check = "SELECT s.quantity FROM Item i JOIN Stock s ON i.stockCode = s.stockCode WHERE i.itemID = '$item_id'";
                $stock_result = mysqli_query($conn, $stock_check);
                
                if(!$stock_result) {
                    throw new Exception("Error checking stock: " . mysqli_error($conn));
                }
                
                $stock_data = mysqli_fetch_assoc($stock_result);
                
                if($stock_data['quantity'] < $quantity) {
                    throw new Exception("Not enough stock for " . get_item_name($conn, $item_id));
                }
                
                // Insert sale detail
                $detail_query = "INSERT INTO SalesDetails (salesID, itemID, quantity, lineTotal) 
                               VALUES ('$sales_id', '$item_id', '$quantity', '$line_total')";
                
                if(!mysqli_query($conn, $detail_query)) {
                    throw new Exception("Error adding sale detail: " . mysqli_error($conn));
                }
                
                // Update stock quantity
                $stock_update = "UPDATE Stock s 
                                JOIN Item i ON s.stockCode = i.stockCode 
                                SET s.quantity = s.quantity - '$quantity' 
                                WHERE i.itemID = '$item_id'";
                
                if(!mysqli_query($conn, $stock_update)) {
                    throw new Exception("Error updating stock: " . mysqli_error($conn));
                }
            }
        }
        
        // If we get here, all queries succeeded, so commit the transaction
        mysqli_commit($conn);
        $message = "<div class='alert alert-success'>Sale added successfully.</div>";
        $current_action = 'list'; // Redirect to list view
        
    } catch (Exception $e) {
        // An error occurred, so rollback the transaction
        mysqli_rollback($conn);
        $message = "<div class='alert alert-danger'>" . $e->getMessage() . "</div>";
    }
}

// Handle Update Sale
if(isset($_POST['update_sale'])) {
    $sales_id = mysqli_real_escape_string($conn, $_POST['sales_id']);
    $items = isset($_POST['items']) ? $_POST['items'] : [];
    $quantities = isset($_POST['quantities']) ? $_POST['quantities'] : [];
    $prices = isset($_POST['prices']) ? $_POST['prices'] : [];
    
    // Calculate total revenue
    $total_revenue = 0;
    for($i = 0; $i < count($items); $i++) {
        if(!empty($items[$i]) && !empty($quantities[$i]) && !empty($prices[$i])) {
            $total_revenue += $quantities[$i] * $prices[$i];
        }
    }
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Get original sale details to restore stock
        $original_details = [];
        $details_query = "SELECT itemID, quantity FROM SalesDetails WHERE salesID = '$sales_id'";
        $details_result = mysqli_query($conn, $details_query);
        
        if(!$details_result) {
            throw new Exception("Error getting original details: " . mysqli_error($conn));
        }
        
        while($row = mysqli_fetch_assoc($details_result)) {
            $original_details[$row['itemID']] = $row['quantity'];
        }
        
        // Update Sales table
        $update_query = "UPDATE Sales SET revenue = '$total_revenue' WHERE salesID = '$sales_id'";
        
        if(!mysqli_query($conn, $update_query)) {
            throw new Exception("Error updating sale: " . mysqli_error($conn));
        }
        
        // Delete existing details
        $delete_details = "DELETE FROM SalesDetails WHERE salesID = '$sales_id'";
        
        if(!mysqli_query($conn, $delete_details)) {
            throw new Exception("Error deleting existing details: " . mysqli_error($conn));
        }
        
        // Restore original stock quantities
        foreach($original_details as $item_id => $quantity) {
            $restore_stock = "UPDATE Stock s 
                            JOIN Item i ON s.stockCode = i.stockCode 
                            SET s.quantity = s.quantity + '$quantity' 
                            WHERE i.itemID = '$item_id'";
            
            if(!mysqli_query($conn, $restore_stock)) {
                throw new Exception("Error restoring stock: " . mysqli_error($conn));
            }
        }
        
        // Insert updated sale details
        for($i = 0; $i < count($items); $i++) {
            if(!empty($items[$i]) && !empty($quantities[$i]) && !empty($prices[$i])) {
                $item_id = mysqli_real_escape_string($conn, $items[$i]);
                $quantity = mysqli_real_escape_string($conn, $quantities[$i]);
                $line_total = $quantity * $prices[$i];
                
                // Check if we have enough stock
                $stock_check = "SELECT s.quantity FROM Item i JOIN Stock s ON i.stockCode = s.stockCode WHERE i.itemID = '$item_id'";
                $stock_result = mysqli_query($conn, $stock_check);
                
                if(!$stock_result) {
                    throw new Exception("Error checking stock: " . mysqli_error($conn));
                }
                
                $stock_data = mysqli_fetch_assoc($stock_result);
                
                if($stock_data['quantity'] < $quantity) {
                    throw new Exception("Not enough stock for " . get_item_name($conn, $item_id));
                }
                
                // Insert sale detail
                $detail_query = "INSERT INTO SalesDetails (salesID, itemID, quantity, lineTotal) 
                               VALUES ('$sales_id', '$item_id', '$quantity', '$line_total')";
                
                if(!mysqli_query($conn, $detail_query)) {
                    throw new Exception("Error adding sale detail: " . mysqli_error($conn));
                }
                
                // Update stock quantity
                $stock_update = "UPDATE Stock s 
                                JOIN Item i ON s.stockCode = i.stockCode 
                                SET s.quantity = s.quantity - '$quantity' 
                                WHERE i.itemID = '$item_id'";
                
                if(!mysqli_query($conn, $stock_update)) {
                    throw new Exception("Error updating stock: " . mysqli_error($conn));
                }
            }
        }
        
        // If we get here, all queries succeeded, so commit the transaction
        mysqli_commit($conn);
        $message = "<div class='alert alert-success'>Sale updated successfully.</div>";
        $current_action = 'list'; // Redirect to list view
        
    } catch (Exception $e) {
        // An error occurred, so rollback the transaction
        mysqli_rollback($conn);
        $message = "<div class='alert alert-danger'>" . $e->getMessage() . "</div>";
    }
}

// Helper function to get item name
function get_item_name($conn, $item_id) {
    $query = "SELECT itemName FROM Item WHERE itemID = '$item_id'";
    $result = mysqli_query($conn, $query);
    
    if($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        return $data['itemName'];
    }
    
    return "Unknown Item";
}

// Get all available inventory items for dropdown
$items_query = "SELECT i.itemID, i.itemName, i.sellPrice, s.quantity 
               FROM Item i 
               JOIN Stock s ON i.stockCode = s.stockCode 
               WHERE s.quantity > 0 
               ORDER BY i.itemName";
$items_result = mysqli_query($conn, $items_query);
$available_items = [];

if($items_result) {
    while($row = mysqli_fetch_assoc($items_result)) {
        $available_items[] = $row;
    }
}

// Get sales details if viewing or editing
$sale_details = [];
if(($current_action == 'view' || $current_action == 'edit') && $sales_id) {
    // Get sale information
    $sale_query = "SELECT s.*, u.name as username 
                  FROM Sales s 
                  JOIN User u ON s.userID = u.userID 
                  WHERE s.salesID = '$sales_id'";
    $sale_result = mysqli_query($conn, $sale_query);
    
    if($sale_result && mysqli_num_rows($sale_result) > 0) {
        $sale_info = mysqli_fetch_assoc($sale_result);
        
        // Get sale details
        $details_query = "SELECT sd.*, i.itemName, i.sellPrice 
                         FROM SalesDetails sd 
                         JOIN Item i ON sd.itemID = i.itemID 
                         WHERE sd.salesID = '$sales_id'";
        $details_result = mysqli_query($conn, $details_query);
        
        if($details_result) {
            while($row = mysqli_fetch_assoc($details_result)) {
                $sale_details[] = $row;
            }
        }
    } else {
        // Sale not found
        $message = "<div class='alert alert-danger'>Sale not found.</div>";
        $current_action = 'list';
    }
}

// Fetch all sales
$sales_query = "SELECT s.*, u.name as username 
               FROM Sales s 
               JOIN User u ON s.userID = u.userID 
               ORDER BY s.salesID DESC";
$sales_result = mysqli_query($conn, $sales_query);
?>

<!-- Main content area -->
<div class="sales-content">

    
    <?php if(!empty($message)): ?>
        <?php echo $message; ?>
    <?php endif; ?>
    
    <?php if($current_action == 'add'): ?>
        <!-- Add Sale Form -->
        <div class="sales-form-container">
            <h3>Add Sales</h3>
            
            <form method="post" action="sales.php" id="addSaleForm">
                <div class="sales-table">
                    <div class="sales-table-header">
                        <div class="sales-column-item">Item</div>
                        <div class="sales-column-price">Price per unit</div>
                        <div class="sales-column-quantity">Quantity</div>
                        <div class="sales-column-revenue">Revenue</div>
                    </div>
                    
                    <div class="sales-items-container" id="salesItemsContainer">
                        <!-- Initial blank row -->
                        <div class="sales-table-row">
                            <div class="sales-column-item">
                                <select name="items[]" class="form-control item-select" onchange="updatePrice(this)">
                                    <option value="">Item Name</option>
                                    <?php foreach($available_items as $item): ?>
                                        <option value="<?php echo $item['itemID']; ?>" data-price="<?php echo $item['sellPrice']; ?>" data-stock="<?php echo $item['quantity']; ?>">
                                            <?php echo $item['itemName']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="sales-column-price">
                                <input type="number" step="0.01" name="prices[]" class="form-control price-input" readonly>
                            </div>
                            <div class="sales-column-quantity">
                                <input type="number" name="quantities[]" class="form-control quantity-input" oninput="calculateRevenue(this)" min="1" max="9999">
                            </div>
                            <div class="sales-column-revenue">
                                <input type="text" class="form-control revenue-display" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="add-more-row">
                        <button type="button" class="add-more-btn" onclick="addItemRow()">
                            <i class="fas fa-plus"></i> Add more
                        </button>
                    </div>
                </div>
                
                <div class="form-submit">
                    <button type="submit" name="add_sale" class="confirm-button">Confirm</button>
                </div>
            </form>
        </div>
        
    <?php elseif($current_action == 'view' && !empty($sale_details)): ?>
        <!-- View Sale Details -->
        <div class="sales-details-container">
            <h3>Sales Details</h3>
            <h4>Sales <?php echo $sales_id; ?></h4>
            
            <div class="sales-table">
                <div class="sales-table-header">
                    <div class="sales-column-item">Item</div>
                    <div class="sales-column-price">Price per unit</div>
                    <div class="sales-column-quantity">Quantity</div>
                    <div class="sales-column-revenue">Revenue</div>
                </div>
                
                <?php foreach($sale_details as $detail): ?>
                    <div class="sales-table-row">
                        <div class="sales-column-item"><?php echo $detail['itemName']; ?></div>
                        <div class="sales-column-price"><?php echo $detail['sellPrice']; ?></div>
                        <div class="sales-column-quantity"><?php echo $detail['quantity']; ?></div>
                        <div class="sales-column-revenue">RM<?php echo number_format($detail['lineTotal'], 2); ?></div>
                    </div>
                <?php endforeach; ?>
                
                <div class="sales-table-footer">
                    <div class="sales-column-total">Total</div>
                    <div class="sales-column-total-revenue">RM<?php echo number_format($sale_info['revenue'], 2); ?></div>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="sales.php" class="back-button">Back to List</a>
                <a href="sales.php?action=edit&id=<?php echo $sales_id; ?>" class="edit-button">Edit</a>
            </div>
        </div>
        
    <?php elseif($current_action == 'edit' && !empty($sale_details)): ?>
        <!-- Edit Sale Form -->
        <div class="sales-form-container">
            <h3>Update Sales</h3>
            <h4>Sales <?php echo $sales_id; ?></h4>
            
            <form method="post" action="sales.php" id="editSaleForm">
                <input type="hidden" name="sales_id" value="<?php echo $sales_id; ?>">
                
                <div class="sales-table">
                    <div class="sales-table-header">
                        <div class="sales-column-item">Item</div>
                        <div class="sales-column-price">Price per unit</div>
                        <div class="sales-column-quantity">Quantity</div>
                        <div class="sales-column-revenue">Revenue</div>
                    </div>
                    
                    <div class="sales-items-container" id="salesItemsContainer">
                        <?php foreach($sale_details as $index => $detail): ?>
                            <div class="sales-table-row">
                                <div class="sales-column-item">
                                    <?php echo $detail['itemName']; ?>
                                    <input type="hidden" name="items[]" value="<?php echo $detail['itemID']; ?>">
                                    <button type="button" class="remove-item-btn" onclick="removeItem(this)">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                                <div class="sales-column-price">
                                    <input type="number" step="0.01" name="prices[]" class="form-control price-input" value="<?php echo $detail['sellPrice']; ?>" readonly>
                                </div>
                                <div class="sales-column-quantity">
                                    <input type="number" name="quantities[]" class="form-control quantity-input" value="<?php echo $detail['quantity']; ?>" oninput="calculateRevenue(this)" min="1" max="9999">
                                </div>
                                <div class="sales-column-revenue">
                                    <input type="text" class="form-control revenue-display" value="RM<?php echo number_format($detail['lineTotal'], 2); ?>" readonly>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="add-more-row">
                        <button type="button" class="add-more-btn" onclick="addItemRowEdit()">
                            <i class="fas fa-plus"></i> Add more
                        </button>
                    </div>
                </div>
                
                <div class="form-submit">
                    <button type="submit" name="update_sale" class="confirm-button">Confirm</button>
                </div>
            </form>
        </div>
    
    <?php else: ?>
        <!-- Sales List -->
        <div class="sales-list-container">
            <!-- Add New Sale Button -->
            <a href="sales.php?action=add" class="add-sales-btn">
                <i class="fas fa-plus"></i>
            </a>
            
            <!-- Sales Table -->
            <div class="sales-list-table">
                <div class="sales-list-header">
                    <div class="sales-column-blank"></div>
                    <div class="sales-column-id">ID</div>
                    <div class="sales-column-date">Date</div>
                    <div class="sales-column-by">By</div>
                    <div class="sales-column-quantity">Quantity</div>
                    <div class="sales-column-total">Total Sales</div>
                    <div class="sales-column-actions"></div>
                </div>
                
                <?php if(mysqli_num_rows($sales_result) > 0): ?>
                    <?php $counter = 1; ?>
                    <?php while($sale = mysqli_fetch_assoc($sales_result)): ?>
                        <div class="sales-list-row">
                            <div class="sales-column-blank"></div>
                            <div class="sales-column-id"><?php echo $counter++; ?></div>
                            <div class="sales-column-date"><?php echo date('Y/m/d', strtotime($sale['date'])); ?></div>
                            <div class="sales-column-by"><?php echo $sale['username']; ?></div>
                            <div class="sales-column-quantity">
                                <?php
                                // Get total quantity for this sale
                                $qty_query = "SELECT SUM(quantity) as total_quantity FROM SalesDetails WHERE salesID = '" . $sale['salesID'] . "'";
                                $qty_result = mysqli_query($conn, $qty_query);
                                $qty_data = mysqli_fetch_assoc($qty_result);
                                echo $qty_data['total_quantity'] ? $qty_data['total_quantity'] : 0;
                                ?>
                            </div>
                            <div class="sales-column-total">RM<?php echo number_format($sale['revenue'], 2); ?></div>
                            <div class="sales-column-actions">
                                <a href="sales.php?action=edit&id=<?php echo $sale['salesID']; ?>" class="edit-icon">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                <a href="sales.php?action=view&id=<?php echo $sale['salesID']; ?>" class="view-receipt-icon">
                                    <i class="fas fa-receipt"></i>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="sales-list-row">
                        <div class="sales-column-blank"></div>
                        <div class="no-sales">No sales records found</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- JavaScript for dynamic form handling -->
<script>
function updatePrice(selectElement) {
    const row = selectElement.closest('.sales-table-row');
    const priceInput = row.querySelector('.price-input');
    const quantityInput = row.querySelector('.quantity-input');
    const revenueDisplay = row.querySelector('.revenue-display');
    
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const price = selectedOption ? selectedOption.getAttribute('data-price') : '';
    const maxStock = selectedOption ? selectedOption.getAttribute('data-stock') : '';
    
    priceInput.value = price;
    
    // Reset quantity and revenue
    quantityInput.value = '';
    revenueDisplay.value = '';
    
    // Update max quantity based on available stock
    if (maxStock) {
        quantityInput.setAttribute('max', maxStock);
    }
}

function calculateRevenue(inputElement) {
    const row = inputElement.closest('.sales-table-row');
    const priceInput = row.querySelector('.price-input');
    const revenueDisplay = row.querySelector('.revenue-display');
    
    const price = parseFloat(priceInput.value) || 0;
    const quantity = parseInt(inputElement.value) || 0;
    
    const revenue = price * quantity;
    revenueDisplay.value = 'RM' + revenue.toFixed(2);
}

function addItemRow() {
    const container = document.getElementById('salesItemsContainer');
    const itemsHTML = `
        <div class="sales-table-row">
            <div class="sales-column-item">
                <select name="items[]" class="form-control item-select" onchange="updatePrice(this)">
                    <option value="">Item Name</option>
                    <?php foreach($available_items as $item): ?>
                        <option value="<?php echo $item['itemID']; ?>" data-price="<?php echo $item['sellPrice']; ?>" data-stock="<?php echo $item['quantity']; ?>">
                            <?php echo $item['itemName']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sales-column-price">
                <input type="number" step="0.01" name="prices[]" class="form-control price-input" readonly>
            </div>
            <div class="sales-column-quantity">
                <input type="number" name="quantities[]" class="form-control quantity-input" oninput="calculateRevenue(this)" min="1" max="9999">
            </div>
            <div class="sales-column-revenue">
                <input type="text" class="form-control revenue-display" readonly>
            </div>
        </div>
    `;
    
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = itemsHTML;
    container.appendChild(tempDiv.firstElementChild);
}

function addItemRowEdit() {
    const container = document.getElementById('salesItemsContainer');
    const itemsHTML = `
        <div class="sales-table-row">
            <div class="sales-column-item">
                <select name="items[]" class="form-control item-select" onchange="updatePrice(this)">
                    <option value="">Item Name</option>
                    <?php foreach($available_items as $item): ?>
                        <option value="<?php echo $item['itemID']; ?>" data-price="<?php echo $item['sellPrice']; ?>" data-stock="<?php echo $item['quantity']; ?>">
                            <?php echo $item['itemName']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="remove-item-btn" onclick="removeItem(this)">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
            <div class="sales-column-price">
                <input type="number" step="0.01" name="prices[]" class="form-control price-input" readonly>
            </div>
            <div class="sales-column-quantity">
                <input type="number" name="quantities[]" class="form-control quantity-input" oninput="calculateRevenue(this)" min="1" max="9999">
            </div>
            <div class="sales-column-revenue">
                <input type="text" class="form-control revenue-display" readonly>
            </div>
        </div>
    `;
    
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = itemsHTML;
    container.appendChild(tempDiv.firstElementChild);
}

function removeItem(button) {
    const row = button.closest('.sales-table-row');
    row.parentNode.removeChild(row);
}
</script>

<?php
// Close the content div and body/html tags
echo '</div>';
echo '</div>';
echo '</body>';
echo '</html>';
?>