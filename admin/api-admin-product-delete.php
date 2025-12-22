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

if ($product_id > 0) {
    // 실제 삭제 대신 상태값 변경을 선호할 수도 있지만, 여기서는 요청대로 삭제 처리
    $sql = "DELETE FROM products WHERE product_id = $product_id";
    if ($conn->query($sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
}
?>