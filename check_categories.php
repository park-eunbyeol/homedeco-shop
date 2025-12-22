<?php
require_once 'includes/db.php';
$res = $conn->query("SELECT category_id, COUNT(*) as cnt FROM products GROUP BY category_id");
echo "Category Counts:\n";
while ($row = $res->fetch_assoc()) {
    echo "Category " . $row['category_id'] . ": " . $row['cnt'] . " items\n";
}
