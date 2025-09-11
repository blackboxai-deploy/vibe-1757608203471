<?php
require_once 'config/config.php';
require_once 'includes/auth.php';

// Logout user
$auth->logout();

// Redirect to home page
header('Location: index.php?logged_out=1');
exit();
?>