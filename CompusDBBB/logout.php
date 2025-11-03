<?php
require_once 'config/config.php';

$auth = new Auth();
$result = $auth->logout();

// Redirect to login page
header('Location: ' . BASE_URL . 'login.php?message=logged_out');
exit;
?>
