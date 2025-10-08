<?php
/**
 * Quick test for admin login functionality
 */
require_once 'site-access-control.php';

$accessControl = new SiteAccessControl();

echo "<h2>Admin Login Test</h2>";
echo "<p><strong>Testing password: Rakeshmurali@10</strong></p>";

$result = $accessControl->verifyAdminPassword('Rakeshmurali@10');
if ($result) {
    echo "<p style='color: green;'>✅ Password verification: SUCCESS</p>";
    $accessControl->grantAdminAccess();
    echo "<p style='color: green;'>✅ Admin access granted</p>";
} else {
    echo "<p style='color: red;'>❌ Password verification: FAILED</p>";
}

echo "<p><a href='admin-login.html'>Go to Admin Login</a></p>";
echo "<p><a href='admin-access.html'>Go to Admin Access</a></p>";
?>
