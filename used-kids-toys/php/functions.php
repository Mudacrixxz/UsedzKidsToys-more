<?php
/**
 * Helper functions for Used Kids Toys website
 */

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect to login page if user is not logged in
 * 
 * @param string $redirect_to URL to redirect to after login
 * @return void
 */
function requireLogin($redirect_to = '') {
    if (!isLoggedIn()) {
        $redirect_param = $redirect_to ? '?redirect=' . urlencode($redirect_to) : '';
        header('Location: ../login.html' . $redirect_param);
        exit;
    }
}

/**
 * Get current user ID
 * 
 * @return int|null User ID if logged in, null otherwise
 */
function getUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * Get current user data
 * 
 * @return array|null User data if logged in, null otherwise
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    global $db_host, $db_user, $db_pass, $db_name;
    
    $user_id = $_SESSION['user_id'];
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        logError('Database connection failed: ' . $conn->connect_error);
        return null;
    }
    
    $stmt = $conn->prepare("SELECT id, name, email, created_at, is_verified, last_login FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
        return $user;
    }
    
    $stmt->close();
    $conn->close();
    return null;
}

/**
 * Check if remember me token is valid
 * 
 * @return bool True if token is valid, false otherwise
 */
function checkRememberToken() {
    if (!isset($_COOKIE['remember_token'])) return false;
    
    global $db_host, $db_user, $db_pass, $db_name;
    
    $token = $_COOKIE['remember_token'];
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        logError('Database connection failed: ' . $conn->connect_error);
        return false;
    }
    
    $stmt = $conn->prepare("SELECT user_id, expiry FROM user_tokens WHERE token = ? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $token_data = $result->fetch_assoc();
        $stmt->close();
        
        // Check if token is expired
        if (strtotime($token_data['expiry']) < time()) {
            // Delete expired token
            $stmt = $conn->prepare("DELETE FROM user_tokens WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $stmt->close();
            $conn->close();
            setcookie('remember_token', '', time() - 3600, '/');
            return false;
        }
        
        // Token is valid, get user data
        $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $token_data['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Create session for remembered user
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            
            // Update last login timestamp
            $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            
            $stmt->close();
            $conn->close();
            return true;
        }
    }
    
    $conn->close();
    return false;
}

/**
 * Log an error message to the error log
 * 
 * @param string $message Error message to log
 * @return void
 */
function logError($message) {
    $logFile = __DIR__ . '/logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    
    // Check if logs directory exists, create it if not
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }
    
    // Append to log file
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Generate a secure random token
 * 
 * @param int $length Length of token to generate
 * @return string Random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Get user's wishlist items
 * 
 * @param int $user_id User ID
 * @return array Array of wishlist product IDs
 */
function getWishlist($user_id = null) {
    if ($user_id === null) {
        $user_id = getUserId();
        if ($user_id === null) return [];
    }
    
    global $db_host, $db_user, $db_pass, $db_name;
    
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        logError('Database connection failed: ' . $conn->connect_error);
        return [];
    }
    
    $stmt = $conn->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $wishlist = [];
    while ($row = $result->fetch_assoc()) {
        $wishlist[] = $row['product_id'];
    }
    
    $stmt->close();
    $conn->close();
    
    return $wishlist;
}

/**
 * Get user's recently viewed products
 * 
 * @param int $user_id User ID
 * @param int $limit Maximum number of products to return
 * @return array Array of recently viewed product IDs
 */
function getRecentlyViewed($user_id = null, $limit = 5) {
    if ($user_id === null) {
        $user_id = getUserId();
        if ($user_id === null) return [];
    }
    
    global $db_host, $db_user, $db_pass, $db_name;
    
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        logError('Database connection failed: ' . $conn->connect_error);
        return [];
    }
    
    $stmt = $conn->prepare("SELECT product_id FROM recently_viewed WHERE user_id = ? ORDER BY viewed_at DESC LIMIT ?");
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $recently_viewed = [];
    while ($row = $result->fetch_assoc()) {
        $recently_viewed[] = $row['product_id'];
    }
    
    $stmt->close();
    $conn->close();
    
    return $recently_viewed;
}

/**
 * Add product to recently viewed
 * 
 * @param int $product_id Product ID
 * @param int $user_id User ID
 * @return bool True if successful, false otherwise
 */
function addToRecentlyViewed($product_id, $user_id = null) {
    if ($user_id === null) {
        $user_id = getUserId();
        if ($user_id === null) return false;
    }
    
    global $db_host, $db_user, $db_pass, $db_name;
    
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        logError('Database connection failed: ' . $conn->connect_error);
        return false;
    }
    
    // Delete existing entry if exists
    $stmt = $conn->prepare("DELETE FROM recently_viewed WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    
    // Insert new entry
    $stmt = $conn->prepare("INSERT INTO recently_viewed (user_id, product_id, viewed_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $user_id, $product_id);
    $result = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return $result;
}

/**
 * Format price with currency symbol
 * 
 * @param float $price Price to format
 * @param string $currency Currency code
 * @return string Formatted price
 */
function formatPrice($price, $currency = '£') {
    return $currency . number_format($price, 2);
}
?> 