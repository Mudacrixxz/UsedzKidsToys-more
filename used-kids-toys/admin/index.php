<?php
// Start session for admin authentication
session_start();

// Include database configuration
include '../php/config.php';

// Check if admin is logged in
$isLoggedIn = isset($_SESSION['admin_id']);

// Handle login form submission
if (!$isLoggedIn && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Simple authentication (in a real app, use proper hashing and secure storage)
    if ($username === 'admin' && $password === 'password123') {
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_username'] = 'admin';
        $isLoggedIn = true;
    } else {
        $error = "Invalid username or password";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Used Kids Toys and More</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .admin-nav {
            background-color: #f5f5f5;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 5px;
        }
        
        .admin-nav ul {
            display: flex;
            list-style: none;
            gap: 20px;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .admin-table th, .admin-table td {
            padding: 12px 15px;
            border: 1px solid #ddd;
            text-align: left;
        }
        
        .admin-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        
        .admin-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .login-form {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <?php if ($isLoggedIn): ?>
        <div class="admin-container">
            <div class="admin-header">
                <div class="admin-logo">
                    <img src="../images/logo.png" alt="Used Kids Toys and More" class="logo-image">
                    <h1>Admin Panel</h1>
                </div>
                <div>
                    <span>Welcome, Admin</span>
                    <a href="?logout=1" class="btn">Logout</a>
                </div>
            </div>
            
            <div class="admin-nav">
                <ul>
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="index.php?view=products">Products</a></li>
                    <li><a href="index.php?view=orders">Orders</a></li>
                    <li><a href="index.php?view=users">Users</a></li>
                </ul>
            </div>
            
            <?php
            // Determine which section to display
            $view = $_GET['view'] ?? 'dashboard';
            
            switch ($view) {
                case 'products':
                    // Display products management
                    include 'products.php';
                    break;
                    
                case 'orders':
                    // Display orders management
                    echo "<h2>Orders Management</h2>";
                    echo "<p>Order management functionality will be implemented here.</p>";
                    break;
                    
                case 'users':
                    // Display users management
                    echo "<h2>Users Management</h2>";
                    echo "<p>User management functionality will be implemented here.</p>";
                    break;
                    
                default:
                    // Display dashboard
                    ?>
                    <h2>Dashboard</h2>
                    
                    <div class="dashboard-stats">
                        <div class="stat-card">
                            <h3>Products</h3>
                            <?php
                            // Connect to database
                            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                            $result = $conn->query("SELECT COUNT(*) as count FROM products");
                            $productCount = $result->fetch_assoc()['count'] ?? 0;
                            ?>
                            <p class="stat-number"><?php echo $productCount; ?></p>
                        </div>
                        
                        <div class="stat-card">
                            <h3>Orders</h3>
                            <?php
                            $result = $conn->query("SELECT COUNT(*) as count FROM orders");
                            $orderCount = $result->fetch_assoc()['count'] ?? 0;
                            ?>
                            <p class="stat-number"><?php echo $orderCount; ?></p>
                        </div>
                        
                        <div class="stat-card">
                            <h3>Users</h3>
                            <?php
                            $result = $conn->query("SELECT COUNT(*) as count FROM users");
                            $userCount = $result->fetch_assoc()['count'] ?? 0;
                            $conn->close();
                            ?>
                            <p class="stat-number"><?php echo $userCount; ?></p>
                        </div>
                    </div>
                    
                    <h3>Recent Orders</h3>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6">No orders yet.</td>
                            </tr>
                        </tbody>
                    </table>
                    <?php
                    break;
            }
            ?>
        </div>
    <?php else: ?>
        <!-- Login form -->
        <div class="login-form">
            <h2>Admin Login</h2>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="post" action="index.php">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="login" class="btn">Login</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</body>
</html> 