<?php
// Include header
include 'headers.php';

// Include database connection
include '../config/db_connection.php';

// Initialize message variable
$message = "";

// Get the current action (add, edit, or list)
$current_action = isset($_GET['action']) ? $_GET['action'] : 'list';
$supplier_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : null;

// Handle Add Supplier
if(isset($_POST['add_supplier'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    
    // Insert new supplier
    $insert_query = "INSERT INTO Supplier (supplierName, supplierPhone, supplierLocation) 
                     VALUES ('$name', '$phone', '$location')";
    
    if(mysqli_query($conn, $insert_query)) {
        $message = "<div class='alert alert-success'>Supplier added successfully.</div>";
        $current_action = 'list'; // Redirect to list view
    } else {
        $message = "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
    }
}

// Handle Update Supplier
if(isset($_POST['update_supplier'])) {
    $supplier_id = mysqli_real_escape_string($conn, $_POST['supplier_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    
    // Update supplier
    $update_query = "UPDATE Supplier SET 
                     supplierName = '$name', 
                     supplierPhone = '$phone', 
                     supplierLocation = '$location' 
                     WHERE supplierID = '$supplier_id'";
    
    if(mysqli_query($conn, $update_query)) {
        $message = "<div class='alert alert-success'>Supplier updated successfully.</div>";
        $current_action = 'list'; // Redirect to list view
    } else {
        $message = "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
    }
}

// Handle Delete Supplier
if(isset($_GET['delete'])) {
    $supplier_id = mysqli_real_escape_string($conn, $_GET['delete']);
    
    // Check if supplier is used in any purchase orders
    $check_query = "SELECT COUNT(*) as count FROM Supplier_PurchaseOrder WHERE supplierID = '$supplier_id'";
    $check_result = mysqli_query($conn, $check_query);
    $check_data = mysqli_fetch_assoc($check_result);
    
    if($check_data['count'] > 0) {
        $message = "<div class='alert alert-danger'>Cannot delete supplier that has purchase orders.</div>";
    } else {
        // Delete supplier
        $delete_query = "DELETE FROM Supplier WHERE supplierID = '$supplier_id'";
        
        if(mysqli_query($conn, $delete_query)) {
            $message = "<div class='alert alert-success'>Supplier deleted successfully.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
        }
    }
}

// Get supplier details if editing
$edit_supplier = null;
if($current_action == 'edit' && $supplier_id) {
    $edit_query = "SELECT * FROM Supplier WHERE supplierID = '$supplier_id'";
    $edit_result = mysqli_query($conn, $edit_query);
    
    if($edit_result && mysqli_num_rows($edit_result) > 0) {
        $edit_supplier = mysqli_fetch_assoc($edit_result);
    } else {
        // Supplier not found
        $message = "<div class='alert alert-danger'>Supplier not found.</div>";
        $current_action = 'list';
    }
}

// Fetch all suppliers
$suppliers_query = "SELECT * FROM Supplier ORDER BY supplierID DESC";
$suppliers_result = mysqli_query($conn, $suppliers_query);
?>

<!-- Main content area -->
<div class="supplier-content">
   
    
    <?php if(!empty($message)): ?>
        <?php echo $message; ?>
    <?php endif; ?>
    
    <?php if($current_action == 'add'): ?>
        <!-- Add Supplier Form -->
        <div class="supplier-form-container">
            <h3>Add Supplier</h3>
            <div class="divider"></div>
            
            <div class="supplier-form-layout">
                <div class="form-subheader">New Supplier</div>
                
                <form method="post" action="supplier.php" class="supplier-form">
                    <div class="form-input">
                        <input type="text" class="form-control" id="name" name="name" placeholder="Name" required>
                    </div>
                    
                    <div class="form-input">
                        <input type="text" class="form-control" id="phone" name="phone" placeholder="Phone Number" required>
                    </div>
                    
                    <div class="form-input">
                        <textarea class="form-control" id="location" name="location" placeholder="Location" rows="4" required></textarea>
                    </div>
                    
                    <div class="form-submit">
                        <button type="submit" name="add_supplier" class="add-button">Add</button>
                    </div>
                </form>
            </div>
        </div>
        
    <?php elseif($current_action == 'edit' && $edit_supplier): ?>
        <!-- Edit Supplier Form -->
        <div class="supplier-form-container">
            <h3>Update Supplier</h3>
            <div class="divider"></div>
            
            <div class="supplier-form-layout">
                <div class="form-subheader"><?php echo $edit_supplier['supplierName']; ?></div>
                
                <form method="post" action="supplier.php" class="supplier-form">
                    <input type="hidden" name="supplier_id" value="<?php echo $edit_supplier['supplierID']; ?>">
                    
                    <div class="form-input">
                        <input type="text" class="form-control" id="name" name="name" placeholder="Name" value="<?php echo $edit_supplier['supplierName']; ?>" required>
                    </div>
                    
                    <div class="form-input">
                        <input type="text" class="form-control" id="phone" name="phone" placeholder="Phone Number" value="<?php echo $edit_supplier['supplierPhone']; ?>" required>
                    </div>
                    
                    <div class="form-input">
                        <textarea class="form-control" id="location" name="location" placeholder="Location" rows="4" required><?php echo $edit_supplier['supplierLocation']; ?></textarea>
                    </div>
                    
                    <div class="form-submit">
                        <button type="submit" name="update_supplier" class="update-button">Update</button>
                    </div>
                </form>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Suppliers List -->
        <div class="supplier-list-container">
            <!-- Add New Supplier Button -->
            <a href="supplier.php?action=add" class="add-supplier-btn">
                <i class="fas fa-plus"></i>
            </a>
            
            <!-- Suppliers Table -->
            <div class="supplier-table">
                <div class="supplier-table-header">
                    <div class="supplier-column-blank"></div>
                    <div class="supplier-column-name">Name</div>
                    <div class="supplier-column-phone">Phone</div>
                    <div class="supplier-column-location">Location</div>
                    <div class="supplier-column-actions"></div>
                </div>
                
                <?php if(mysqli_num_rows($suppliers_result) > 0): ?>
                    <?php while($supplier = mysqli_fetch_assoc($suppliers_result)): ?>
                        <div class="supplier-table-row">
                            <div class="supplier-column-blank"></div>
                            <div class="supplier-column-name"><?php echo $supplier['supplierName']; ?></div>
                            <div class="supplier-column-phone"><?php echo $supplier['supplierPhone']; ?></div>
                            <div class="supplier-column-location"><?php echo $supplier['supplierLocation']; ?></div>
                            <div class="supplier-column-actions">
                                <a href="supplier.php?action=edit&id=<?php echo $supplier['supplierID']; ?>" class="edit-icon">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                <a href="supplier.php?delete=<?php echo $supplier['supplierID']; ?>" class="delete-icon" onclick="return confirm('Are you sure you want to delete this supplier?')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="supplier-table-row">
                        <div class="supplier-column-blank"></div>
                        <div class="no-suppliers">No suppliers found</div>
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