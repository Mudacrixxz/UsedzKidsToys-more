<?php
// Include database configuration
include 'config.php';

// Start session for cart management
session_start();

// Set headers for JSON response
header('Content-Type: application/json');

// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Your cart is empty'
    ]);
    exit;
}

// Connect to the database
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Validate request data
    if (!isset($_POST['name']) || empty($_POST['name'])) {
        throw new Exception("Name is required");
    }
    
    if (!isset($_POST['email']) || empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Valid email is required");
    }
    
    if (!isset($_POST['address']) || empty($_POST['address'])) {
        throw new Exception("Address is required");
    }
    
    // Get form data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    // Check if user exists, if not create new user
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // User exists, get user ID
        $user = $result->fetch_assoc();
        $userId = $user['id'];
        
        // Update user information
        $stmt = $conn->prepare("UPDATE users SET name = ?, address = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $address, $userId);
        $stmt->execute();
    } else {
        // Create new user
        $stmt = $conn->prepare("INSERT INTO users (name, email, address) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $address);
        $stmt->execute();
        $userId = $conn->insert_id;
    }
    
    // Calculate order total
    $total = 0;
    $orderItems = [];
    
    foreach ($_SESSION['cart'] as $productId => $quantity) {
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
            
            // Check if product is still in stock
            if ($product['stock'] < $quantity) {
                throw new Exception("Product '" . $product['name'] . "' is out of stock or has insufficient quantity");
            }
            
            $itemTotal = $product['price'] * $quantity;
            $total += $itemTotal;
            
            $orderItems[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $product['price']
            ];
            
            // Update stock
            $newStock = $product['stock'] - $quantity;
            $stmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
            $stmt->bind_param("ii", $newStock, $productId);
            $stmt->execute();
        }
    }
    
    // Create new order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total, status, shipping_address) VALUES (?, ?, 'pending', ?)");
    $stmt->bind_param("ids", $userId, $total, $address);
    $stmt->execute();
    $orderId = $conn->insert_id;
    
    // Create order items
    foreach ($orderItems as $item) {
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $orderId, $item['product_id'], $item['quantity'], $item['price']);
        $stmt->execute();
    }
    
    // Process payment (in a real application, this would involve a payment gateway)
    // For demonstration purposes, we'll just assume the payment was successful
    
    // Update order status to paid
    $stmt = $conn->prepare("UPDATE orders SET status = 'paid' WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Clear the cart
    $_SESSION['cart'] = [];
    
    // Return success with order information
    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully',
        'order_id' => $orderId,
        'total' => $total
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if an error occurred
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    // Return error as JSON
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Close the database connection if it exists
if (isset($conn)) {
    $conn->close();
}
?> 