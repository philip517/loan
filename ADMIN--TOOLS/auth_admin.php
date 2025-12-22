<?php
// admin/auth_admin.php


// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    // Not logged in or not admin
    header("Location: ../index.php");
    exit();
}
?>
