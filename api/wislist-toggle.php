<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'not_logged_in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
$user_id = $_SESSION['user_id'];

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'invalid_product']);
    exit;
}

// 이미 찜했는지 확인
$check_sql = "SELECT wishlist_id FROM wishlist WHERE user_id = $user_id AND product_id = $product_id";
$check_result = $conn->query($check_sql);

if ($check_result->num_rows > 0) {
    // 찜 제거
    $delete_sql = "DELETE FROM wishlist WHERE user_id = $user_id AND product_id = $product_id";
    if ($conn->query($delete_sql)) {
        echo json_encode(['success' => true, 'action' => 'removed']);
    } else {
        echo json_encode(['success' => false, 'message' => 'database_error']);
    }
} else {
    // 찜 추가
    $insert_sql = "INSERT INTO wishlist (user_id, product_id) VALUES ($user_id, $product_id)";
    if ($conn->query($insert_sql)) {
        echo json_encode(['success' => true, 'action' => 'added']);
    } else {
        echo json_encode(['success' => false, 'message' => 'database_error']);
    }
}
?>