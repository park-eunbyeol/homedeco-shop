<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$review_id = isset($input['review_id']) ? (int) $input['review_id'] : 0;

if ($review_id <= 0) {
    echo json_encode(['success' => false, 'message' => '유효하지 않은 리뷰 ID입니다.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// 리뷰 존재 여부 및 권한 확인
$check_sql = "SELECT product_id, user_id FROM reviews WHERE review_id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("i", $review_id);
$stmt->execute();
$result = $stmt->get_result();
$review = $result->fetch_assoc();

if (!$review) {
    echo json_encode(['success' => false, 'message' => '리뷰를 찾을 수 없습니다.']);
    exit;
}

// 본인이 작성한 리뷰이거나 관리자인 경우에만 삭제 가능 (is_admin() 함수가 있다면 사용)
if ($review['user_id'] != $user_id && !is_admin()) {
    echo json_encode(['success' => false, 'message' => '삭제 권한이 없습니다.']);
    exit;
}

$product_id = $review['product_id'];

// 리뷰 삭제
$delete_sql = "DELETE FROM reviews WHERE review_id = ?";
$stmt = $conn->prepare($delete_sql);
$stmt->bind_param("i", $review_id);

if ($stmt->execute()) {
    // 상품 평점 및 리뷰 수 재계산 및 업데이트
    $stats_query = "SELECT COUNT(*) as cnt, AVG(rating) as avg_rating FROM reviews WHERE product_id = $product_id AND is_approved = 1";
    $stats_result = $conn->query($stats_query);
    $stats = $stats_result->fetch_assoc();

    $cnt = (int) ($stats['cnt'] ?? 0);
    $avg = (float) ($stats['avg_rating'] ?? 0);

    $update_sql = "UPDATE products SET rating = ?, review_count = ? WHERE product_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("dii", $avg, $cnt, $product_id);
    $update_stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => '리뷰가 삭제되었습니다.',
        'new_count' => $cnt,
        'new_rating' => $avg
    ]);
} else {
    echo json_encode(['success' => false, 'message' => '삭제 실패: ' . $conn->error]);
}
?>