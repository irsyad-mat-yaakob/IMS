<?php
// Include header
include 'headers.php';

// Include database connection
include '../config/db_connection.php';

// Initialize message variable
$message = "";

// Get the current action (add, edit, view, or list)
$current_action = isset($_GET['action']) ? $_GET['action'] : 'list';
$notification_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : null;
$notification_type = isset($_GET['type']) ? mysqli_real_escape_string($conn, $_GET['type']) : 'event'; // event or restock

// Handle Add Event Notification
if(isset($_POST['add_event_notification'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $event_date = mysqli_real_escape_string($conn, $_POST['event_date']);
    $reminder_days = mysqli_real_escape_string($conn, $_POST['reminder_days']);
    $user_id = $_SESSION['userID'];
    
    // Insert new notification
    $insert_query = "INSERT INTO Notifications (title, description, eventDate, reminderDays, createdBy) 
                     VALUES ('$title', '$description', '$event_date', '$reminder_days', '$user_id')";
    
    if(mysqli_query($conn, $insert_query)) {
        $message = "<div class='alert alert-success'>Event notification added successfully.</div>";
        $current_action = 'list'; // Redirect to list view
    } else {
        $message = "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
    }
}

// Handle Add Restock Notification
if(isset($_POST['add_restock_notification'])) {
    $item_id = mysqli_real_escape_string($conn, $_POST['item_id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $target_quantity = mysqli_real_escape_string($conn, $_POST['target_quantity']);
    $user_id = $_SESSION['userID'];
    
    // Insert new item notification
    $insert_query = "INSERT INTO ItemNotifications (itemID, title, description, targetQuantity, createdBy) 
                     VALUES ('$item_id', '$title', '$description', '$target_quantity', '$user_id')";
    
    if(mysqli_query($conn, $insert_query)) {
        $message = "<div class='alert alert-success'>Restock notification added successfully.</div>";
        $current_action = 'list'; // Redirect to list view
    } else {
        $message = "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
    }
}

// Handle Dismiss Notification
if(isset($_GET['dismiss'])) {
    $notification_id = mysqli_real_escape_string($conn, $_GET['dismiss']);
    $notification_type = isset($_GET['type']) ? mysqli_real_escape_string($conn, $_GET['type']) : 'event';
    
    if($notification_type == 'event') {
        $update_query = "UPDATE Notifications SET status = 'dismissed' WHERE notificationID = '$notification_id'";
    } else {
        $update_query = "UPDATE ItemNotifications SET status = 'dismissed' WHERE itemNotificationID = '$notification_id'";
    }
    
    if(mysqli_query($conn, $update_query)) {
        $message = "<div class='alert alert-success'>Notification dismissed successfully.</div>";
    } else {
        $message = "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
    }
}

// Get all available inventory items for dropdown
$items_query = "SELECT i.itemID, i.itemName, s.quantity 
               FROM Item i 
               JOIN Stock s ON i.stockCode = s.stockCode 
               ORDER BY i.itemName";
$items_result = mysqli_query($conn, $items_query);
$available_items = [];

if($items_result) {
    while($row = mysqli_fetch_assoc($items_result)) {
        $available_items[] = $row;
    }
}

// Fetch upcoming event notifications
$event_query = "SELECT n.*, u.name as username 
               FROM Notifications n 
               JOIN User u ON n.createdBy = u.userID 
               WHERE n.status = 'active' 
               ORDER BY n.eventDate ASC";
$event_result = mysqli_query($conn, $event_query);

// Fetch restock notifications
// Fetch restock notifications
$restock_query = "SELECT inot.*, i.itemName, s.quantity, u.name as username 
                 FROM ItemNotifications inot 
                 JOIN Item i ON inot.itemID = i.itemID 
                 JOIN Stock s ON i.stockCode = s.stockCode 
                 JOIN User u ON inot.createdBy = u.userID 
                 WHERE inot.status = 'active' 
                 ORDER BY i.itemName ASC";
$restock_result = mysqli_query($conn, $restock_query);
// Calculate active notification count for the top navigation badge
$active_notification_count = 0;

// Count upcoming event notifications
$today = date('Y-m-d');
$count_event_query = "SELECT COUNT(*) as count 
                     FROM Notifications 
                     WHERE status = 'active' 
                     AND DATE_ADD(eventDate, INTERVAL -reminderDays DAY) <= '$today' 
                     AND eventDate >= '$today'";
$count_event_result = mysqli_query($conn, $count_event_query);
if($count_event_result) {
    $count_data = mysqli_fetch_assoc($count_event_result);
    $active_notification_count += $count_data['count'];
}

// Count active restock notifications where current quantity is below target
$count_restock_query = "SELECT COUNT(*) as count 
                       FROM ItemNotifications inot 
                       JOIN Item i ON inot.itemID = i.itemID 
                       JOIN Stock s ON i.stockCode = s.stockCode 
                       WHERE inot.status = 'active' 
                       AND s.quantity < inot.targetQuantity";
$count_restock_result = mysqli_query($conn, $count_restock_query);
if($count_restock_result) {
    $count_data = mysqli_fetch_assoc($count_restock_result);
    $active_notification_count += $count_data['count'];
}

// Store the notification count in a session variable for use in other pages
$_SESSION['active_notification_count'] = $active_notification_count;
?>

<!-- Main content area -->
<div class="notifications-content">
 
    
    <?php if(!empty($message)): ?>
        <?php echo $message; ?>
    <?php endif; ?>
    
    <?php if($current_action == 'add' && $notification_type == 'event'): ?>
        <!-- Add Event Notification Form -->
        <div class="notification-form-container">
            <h3>Add Event Notification</h3>
            <div class="divider"></div>
            
            <form method="post" action="notifications.php" class="notification-form">
                <div class="form-row">
                    <div class="form-label">Title:</div>
                    <div class="form-input">
                        <input type="text" class="form-control" id="title" name="title" placeholder="e.g., Hari Raya Restock" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-label">Description:</div>
                    <div class="form-input">
                        <textarea class="form-control" id="description" name="description" placeholder="Event details or restock instructions" rows="4"></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-label">Event Date:</div>
                    <div class="form-input">
                        <input type="date" class="form-control" id="event_date" name="event_date" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-label">Reminder Days:</div>
                    <div class="form-input">
                        <input type="number" class="form-control" id="reminder_days" name="reminder_days" value="7" min="1" max="90" required>
                        <small class="form-text text-muted">Number of days before event to start showing notification</small>
                    </div>
                </div>
                
                <div class="form-submit">
                    <button type="submit" name="add_event_notification" class="add-button">Add Event</button>
                    <a href="notifications.php" class="cancel-button">Cancel</a>
                </div>
            </form>
        </div>
        
    <?php elseif($current_action == 'add' && $notification_type == 'restock'): ?>
        <!-- Add Restock Notification Form -->
        <div class="notification-form-container">
            <h3>Add Restock Notification</h3>
            <div class="divider"></div>
            
            <form method="post" action="notifications.php" class="notification-form">
                <div class="form-row">
                    <div class="form-label">Item:</div>
                    <div class="form-input">
                        <select class="form-control" id="item_id" name="item_id" required>
                            <option value="">Select Item</option>
                            <?php foreach($available_items as $item): ?>
                                <option value="<?php echo $item['itemID']; ?>" data-current="<?php echo $item['quantity']; ?>">
                                    <?php echo $item['itemName']; ?> (Current: <?php echo $item['quantity']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-label">Title:</div>
                    <div class="form-input">
                        <input type="text" class="form-control" id="title" name="title" placeholder="e.g., Restock Alert" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-label">Description:</div>
                    <div class="form-input">
                        <textarea class="form-control" id="description" name="description" placeholder="Restock instructions or notes" rows="4"></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-label">Target Quantity:</div>
                    <div class="form-input">
                        <input type="number" class="form-control" id="target_quantity" name="target_quantity" min="1" required>
                        <small class="form-text text-muted">Minimum quantity that should be maintained in stock</small>
                    </div>
                </div>
                
                <div class="form-submit">
                    <button type="submit" name="add_restock_notification" class="add-button">Add Restock Alert</button>
                    <a href="notifications.php" class="cancel-button">Cancel</a>
                </div>
            </form>
        </div>
        
    <?php else: ?>
        <!-- Notifications List and Add Buttons -->
        <div class="notification-actions">
            <a href="notifications.php?action=add&type=event" class="btn btn-primary">
                <i class="fas fa-calendar-plus"></i> Add Event Notification
            </a>
            <a href="notifications.php?action=add&type=restock" class="btn btn-success">
                <i class="fas fa-cubes"></i> Add Restock Alert
            </a>
        </div>
        
        <!-- Event Notifications Section -->
        <div class="notification-section">
            <h3>Upcoming Events</h3>
            
            <div class="notification-table">
                <div class="notification-table-header">
                    <div class="notification-column-title">Title</div>
                    <div class="notification-column-date">Event Date</div>
                    <div class="notification-column-reminder">Reminder Days</div>
                    <div class="notification-column-created">Created By</div>
                    <div class="notification-column-actions"></div>
                </div>
                
                <?php if(mysqli_num_rows($event_result) > 0): ?>
                    <?php while($notification = mysqli_fetch_assoc($event_result)): ?>
                        <?php 
                        $event_date = new DateTime($notification['eventDate']);
                        $today = new DateTime();
                        $days_until = $today->diff($event_date)->days;
                        $is_active = $days_until <= $notification['reminderDays'] && $event_date >= $today;
                        ?>
                        <div class="notification-table-row <?php echo $is_active ? 'active-notification' : ''; ?>">
                            <div class="notification-column-title">
                                <?php echo $notification['title']; ?>
                                <?php if(!empty($notification['description'])): ?>
                                    <div class="notification-description"><?php echo $notification['description']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="notification-column-date"><?php echo date('d/m/Y', strtotime($notification['eventDate'])); ?></div>
                            <div class="notification-column-reminder"><?php echo $notification['reminderDays']; ?></div>
                            <div class="notification-column-created"><?php echo $notification['username']; ?></div>
                            <div class="notification-column-actions">
                                <a href="notifications.php?dismiss=<?php echo $notification['notificationID']; ?>&type=event" class="dismiss-icon" onclick="return confirm('Are you sure you want to dismiss this notification?')">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="notification-table-row">
                        <div class="no-notifications">No event notifications found</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Restock Notifications Section -->
        <div class="notification-section">
            <h3>Restock Alerts</h3>
            
            <div class="notification-table">
                <div class="notification-table-header">
                    <div class="notification-column-title">Item</div>
                    <div class="notification-column-current">Current Quantity</div>
                    <div class="notification-column-target">Target Quantity</div>
                    <div class="notification-column-status">Status</div>
                    <div class="notification-column-created">Created By</div>
                    <div class="notification-column-actions"></div>
                </div>
                
                <?php if(mysqli_num_rows($restock_result) > 0): ?>
                    <?php while($notification = mysqli_fetch_assoc($restock_result)): ?>
                        <?php $is_active = $notification['quantity'] < $notification['targetQuantity']; ?>
                        <div class="notification-table-row <?php echo $is_active ? 'active-notification' : ''; ?>">
                            <div class="notification-column-title">
                                <?php echo $notification['itemName']; ?>
                                <?php if(!empty($notification['description'])): ?>
                                    <div class="notification-description"><?php echo $notification['description']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="notification-column-current <?php echo $is_active ? 'low-quantity' : ''; ?>">
                                <?php echo $notification['quantity']; ?>
                            </div>
                            <div class="notification-column-target"><?php echo $notification['targetQuantity']; ?></div>
                            <div class="notification-column-status">
                                <?php if($is_active): ?>
                                    <span class="status-badge restock-needed">Restock Needed</span>
                                <?php else: ?>
                                    <span class="status-badge stock-ok">Stock OK</span>
                                <?php endif; ?>
                            </div>
                            <div class="notification-column-created"><?php echo $notification['username']; ?></div>
                            <div class="notification-column-actions">
                                <a href="notifications.php?dismiss=<?php echo $notification['itemNotificationID']; ?>&type=restock" class="dismiss-icon" onclick="return confirm('Are you sure you want to dismiss this notification?')">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="notification-table-row">
                        <div class="no-notifications">No restock notifications found</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Script to update notification badge in the header -->
<script>
    // Update the notification badge in the header
    document.addEventListener('DOMContentLoaded', function() {
        const notificationCount = <?php echo $active_notification_count; ?>;
        const topNotification = document.querySelector('.top-notification a');
        
        if(topNotification && notificationCount > 0) {
            // Create or update the badge
            let badge = document.querySelector('.notification-badge');
            
            if(!badge) {
                badge = document.createElement('span');
                badge.className = 'notification-badge';
                topNotification.appendChild(badge);
            }
            
            badge.textContent = notificationCount;
        }
    });
    
    // Auto-populate the title when selecting an item for restock notification
    const itemSelect = document.getElementById('item_id');
    if(itemSelect) {
        itemSelect.addEventListener('change', function() {
            const titleInput = document.getElementById('title');
            const targetInput = document.getElementById('target_quantity');
            const selectedOption = this.options[this.selectedIndex];
            
            if(selectedOption.value) {
                const itemName = selectedOption.textContent.split(' (Current:')[0].trim();
                const currentQty = parseInt(selectedOption.getAttribute('data-current')) || 0;
                
                // Set default title
                if(!titleInput.value) {
                    titleInput.value = `Restock Alert: ${itemName}`;
                }
                
                // Set default target quantity (current + 20%)
                if(!targetInput.value) {
                    targetInput.value = Math.max(currentQty + Math.ceil(currentQty * 0.2), currentQty + 10);
                }
            }
        });
    }
</script>

<?php
// Close the content div and body/html tags
echo '</div>';
echo '</div>';
echo '</body>';
echo '</html>';
?>