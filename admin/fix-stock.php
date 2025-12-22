<?php
require_once '../includes/db.php';
$sql = "UPDATE products SET stock = 0 WHERE is_active = 0";
if ($conn->query($sql)) {
    echo "<h1>성공: 판매 중지된 모든 상품의 재고가 0으로 수정되었습니다.</h1>";
    echo "<p><a href='products-manage.php'>상품 관리로 돌아가기</a></p>";
} else {
    echo "<h1>오류 발생: " . $conn->error . "</h1>";
}
?>