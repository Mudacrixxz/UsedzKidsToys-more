<?php
// Include database configuration
include 'config.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Connect to the database
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Start building the query
    $query = "SELECT * FROM products WHERE 1=1";
    $params = [];
    
    // Filter by category if specified
    if (isset($_GET['category']) && !empty($_GET['category'])) {
        $query .= " AND category_id = ?";
        $params[] = $_GET['category'];
    }
    
    // Filter by price range if specified
    if (isset($_GET['price_min']) && !empty($_GET['price_min'])) {
        $query .= " AND price >= ?";
        $params[] = $_GET['price_min'];
    }
    
    if (isset($_GET['price_max']) && !empty($_GET['price_max'])) {
        $query .= " AND price <= ?";
        $params[] = $_GET['price_max'];
    }
    
    // Filter by search term if specified
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $query .= " AND (name LIKE ? OR description LIKE ?)";
        $searchTerm = "%" . $_GET['search'] . "%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Add sorting
    $query .= " ORDER BY name ASC";
    
    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        // Create the parameter type string
        $types = str_repeat('s', count($params));
        
        // Bind parameters
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Fetch all products
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'price' => $row['price'],
            'category_id' => $row['category_id'],
            'image' => $row['image'],
            'condition' => $row['condition'],
            'stock' => $row['stock']
        ];
    }
    
    // Return the products as JSON
    echo json_encode($products);
    
} catch (Exception $e) {
    // Return error as JSON
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}

// Close the database connection if it exists
if (isset($conn)) {
    $conn->close();
}
?> 