<?php
require_once 'includes/db.php';

try {
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    $conn->query("TRUNCATE TABLE order_items");
    $conn->query("TRUNCATE TABLE orders");
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");

    echo "<h1>모든 주문 내역이 초기화되었습니다.</h1>";
    echo "<p><a href='admin/orders-manage.php'>관리자 페이지로 돌아가기</a></p>";
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage();
}
?>