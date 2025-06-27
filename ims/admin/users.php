<?php
// Include header
include 'headers.php';

// Include database connection
include '../config/db_connection.php';

// Initialize message variable
$message = "";

// Handle Add/Register User
if (isset($_POST['register'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $usertype = mysqli_real_escape_string($conn, $_POST['usertype']);

    // Check if username already exists
    $check_query = "SELECT * FROM User WHERE username = '$username'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        $message = "<div class='alert alert-danger'>Username already exists. Please choose another username.</div>";
    } else {
        // Insert new user
        $insert_query = "INSERT INTO User (name, phone, username, password, usertype) 
                         VALUES ('$name', '$phone', '$username', '$password', '$usertype')";

        if (mysqli_query($conn, $insert_query)) {
            $message = "<div class='alert alert-success'>User account created successfully.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
        }
    }
}

// Handle Delete User
if (isset($_GET['delete'])) {
    $user_id = mysqli_real_escape_string($conn, $_GET['delete']);

    // Delete user
    $delete_query = "DELETE FROM User WHERE userID = '$user_id'";

    if (mysqli_query($conn, $delete_query)) {
        $message = "<div class='alert alert-success'>User account deleted successfully.</div>";
    } else {
        $message = "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
    }
}

// Handle Update User
if (isset($_POST['update'])) {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $usertype = mysqli_real_escape_string($conn, $_POST['usertype']);

    // Check if username already exists (excluding current user)
    $check_query = "SELECT * FROM User WHERE username = '$username' AND userID != '$user_id'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        $message = "<div class='alert alert-danger'>Username already exists. Please choose another username.</div>";
    } else {
        // Update user info
        $update_query = "UPDATE User SET 
                         name = '$name', 
                         phone = '$phone', 
                         username = '$username',
                         usertype = '$usertype'";

        // Only update password if it's not empty
        if (!empty($password)) {
            $update_query .= ", password = '$password'";
        }

        $update_query .= " WHERE userID = '$user_id'";

        if (mysqli_query($conn, $update_query)) {
            $message = "<div class='alert alert-success'>User account updated successfully.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
        }
    }
}

// Get user details if editing
$edit_user = null;
if (isset($_GET['edit'])) {
    $user_id = mysqli_real_escape_string($conn, $_GET['edit']);
    $edit_query = "SELECT * FROM User WHERE userID = '$user_id'";
    $edit_result = mysqli_query($conn, $edit_query);

    if (mysqli_num_rows($edit_result) > 0) {
        $edit_user = mysqli_fetch_assoc($edit_result);
    }
}

// Fetch all users
$users_query = "SELECT * FROM User ORDER BY userID DESC";
$users_result = mysqli_query($conn, $users_query);

// Determine current action (add or edit)
$current_action = "list"; // Default view
if (isset($_GET['action'])) {
    $current_action = $_GET['action'];
}
if (isset($_GET['edit'])) {
    $current_action = "edit";
}
?>

<!-- Main content area -->
<div class="user-content">


    <?php if (!empty($message)): ?>
        <?php echo $message; ?>
    <?php endif; ?>

    <?php if ($current_action == "add"): ?>
        <!-- Add User Form - Redesigned to match screenshot -->
        <div class="user-form-container">
            <h3>Add User</h3>
            <div class="divider"></div>

            <div class="user-form-layout">
                <div class="centered-form">
                    <div class="form-subheader">New User</div>

                    <form method="post" action="users.php" class="user-form">
                        <div class="form-input">
                            <input type="text" class="form-control" id="name" name="name" placeholder="Name" required>
                        </div>

                        <div class="form-input">
                            <input type="text" class="form-control" id="username" name="username" placeholder="Username"
                                required>
                        </div>

                        <div class="form-input">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password"
                                required>
                        </div>

                        <div class="form-input">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                placeholder="Confirm Password" required>
                        </div>

                        <div class="form-input">
                            <input type="text" class="form-control" id="phone" name="phone" placeholder="Phone Number">
                        </div>

                        <div class="form-input">
                            <select class="form-control" id="usertype" name="usertype" required>
                                <option value="" disabled selected>Type</option>
                                <option value="Admin">Admin</option>
                                <option value="Employee">Employee</option>
                            </select>
                        </div>

                        <div class="form-submit">
                            <button type="submit" name="register" class="add-button">Add</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    <?php elseif ($current_action == "edit" && $edit_user): ?>
        <!-- Edit User Form - Redesigned to match screenshot style -->
        <div class="user-form-container">
            <h3>Update User</h3>
            <div class="divider"></div>

            <div class="user-form-layout">
                <div class="centered-form">
                    <div class="form-subheader">Edit User</div>

                    <form method="post" action="users.php" class="user-form">
                        <input type="hidden" name="user_id" value="<?php echo $edit_user['userID']; ?>">

                        <div class="form-input">
                            <input type="text" class="form-control" id="name" name="name" placeholder="Name"
                                value="<?php echo $edit_user['name']; ?>" required>
                        </div>

                        <div class="form-input">
                            <input type="text" class="form-control" id="username" name="username" placeholder="Username"
                                value="<?php echo $edit_user['username']; ?>" required>
                        </div>

                        <div class="form-input">
                            <input type="password" class="form-control" id="password" name="password"
                                placeholder="Password (leave blank to keep current)">
                        </div>

                        <div class="form-input">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                placeholder="Confirm Password">
                        </div>

                        <div class="form-input">
                            <input type="text" class="form-control" id="phone" name="phone" placeholder="Phone Number"
                                value="<?php echo $edit_user['phone']; ?>">
                        </div>

                        <div class="form-input">
                            <select class="form-control" id="usertype" name="usertype" required>
                                <option value="Admin" <?php echo (strtolower($edit_user['usertype']) == 'admin' || strtolower($edit_user['usertype']) == 'administrator') ? 'selected' : ''; ?>>Admin
                                </option>
                                <option value="Employee" <?php echo (strtolower($edit_user['usertype']) == 'employee') ? 'selected' : ''; ?>>Employee</option>
                            </select>
                        </div>

                        <div class="form-submit">
                            <button type="submit" name="update" class="add-button">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- User List -->
        <div class="user-list-container">
            <!-- Add New User Button -->
            <a href="users.php?action=add" class="add-user-btn">
                <i class="fas fa-plus"></i>
            </a>

            <!-- User List Table -->
            <div class="user-table">
                <div class="user-table-header">
                    <div class="user-column-blank"></div> <!-- Blank column for plus button space -->
                    <div class="user-column-name">Name</div>
                    <div class="user-column-type">Type</div>
                    <div class="user-column-phone">Phone</div>
                    <div class="user-column-username">Username</div>
                    <div class="user-column-actions"></div>
                </div>

                <?php if (mysqli_num_rows($users_result) > 0): ?>
                    <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                        <div class="user-table-row">
                            <div class="user-column-blank"></div> <!-- Blank column for plus button space -->
                            <div class="user-column-name"><?php echo $user['name']; ?></div>
                            <div class="user-column-type">
                                <?php
                                if (strtolower($user['usertype']) == 'administrator' || strtolower($user['usertype']) == 'admin') {
                                    echo 'Admin';
                                } else {
                                    echo 'Employee';
                                }
                                ?>
                            </div>
                            <div class="user-column-phone"><?php echo $user['phone']; ?></div>
                            <div class="user-column-username"><?php echo $user['username']; ?></div>
                            <div class="user-column-actions">
                                <a href="users.php?edit=<?php echo $user['userID']; ?>" class="edit-icon">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                <a href="users.php?delete=<?php echo $user['userID']; ?>" class="delete-icon"
                                    onclick="return confirm('Are you sure you want to delete this user?')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="user-table-row">
                        <div class="user-column-blank"></div> <!-- Blank column for plus button space -->
                        <div class="no-users">No users found</div>
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