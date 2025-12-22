<?php
// 에러 출력 설정 (화면 출력 끔, JSON 보호)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// JSON 헤더 설정
header('Content-Type: application/json; charset=utf-8');

try {
    // 1. DB 설정 직접 정의 (파일 인클루드 문제 배제)
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'homedeco_shop';

    // 2. DB 연결 시도
    mysqli_report(MYSQLI_REPORT_OFF); // 자동 예외 발생 끄기 (수동 처리)
    $conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);

    if ($conn->connect_errno) {
        throw new Exception("데이터베이스 연결 실패: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");

    // 3. 파라미터 검증
    $category_id = isset($_GET['category']) ? (int) $_GET['category'] : 0;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 8;
    $limit = max(1, min(100, $limit));

    if ($category_id <= 0) {
        throw new Exception('유효하지 않은 카테고리 ID입니다.');
    }

    // 4. 데이터 조회
    $sql = "SELECT product_id, name, price, main_image, brand 
            FROM products 
            WHERE category_id = ? AND is_active = 1 
            ORDER BY created_at DESC 
            LIMIT ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('SQL 준비 실패: ' . $conn->error);
    }

    $stmt->bind_param("ii", $category_id, $limit);
    if (!$stmt->execute()) {
        throw new Exception('쿼리 실행 실패: ' . $stmt->error);
    }

    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        // 내부 상세 페이지 링크 생성
        $row['link'] = 'product-detail.php?id=' . $row['product_id'];
        // 이미지 경로 보정 (혹시 모르니)
        if (empty($row['main_image']))
            $row['main_image'] = 'images/placeholder.jpg';
        $products[] = $row;
    }

    // 5. 성공 응답
    echo json_encode([
        'success' => true,
        'products' => $products,
        'count' => count($products)
    ]);

} catch (Exception $e) {
    // 6. 에러 응답
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // 7. 리소스 정리
    if (isset($stmt) && $stmt instanceof mysqli_stmt)
        $stmt->close();
    if (isset($conn) && $conn instanceof mysqli)
        $conn->close();
}
?>