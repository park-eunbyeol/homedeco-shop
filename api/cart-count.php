<?php
// api/cart-count.php
header('Content-Type: application/json');
require_once '../includes/db.php';

if (!is_logged_in()) {
    $count = 0;
    if (isset($_SESSION['guest_cart'])) {
        foreach ($_SESSION['guest_cart'] as $item) {
            $count += $item['quantity'];
        }
    }
    echo json_encode(['success' => true, 'count' => $count]);
    exit;
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT SUM(quantity) as count FROM cart_items WHERE user_id = $user_id";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

echo json_encode(['success' => true, 'count' => (int) ($row['count'] ?? 0)]);
?>

---