<?php
// Enhanced local development admin bypass
// Generated on: 11-10-2025  9:08:01.09
session_start();
$_SESSION['attral_admin_access'] = true;
$_SESSION['attral_admin_user'] = 'local-admin';
$_SESSION['attral_admin_login_time'] = time();
$_SESSION['attral_admin_username'] = 'attral';
$_SESSION['attral_admin_password'] = 'Rakeshmurali@10';
$_SESSION['attral_admin_permissions'] = ['all'];
echo 'Local admin access enabled with full permissions';
echo 'Username: attral';
echo 'Password: Rakeshmurali@10';
?>
