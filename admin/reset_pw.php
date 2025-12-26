<?php
require_once 'includes/db.php';
$pw = password_hash('admin123', PASSWORD_DEFAULT);
$conn->query("UPDATE users SET password = '$pw' WHERE email IN ('admin@homedeco.com', 'manager@homedeco.com')");
echo "Passwords updated to admin123\n";
?>