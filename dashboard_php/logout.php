<?php
/**
 * Logout - Secure session termination
 * Calma Courier Management System
 */

// Start session to access it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection (optional - for logging)
$connect = @mysqli_connect("localhost", "root", "", "courier_management");

// Log logout activity before destroying session
if ($connect && isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    $user_id = $_SESSION['user_id'];
    $user_type = $_SESSION['user_type'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    @mysqli_query($connect, "INSERT INTO user_activity_logs (user_id, user_type, action, ip_address, created_at) 
                            VALUES ('$user_id', '$user_type', 'Logout', '$ip', NOW())");
}

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000, 
        $params['path'], 
        $params['domain'], 
        $params['secure'], 
        $params['httponly']
    );
}

// Destroy any other session cookies
$session_cookies = ['PHPSESSID', 'PHPSESSIDNEW'];
foreach ($session_cookies as $cookie_name) {
    if (isset($_COOKIE[$cookie_name])) {
        setcookie($cookie_name, '', time() - 42000, '/');
    }
}

// Destroy the session
session_destroy();

// Add headers to prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate, post-check=0, pre-check=0');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Robots-Tag: noindex, nofollow');

// Close database connection
if ($connect) {
    mysqli_close($connect);
}

// Redirect to login page
header('Location: login.php');
exit;
?>
