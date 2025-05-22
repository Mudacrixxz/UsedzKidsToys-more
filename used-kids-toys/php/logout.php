<?php
session_start();

// Initialize response array
$response = [
    'success' => true,
    'message' => 'You have been logged out successfully.',
    'redirect' => '../index.html'
];

// Clear all session variables
$_SESSION = [];

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Delete remember me cookie if it exists
if (isset($_COOKIE['remember_token'])) {
    // Connect to database to remove token
    require_once 'config.php';
    $token = $_COOKIE['remember_token'];
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if (!$conn->connect_error) {
        $stmt = $conn->prepare("DELETE FROM user_tokens WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    }
    
    // Delete cookie
    setcookie('remember_token', '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Return JSON response if API request
if (isset($_GET['api']) && $_GET['api'] === 'true') {
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    // Redirect to homepage
    header('Location: ../index.html');
}
exit; 