<?php
require_once 'includes/db.php';
$result = $conn->query("DESCRIBE reviews");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . " - Default: " . $row['Default'] . "\n";
}
?>