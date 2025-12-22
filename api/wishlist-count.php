<?php
// api/wishlist-count.php
header('Content-Type: application/json');
require_once '../includes/db.php';

if (!is_logged_in()) {
    echo json_encode(['count' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT COUNT(*) as total FROM wishlist WHERE user_id = $user_id";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

echo json_encode(['count' => $row['total'] ?? 0]);
?>