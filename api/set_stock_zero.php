<?php
require_once '../includes/db.php';

// JSON 응답 헤더
header('Content-Type: application/json');

// POST 데이터 확인
$data = json_decode(file_get_contents('php://input'), true);
$product_id = isset($data['product_id']) ? (int) $data['product_id'] : 0;

if ($product_id > 0) {
    // 상품 재고를 0으로 업데이트
    $sql = "UPDATE products SET stock = 0 WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Stock set to 0', 'id' => $product_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
}

$conn->close();
?>