<?php
// api/cart-delete-multiple.php
header('Content-Type: application/json');
require_once '../includes/db.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'not_logged_in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$cart_ids = $data['cart_ids'];
$user_id = $_SESSION['user_id'];

if (empty($cart_ids)) {
    echo json_encode(['success' => false, 'message' => 'no_items']);
    exit;
}

$ids = implode(',', array_map('intval', $cart_ids));
$sql = "DELETE FROM cart WHERE cart_id IN ($ids) AND user_id = $user_id";

if ($conn->query($sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'database_error']);
}
?>