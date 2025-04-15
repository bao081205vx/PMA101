<?php
session_start();

// Clear all session data
session_unset();
session_destroy();

// Remove remember token cookie if exists
if (isset($_COOKIE['remember_token'])) {
    require_once '../config/database.php';
    
    // Delete token from database
    $stmt = $conn->prepare("DELETE FROM user_tokens WHERE token = ?");
    $stmt->execute([$_COOKIE['remember_token']]);
    
    // Delete cookie
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}

// Redirect to login page
header('Location: login.php');
exit;
