<?php

session_start();

// Destroy all session data
session_unset();
session_destroy();

// clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => "Logged out successfully"
]);
?>