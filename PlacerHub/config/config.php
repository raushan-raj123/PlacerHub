<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/database.php';

// Site configuration
define('SITE_NAME', 'PlacerHub');
define('SITE_URL', 'http://localhost/PlacerHub');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);

// Security settings
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Pagination
define('RECORDS_PER_PAGE', 10);

// Email settings (configure these in production)
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');

// Utility functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function isStudent() {
    return isLoggedIn() && $_SESSION['role'] === 'student';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/auth/login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/dashboard/');
        exit();
    }
}

function redirect($url) {
    header('Location: ' . $url);
    exit();
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('M d, Y h:i A', strtotime($datetime));
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}

function generateTicketId() {
    return 'TKT-' . strtoupper(substr(uniqid(), -8));
}

// Error handling
function logError($message, $file = '', $line = '') {
    $log = date('Y-m-d H:i:s') . " - Error: $message";
    if ($file) $log .= " in $file";
    if ($line) $log .= " on line $line";
    $log .= PHP_EOL;
    
    error_log($log, 3, __DIR__ . '/../logs/error.log');
}

// Create necessary directories
$directories = [
    __DIR__ . '/../uploads/resumes/',
    __DIR__ . '/../uploads/photos/',
    __DIR__ . '/../uploads/documents/',
    __DIR__ . '/../logs/'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}
?>
