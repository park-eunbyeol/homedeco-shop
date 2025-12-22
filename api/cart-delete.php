<?php
// api/cart-delete.php
header('Content-Type: application/json');
require_once '../includes/db.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'not_logged_in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$cart_id = (int)$data['cart_id'];
$user_id = $_SESSION['user_id'];

$sql = "DELETE FROM cart WHERE cart_id = $cart_id AND user_id = $user_id";
if ($conn->query($sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'database_error']);
}
?>
