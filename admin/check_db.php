<?php
require_once 'includes/db.php';
$res = $conn->query("SELECT email, admin_level FROM users");
while ($row = $res->fetch_assoc()) {
    echo "Email: " . $row['email'] . " | Level: " . $row['admin_level'] . "\n";
}
?>