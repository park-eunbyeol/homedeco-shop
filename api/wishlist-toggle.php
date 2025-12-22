<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

// 로그인 확인
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// JSON 또는 POST 데이터 처리
$input_json = json_decode(file_get_contents('php://input'), true);
if ($input_json && isset($input_json['product_id'])) {
    $product_id = (int) $input_json['product_id'];
} else {
    $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
}

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => '잘못된 상품입니다.']);
    exit;
}

// 이미 찜했는지 확인
$check_sql = "SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $user_id, $product_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    // 이미 찜한 경우 → 삭제
    $delete_sql = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $user_id, $product_id);

    if ($delete_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'action' => 'removed',
            'message' => '찜 목록에서 제거되었습니다.'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => '삭제 실패']);
    }
} else {
    // 찜하지 않은 경우 → 추가
    $insert_sql = "INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ii", $user_id, $product_id);

    if ($insert_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'action' => 'added',
            'message' => '찜 목록에 추가되었습니다.'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => '추가 실패']);
    }
}
?>