<?php
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    logActivity($_SESSION['user_id'], 'Logout', 'User logged out');
}

session_destroy();
redirect('index.php');
?>