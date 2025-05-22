<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'redirect' => ''
];

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // User is already logged in, redirect to account page
    $response['success'] = true;
    $response['redirect'] = '../account.html';
    
    if (isset($_GET['api']) && $_GET['api'] === 'true') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } else {
        header('Location: ../account.html');
        exit;
    }
}

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validate form data
    if (empty($email) || empty($password)) {
        $response['message'] = 'Please fill in all fields.';
    } else {
        // Connect to database
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        
        // Check connection
        if ($conn->connect_error) {
            $response['message'] = 'Database connection failed. Please try again later.';
            logError('Database connection failed: ' . $conn->connect_error);
        } else {
            // Prepare statement to prevent SQL injection
            $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Password is correct, create session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    
                    // Set remember me cookie if checked
                    if ($remember) {
                        $token = generateToken();
                        $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                        
                        // Store token in database
                        $stmt = $conn->prepare("INSERT INTO user_tokens (user_id, token, expiry) VALUES (?, ?, ?)");
                        $stmt->bind_param("iss", $user['id'], $token, date('Y-m-d H:i:s', $expiry));
                        $stmt->execute();
                        
                        // Set cookie
                        setcookie('remember_token', $token, $expiry, '/', '', true, true);
                    }
                    
                    // Update last login timestamp
                    $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->bind_param("i", $user['id']);
                    $stmt->execute();
                    
                    $response['success'] = true;
                    $response['message'] = 'Login successful!';
                    $response['redirect'] = '../account.html';
                    
                } else {
                    // Password is incorrect
                    $response['message'] = 'Invalid email or password.';
                }
            } else {
                // No user found with that email
                $response['message'] = 'Invalid email or password.';
            }
            
            $stmt->close();
            $conn->close();
        }
    }
    
    // Return JSON response if API request
    if (isset($_GET['api']) && $_GET['api'] === 'true') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } else {
        // Set message in session for non-AJAX request
        if (!$response['success']) {
            $_SESSION['login_error'] = $response['message'];
            header('Location: ../login.html');
        } else {
            header('Location: ' . $response['redirect']);
        }
        exit;
    }
} else {
    // Not a POST request, redirect to login page
    header('Location: ../login.html');
    exit;
}

// Helper function for logging errors
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

// Helper function to generate a secure token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}
?> 