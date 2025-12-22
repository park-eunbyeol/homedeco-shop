<?php
session_start();
require_once '../includes/db.php';

// 관리자 권한 확인
if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$product_id = isset($data['product_id']) ? (int) $data['product_id'] : 0;
$is_active = isset($data['is_active']) ? (int) $data['is_active'] : 0;

if ($product_id > 0) {
    if ($is_active == 0) {
        $sql = "UPDATE products SET is_active = 0, stock = 0 WHERE product_id = $product_id";
    } else {
        $sql = "UPDATE products SET is_active = 1 WHERE product_id = $product_id";
    }
    if ($conn->query($sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
}
?>