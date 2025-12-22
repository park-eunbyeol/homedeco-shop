<?php
// 새 API 파일: api/category_products.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

try {
    // DB 연결
    $conn = new mysqli('localhost', 'root', '', 'homedeco_shop');

    if ($conn->connect_error) {
        throw new Exception("DB Connection Failed");
    }
    $conn->set_charset("utf8mb4");

    // 파라미터
    $category_id = isset($_GET['category']) && $_GET['category'] !== 'new' ? (int) $_GET['category'] : 0;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 8;

    // 데이터 조회
    if ($category_id > 0) {
        $sql = "SELECT product_id, name, price, main_image, stock 
                FROM products 
                WHERE category_id = " . $category_id . " AND is_active = 1 
                ORDER BY created_at DESC 
                LIMIT $limit";
    } else {
        // 'new' 또는 카테고리 미지정 시 전체 상품 중 최신순
        $sql = "SELECT product_id, name, price, main_image, stock 
                FROM products 
                WHERE is_active = 1 
                ORDER BY created_at DESC 
                LIMIT $limit";
    }

    // Prepared Statement 대신 단순 쿼리로 테스트 (호환성 이슈 배제)
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception($conn->error);
    }

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $row['link'] = '/homedeco-shop/product-detail.php?id=' . $row['product_id'];
        $products[] = $row;
    }

    echo json_encode(['success' => true, 'products' => $products]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>