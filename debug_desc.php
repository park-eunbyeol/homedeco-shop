<?php
require_once 'includes/db.php';
$res = $conn->query("SELECT product_id, name, description FROM products LIMIT 20");
while ($row = $res->fetch_assoc()) {
    echo "ID: " . $row['product_id'] . " | Name: " . $row['name'] . " | Desc: " . substr($row['description'], 0, 50) . "...\n";
}
