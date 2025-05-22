<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Initialize response array
$response = [
    'authenticated' => false,
    'name' => '',
    'email' => '',
    'id' => null,
    'message' => 'Not authenticated'
];

// Check if user is logged in via session
if (isLoggedIn()) {
    $response['authenticated'] = true;
    $response['name'] = $_SESSION['user_name'];
    $response['email'] = $_SESSION['user_email'];
    $response['id'] = $_SESSION['user_id'];
    $response['message'] = 'Authenticated via session';
} 
// Otherwise check for remember me token
else if (checkRememberToken()) {
    $response['authenticated'] = true;
    $response['name'] = $_SESSION['user_name'];
    $response['email'] = $_SESSION['user_email'];
    $response['id'] = $_SESSION['user_id'];
    $response['message'] = 'Authenticated via remember token';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 