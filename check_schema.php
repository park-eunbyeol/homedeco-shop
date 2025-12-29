<?php
require_once 'includes/db.php';
$result = $conn->query("DESCRIBE products");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>