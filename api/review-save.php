<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$user_id = $_SESSION['user_id'];
$product_id = isset($input['product_id']) ? (int) $input['product_id'] : 0;
$rating = isset($input['rating']) ? (int) $input['rating'] : 5;
$title = isset($input['title']) ? trim($input['title']) : '';
$content = isset($input['content']) ? trim($input['content']) : '';
// order_id는 추후 주문 상태 업데이트 등에 사용할 수 있으나 현재 reviews 테이블에 저장하는지는 불확실하여 일단 받기만 함
$order_id = isset($input['order_id']) ? (int) $input['order_id'] : 0;

if ($product_id <= 0 || empty($title) || empty($content)) {
    echo json_encode(['success' => false, 'message' => '입력 정보가 부족합니다.']);
    exit;
}

// 리뷰 저장 (is_approved 기본값 1로 저장)
$sql = "INSERT INTO reviews (user_id, product_id, rating, title, content, is_approved, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())";
$stmt = $conn->prepare($sql);

// 테이블이 없어서 에러(1146)가 발생했거나 prepare가 실패한 경우 테이블 생성 시도
if (!$stmt) {
    if ($conn->errno == 1146 || strpos($conn->error, "Unknown column 'is_approved'") !== false) {
        $conn->query("CREATE TABLE IF NOT EXISTS reviews (
            review_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            rating INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            is_approved TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id),
            INDEX (product_id)
        )");

        // 만약 테이블은 있는데 컬럼만 없는 경우
        if (strpos($conn->error, "Unknown column 'is_approved'") !== false) {
            $conn->query("ALTER TABLE reviews ADD COLUMN is_approved TINYINT(1) DEFAULT 1 AFTER content");
        }

        $stmt = $conn->prepare($sql);
    }

    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'DB 준비 오류: ' . $conn->error]);
        exit;
    }
}

$stmt->bind_param("iiiss", $user_id, $product_id, $rating, $title, $content);

if ($stmt->execute()) {
    // 상품 평점 및 리뷰 수 업데이트
    $conn->query("UPDATE products SET 
        rating = (SELECT AVG(rating) FROM reviews WHERE product_id = $product_id AND is_approved = 1),
        review_count = (SELECT COUNT(*) FROM reviews WHERE product_id = $product_id AND is_approved = 1)
        WHERE product_id = $product_id");

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => '저장 실패: ' . $stmt->error]);
}
?>