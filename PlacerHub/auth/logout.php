<?php
require_once '../config/config.php';

if (isLoggedIn()) {
    // Log activity
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, ip_address, user_agent) VALUES (?, 'logout', ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
    } catch (Exception $e) {
        logError($e->getMessage());
    }
}

// Destroy session
session_destroy();

// Redirect to login
redirect(SITE_URL . '/auth/login.php');
?>
