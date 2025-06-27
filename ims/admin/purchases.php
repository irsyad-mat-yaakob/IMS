<?php
// Include header
include 'headers.php';

// Include database connection
include '../config/db_connection.php';

// Initialize message variable
$message = "";

// Get the current action (view, add, or list)
$current_action = isset($_GET['action']) ? $_GET['action'] : 'list';
$purchase_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : null;

// Handle Add Purchase Order
if(isset($_POST['add_purchase'])) {
    $supplier = mysqli_real_escape_string($conn, $_POST['supplier']);
    $items = isset($_POST['items']) ? $_POST['items'] : [];
    $quantities = isset($_POST['quantities']) ? $_POST['quantities'] : [];
    $prices = isset($_POST['prices']) ? $_POST['prices'] : [];
    
    // Calculate total cost
    $total_cost = 0;
    for($i = 0; $i < count($items); $i++) {
        if(!empty($items[$i]) && !empty($quantities[$i]) && !empty($prices[$i])) {
            $total_cost += $quantities[$i] * $prices[$i];
        }
    }
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Get first item's stock code
        $first_item = !empty($items[0]) ? mysqli_real_escape_string($conn, $items[0]) : null;
        $stock_code = null;
        
        if($first_item) {
            $stock_query = "SELECT stockCode FROM Item WHERE itemID = '$first_item'";
            $stock_result = mysqli_query($conn, $stock_query);
            
            if(!$stock_result) {
                throw new Exception("Error getting stock code: " . mysqli_error($conn));
            }
            
            if(mysqli_num_rows($stock_result) > 0) {
                $stock_data = mysqli_fetch_assoc($stock_result);
                $stock_code = $stock_data['stockCode'];
            } else {
                throw new Exception("Item not found.");
            }
        } else {
            throw new Exception("No items selected.");
        }
        
        // Insert into PurchaseOrder table
        $date = date('Y-m-d');
        $total_quantity = array_sum($quantities);
        
        $purchase_query = "INSERT INTO PurchaseOrder (date, quantity, totalCost, stockCode) 
                          VALUES ('$date', '$total_quantity', '$total_cost', '$stock_code')";
        
        if(!mysqli_query($conn, $purchase_query)) {
            throw new Exception("Error creating purchase order: " . mysqli_error($conn));
        }
        
        $purchase_id = mysqli_insert_id($conn);
        
        // Link supplier to purchase order
        $supplier_link = "INSERT INTO Supplier_PurchaseOrder (supplierID, poID) 
                         VALUES ('$supplier', '$purchase_id')";
        
        if(!mysqli_query($conn, $supplier_link)) {
            throw new Exception("Error linking supplier: " . mysqli_error($conn));
        }
        
        // Update stock quantities
        for($i = 0; $i < count($items); $i++) {
            if(!empty($items[$i]) && !empty($quantities[$i])) {
                $item_id = mysqli_real_escape_string($conn, $items[$i]);
                $quantity = mysqli_real_escape_string($conn, $quantities[$i]);
                
                // Update stock quantity
                $stock_update = "UPDATE Stock s 
                                JOIN Item i ON s.stockCode = i.stockCode 
                                SET s.quantity = s.quantity + '$quantity' 
                                WHERE i.itemID = '$item_id'";
                
                if(!mysqli_query($conn, $stock_update)) {
                    throw new Exception("Error updating stock: " . mysqli_error($conn));
                }
            }
        }
        
        // If we get here, all queries succeeded, so commit the transaction
        mysqli_commit($conn);
        $message = "<div class='alert alert-success'>Purchase order added successfully.</div>";
        $current_action = 'list'; // Redirect to list view
        
    } catch (Exception $e) {
        // An error occurred, so rollback the transaction
        mysqli_rollback($conn);
        $message = "<div class='alert alert-danger'>" . $e->getMessage() . "</div>";
    }
}

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

// Get all suppliers for dropdown
$suppliers_query = "SELECT supplierID, supplierName FROM Supplier ORDER BY supplierName";
$suppliers_result = mysqli_query($conn, $suppliers_query);
$suppliers = [];

if($suppliers_result) {
    while($row = mysqli_fetch_assoc($suppliers_result)) {
        $suppliers[] = $row;
    }
}

// Get all available inventory items for dropdown
$items_query = "SELECT i.itemID, i.itemName, i.sellPrice 
               FROM Item i 
               ORDER BY i.itemName";
$items_result = mysqli_query($conn, $items_query);
$available_items = [];

if($items_result) {
    while($row = mysqli_fetch_assoc($items_result)) {
        $available_items[] = $row;
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
    
    <?php if($current_action == 'add'): ?>
        <!-- Add Purchase Order Form -->
        <div class="purchase-form-container">
            <h3>Add Purchase Order</h3>
            
            <form method="post" action="purchases.php" id="addPurchaseForm">
                <div class="supplier-selection">
                    <div class="supplier-label">By</div>
                    <div class="supplier-input">
                        <select name="supplier" class="form-control" required>
                            <option value="">Select Supplier</option>
                            <?php foreach($suppliers as $supplier): ?>
                                <option value="<?php echo $supplier['supplierID']; ?>"><?php echo $supplier['supplierName']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="purchase-table">
                    <div class="purchase-table-header">
                        <div class="purchase-column-item">Item</div>
                        <div class="purchase-column-price">Price per unit</div>
                        <div class="purchase-column-quantity">Quantity</div>
                        <div class="purchase-column-cost">Cost</div>
                    </div>
                    
                    <div class="purchase-items-container" id="purchaseItemsContainer">
                        <!-- Initial items rows -->
                        <div class="purchase-table-row">
                            <div class="purchase-column-item">
                                <select name="items[]" class="form-control item-select" onchange="updatePurchasePrice(this)">
                                    <option value="">Item Name</option>
                                    <?php foreach($available_items as $item): ?>
                                        <option value="<?php echo $item['itemID']; ?>" data-price="<?php echo $item['sellPrice']; ?>">
                                            <?php echo $item['itemName']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="remove-item-btn" onclick="removeItem(this)">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                            <div class="purchase-column-price">
                                <input type="number" step="0.01" name="prices[]" class="form-control price-input" oninput="calculatePurchaseCost(this)">
                            </div>
                            <div class="purchase-column-quantity">
                                <input type="number" name="quantities[]" class="form-control quantity-input" oninput="calculatePurchaseCost(this)" min="1" max="9999">
                            </div>
                            <div class="purchase-column-cost">
                                <input type="text" class="form-control cost-display" readonly>
                            </div>
                        </div>
                        
                        <div class="purchase-table-row">
                            <div class="purchase-column-item">
                                <select name="items[]" class="form-control item-select" onchange="updatePurchasePrice(this)">
                                    <option value="">Item Name</option>
                                    <?php foreach($available_items as $item): ?>
                                        <option value="<?php echo $item['itemID']; ?>" data-price="<?php echo $item['sellPrice']; ?>">
                                            <?php echo $item['itemName']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="remove-item-btn" onclick="removeItem(this)">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                            <div class="purchase-column-price">
                                <input type="number" step="0.01" name="prices[]" class="form-control price-input" oninput="calculatePurchaseCost(this)">
                            </div>
                            <div class="purchase-column-quantity">
                                <input type="number" name="quantities[]" class="form-control quantity-input" oninput="calculatePurchaseCost(this)" min="1" max="9999">
                            </div>
                            <div class="purchase-column-cost">
                                <input type="text" class="form-control cost-display" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="add-more-row">
                        <button type="button" class="add-more-btn" onclick="addPurchaseItemRow()">
                            <i class="fas fa-plus"></i> Add more
                        </button>
                    </div>
                </div>
                
                <div class="form-submit">
                    <button type="submit" name="add_purchase" class="confirm-button">Confirm</button>
                </div>
            </form>
        </div>
        
    <?php elseif($current_action == 'view' && !empty($purchase_details)): ?>
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
            <!-- Add New Purchase Order Button -->
            <a href="purchases.php?action=add" class="add-purchase-btn">
                <i class="fas fa-plus"></i>
            </a>
            
            <!-- Purchase Orders Table -->
            <div class="purchases-list-table">
                <div class="purchases-list-header">
                    <div class="purchases-column-blank"></div>
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
                            <div class="purchases-column-blank"></div>
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
                        <div class="purchases-column-blank"></div>
                        <div class="no-purchases">No purchase orders found</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- JavaScript for dynamic form handling -->
<script>
function updatePurchasePrice(selectElement) {
    const row = selectElement.closest('.purchase-table-row');
    const priceInput = row.querySelector('.price-input');
    const quantityInput = row.querySelector('.quantity-input');
    const costDisplay = row.querySelector('.cost-display');
    
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    // Default price to recommended price from item
    const suggestedPrice = selectedOption ? selectedOption.getAttribute('data-price') : '';
    
    priceInput.value = suggestedPrice;
    
    // Reset quantity and cost
    quantityInput.value = '';
    costDisplay.value = '';
}

function calculatePurchaseCost(inputElement) {
    const row = inputElement.closest('.purchase-table-row');
    const priceInput = row.querySelector('.price-input');
    const quantityInput = row.querySelector('.quantity-input');
    const costDisplay = row.querySelector('.cost-display');
    
    const price = parseFloat(priceInput.value) || 0;
    const quantity = parseInt(quantityInput.value) || 0;
    
    const cost = price * quantity;
    costDisplay.value = 'RM' + cost.toFixed(2);
}

function addPurchaseItemRow() {
    const container = document.getElementById('purchaseItemsContainer');
    const itemsHTML = `
        <div class="purchase-table-row">
            <div class="purchase-column-item">
                <select name="items[]" class="form-control item-select" onchange="updatePurchasePrice(this)">
                    <option value="">Item Name</option>
                    <?php foreach($available_items as $item): ?>
                        <option value="<?php echo $item['itemID']; ?>" data-price="<?php echo $item['sellPrice']; ?>">
                            <?php echo $item['itemName']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="remove-item-btn" onclick="removeItem(this)">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
            <div class="purchase-column-price">
                <input type="number" step="0.01" name="prices[]" class="form-control price-input" oninput="calculatePurchaseCost(this)">
            </div>
            <div class="purchase-column-quantity">
                <input type="number" name="quantities[]" class="form-control quantity-input" oninput="calculatePurchaseCost(this)" min="1" max="9999">
            </div>
            <div class="purchase-column-cost">
                <input type="text" class="form-control cost-display" readonly>
            </div>
        </div>
    `;
    
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = itemsHTML;
    container.appendChild(tempDiv.firstElementChild);
}

function removeItem(button) {
    const row = button.closest('.purchase-table-row');
    
    // Don't remove if it's the last row
    const container = document.getElementById('purchaseItemsContainer');
    if (container.children.length > 1) {
        row.parentNode.removeChild(row);
    }
}
</script>

<?php
// Close the content div and body/html tags
echo '</div>';
echo '</div>';
echo '</body>';
echo '</html>';
?>