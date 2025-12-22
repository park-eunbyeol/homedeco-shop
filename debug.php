<?php
require_once 'includes/db.php';
$cnt = $conn->query("SELECT COUNT(*) as cnt FROM products")->fetch_assoc()['cnt'];
echo "Product Count: " . $cnt . "\n";
$rcnt = $conn->query("SELECT COUNT(*) as cnt FROM reviews")->fetch_assoc()['cnt'];
echo "Review Count: " . $rcnt . "\n";
$res = $conn->query("SELECT name FROM products LIMIT 5");
while ($row = $res->fetch_assoc()) {
    echo "Item: " . $row['name'] . "\n";
}