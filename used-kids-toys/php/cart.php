<?php
// Include database configuration
include 'config.php';

// Start session for cart management
session_start();

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Set headers for JSON response
header('Content-Type: application/json');

// Connect to the database
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Determine the action to perform
    $action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');
    
    switch ($action) {
        case 'add':
            // Add item to cart
            if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
                throw new Exception("Product ID and quantity are required");
            }
            
            $productId = $_POST['product_id'];
            $quantity = (int)$_POST['quantity'];
            
            // Check if product exists and is in stock
            $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND stock >= ?");
            $stmt->bind_param("ii", $productId, $quantity);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Product not available or insufficient stock");
            }
            
            // Add to cart or update quantity if already in cart
            if (isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId] += $quantity;
            } else {
                $_SESSION['cart'][$productId] = $quantity;
            }
            
            // Return success
            echo json_encode([
                'success' => true,
                'message' => 'Product added to cart'
            ]);
            break;
            
        case 'remove':
            // Remove item from cart
            if (!isset($_POST['product_id'])) {
                throw new Exception("Product ID is required");
            }
            
            $productId = $_POST['product_id'];
            
            // Remove from cart if exists
            if (isset($_SESSION['cart'][$productId])) {
                unset($_SESSION['cart'][$productId]);
            }
            
            // Return success
            echo json_encode([
                'success' => true,
                'message' => 'Product removed from cart'
            ]);
            break;
            
        case 'increase':
            // Increase item quantity
            if (!isset($_POST['product_id'])) {
                throw new Exception("Product ID is required");
            }
            
            $productId = $_POST['product_id'];
            
            // Increase quantity if exists
            if (isset($_SESSION['cart'][$productId])) {
                // Check stock before increasing
                $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
                $stmt->bind_param("i", $productId);
                $stmt->execute();
                $result = $stmt->get_result();
                $product = $result->fetch_assoc();
                
                if ($_SESSION['cart'][$productId] < $product['stock']) {
                    $_SESSION['cart'][$productId]++;
                } else {
                    throw new Exception("Cannot increase quantity, insufficient stock");
                }
            }
            
            // Return success
            echo json_encode([
                'success' => true,
                'message' => 'Quantity increased'
            ]);
            break;
            
        case 'decrease':
            // Decrease item quantity
            if (!isset($_POST['product_id'])) {
                throw new Exception("Product ID is required");
            }
            
            $productId = $_POST['product_id'];
            
            // Decrease quantity if exists
            if (isset($_SESSION['cart'][$productId])) {
                if ($_SESSION['cart'][$productId] > 1) {
                    $_SESSION['cart'][$productId]--;
                } else {
                    unset($_SESSION['cart'][$productId]);
                }
            }
            
            // Return success
            echo json_encode([
                'success' => true,
                'message' => 'Quantity decreased'
            ]);
            break;
            
        case 'view':
            // View cart contents
            $cartItems = [];
            $total = 0;
            
            // Get details of each item in cart
            foreach ($_SESSION['cart'] as $productId => $quantity) {
                $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
                $stmt->bind_param("i", $productId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $product = $result->fetch_assoc();
                    $itemTotal = $product['price'] * $quantity;
                    $total += $itemTotal;
                    
                    $cartItems[] = [
                        'id' => $product['id'],
                        'name' => $product['name'],
                        'price' => $product['price'],
                        'image' => $product['image'],
                        'quantity' => $quantity,
                        'total' => $itemTotal
                    ];
                }
            }
            
            // Return cart contents
            echo json_encode([
                'items' => $cartItems,
                'total' => $total
            ]);
            break;
            
        case 'count':
            // Count items in cart
            $count = 0;
            
            foreach ($_SESSION['cart'] as $quantity) {
                $count += $quantity;
            }
            
            // Return cart count
            echo json_encode([
                'count' => $count
            ]);
            break;
            
        case 'clear':
            // Clear cart
            $_SESSION['cart'] = [];
            
            // Return success
            echo json_encode([
                'success' => true,
                'message' => 'Cart cleared'
            ]);
            break;
            
        default:
            throw new Exception("Invalid action");
    }
    
} catch (Exception $e) {
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