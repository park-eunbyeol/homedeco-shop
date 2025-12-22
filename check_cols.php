<?php
require_once 'includes/db.php';
$result = $conn->query("SELECT * FROM inquiries LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    echo "Columns: " . implode(", ", array_keys($row));
} else {
    echo "No rows or error: " . $conn->error;
}
?>