<?php
// api/import-naver-products.php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once '../includes/db.php';
    require_once '../includes/naver_api.php';

    // 관리자 권한 확인 (임시 비활성화)
    // if (!is_logged_in() || $_SESSION['role'] !== 'admin') {
    //     echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    //     exit;
    // }

    $category_id = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
    $keyword = isset($_POST['keyword']) ? clean_input($_POST['keyword']) : '';
    $limit = isset($_POST['limit']) ? min(100, (int) $_POST['limit']) : 20;

    if (empty($keyword)) {
        echo json_encode(['success' => false, 'message' => '검색 키워드를 입력하세요.']);
        exit;
    }

    // 네이버 API로 상품 검색
    $products = search_naver_products($keyword, $limit);

    if (empty($products)) {
        echo json_encode(['success' => false, 'message' => '검색 결과가 없습니다. 키워드를 변경해보세요.']);
        exit;
    }

    $imported_count = 0;
    $skipped_count = 0;
    $errors = [];

    foreach ($products as $product) {
        $name = $product['name'];
        $price = $product['price'];
        $image_url = $product['main_image'];
        $brand = $product['brand'] ?? '';

        // 중복 체크 (같은 이름의 상품이 이미 있는지)
        $check_sql = "SELECT product_id FROM products WHERE name = ? LIMIT 1";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $name);
        $check_stmt->execute();
        $exists = $check_stmt->get_result()->num_rows > 0;

        if ($exists) {
            $skipped_count++;
            continue;
        }

        // 상품 설명 생성 (브랜드 정보 포함)
        $description = !empty($brand) ? "{$brand} 제품입니다." : "고품질 인테리어 상품입니다.";

        // DB에 저장
        $insert_sql = "INSERT INTO products (category_id, name, description, price, main_image, stock, is_active) 
                       VALUES (?, ?, ?, ?, ?, 999, 1)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("issds", $category_id, $name, $description, $price, $image_url);

        if ($insert_stmt->execute()) {
            $imported_count++;
        } else {
            $errors[] = "상품 '{$name}' 저장 실패: " . $insert_stmt->error;
        }
    }

    $message = "{$imported_count}개 상품을 가져왔습니다.";
    if ($skipped_count > 0) {
        $message .= " (중복 {$skipped_count}개 제외)";
    }
    if (!empty($errors)) {
        $message .= " 오류: " . implode(", ", $errors);
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'imported' => $imported_count,
        'skipped' => $skipped_count,
        'errors' => $errors
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '오류 발생: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>