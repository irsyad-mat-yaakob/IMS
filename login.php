<?php
// Include database connection
include 'config/db_connection.php';

// Initialize error message variable
$error = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // SQL query to check username and password
    $sql = "SELECT * FROM User WHERE username = '$username' AND password = '$password'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) == 1) {
        // User exists, start session
        session_start();
        $row = mysqli_fetch_assoc($result);
        
        // Store user data in session
        $_SESSION['userID'] = $row['userID'];
        $_SESSION['name'] = $row['name'];
        $_SESSION['usertype'] = $row['usertype'];
        
        // Redirect based on usertype
        if (strtolower($row['usertype']) == 'administrator' || strtolower($row['usertype']) == 'admin') {
            header("Location: admin/index.php");
        } else {
            header("Location: employee/index.php");
        }
        exit;
    } else {
        // Invalid credentials
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Inventory Management System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="login-container">
        <h1>Welcome to Inventory Management System!</h1>
        
        <?php if(!empty($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" class="login-btn">Login</button>
        </form>
    </div>
</body>
</html>