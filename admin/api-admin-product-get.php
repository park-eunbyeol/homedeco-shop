<?php
session_start();
require_once '../includes/db.php';

// 관리자 권한 확인
if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$sql = "SELECT * FROM products WHERE product_id = $product_id";
$result = $conn->query($sql);

if ($result && $row = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'data' => $row]);
} else {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
}
?>