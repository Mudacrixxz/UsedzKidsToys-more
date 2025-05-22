<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'errors' => [],
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

// Process registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $terms = isset($_POST['terms']) ? true : false;
    
    // Validate form data
    $errors = [];
    
    if (empty($name)) {
        $errors['name'] = 'Please enter your name.';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Please enter a password.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Password must contain at least one uppercase letter, one lowercase letter, and one number.';
    }
    
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }
    
    if (!$terms) {
        $errors['terms'] = 'You must agree to the Terms & Conditions and Privacy Policy.';
    }
    
    // Check if email is already registered
    if (empty($errors['email'])) {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        
        // Check connection
        if ($conn->connect_error) {
            $errors['database'] = 'Database connection failed. Please try again later.';
            logError('Database connection failed: ' . $conn->connect_error);
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $errors['email'] = 'Email address is already registered.';
            }
            
            $stmt->close();
        }
    }
    
    // If no errors, create user account
    if (empty($errors)) {
        // Connect to database if not already connected
        if (!isset($conn) || $conn->connect_error) {
            $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        }
        
        // Check connection
        if ($conn->connect_error) {
            $response['message'] = 'Database connection failed. Please try again later.';
            logError('Database connection failed: ' . $conn->connect_error);
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate verification token
            $verification_token = bin2hex(random_bytes(32));
            
            // Insert user into database
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, verification_token, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $verification_token);
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                
                // Create user session
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                
                // Send verification email
                $verification_link = "https://" . $_SERVER['HTTP_HOST'] . "/php/verify.php?token=$verification_token&email=" . urlencode($email);
                $subject = "Verify Your Email - Used Kids Toys";
                
                $message = "
                <html>
                <head>
                    <title>Email Verification</title>
                </head>
                <body>
                    <h2>Welcome to Used Kids Toys!</h2>
                    <p>Thank you for registering. Please click the link below to verify your email address:</p>
                    <p><a href='$verification_link'>Verify Email Address</a></p>
                    <p>If you did not create an account, please ignore this email.</p>
                    <p>Thank you,<br>Used Kids Toys Team</p>
                </body>
                </html>
                ";
                
                // Set email headers
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= "From: Used Kids Toys <noreply@usedkidstoys.com>" . "\r\n";
                
                // Send email
                // Uncomment the next line when ready to send emails
                // mail($email, $subject, $message, $headers);
                
                // Set success response
                $response['success'] = true;
                $response['message'] = 'Registration successful! Please check your email to verify your account.';
                $response['redirect'] = '../account.html';
                
            } else {
                $response['message'] = 'Registration failed. Please try again.';
                logError('Registration failed: ' . $stmt->error);
            }
            
            $stmt->close();
            $conn->close();
        }
    } else {
        $response['message'] = 'Please correct the errors below.';
        $response['errors'] = $errors;
    }
    
    // Return JSON response if API request
    if (isset($_GET['api']) && $_GET['api'] === 'true') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } else {
        // Set message in session for non-AJAX request
        if (!$response['success']) {
            $_SESSION['register_error'] = $response['message'];
            $_SESSION['register_errors'] = $response['errors'];
            $_SESSION['register_form_data'] = [
                'name' => $name,
                'email' => $email
            ];
            header('Location: ../login.html');
        } else {
            header('Location: ' . $response['redirect']);
        }
        exit;
    }
} else {
    // Not a POST request, redirect to registration page
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
?> 