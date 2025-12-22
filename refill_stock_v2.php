<?php
require_once 'includes/db.php';

// 캐시 방지 헤더
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// 1. 모든 상품의 재고를 100으로 업데이트
$sql_stock = "UPDATE products SET stock = 100";
if ($conn->query($sql_stock) === TRUE) {
    echo "✅ 모든 상품의 재고가 100개로 충전되었습니다!<br>";
} else {
    echo "❌ 재고 업데이트 오류: " . $conn->error . "<br>";
}

// 2. 가장 최근 주문의 상태를 'shipped'로 변경 (배송조회 테스트용)
$sql_order = "UPDATE orders SET status = 'shipped' ORDER BY order_id DESC LIMIT 1";
if ($conn->query($sql_order) === TRUE) {
    if ($conn->affected_rows > 0) {
        echo "✅ 최근 주문 1건의 상태가 '배송중(shipped)'으로 변경되었습니다! (마이페이지에서 배송조회 버튼 확인 가능)<br>";
    } else {
        echo "ℹ️ 변경할 주문 내역이 없습니다. (주문 내역이 없으면 insert해서라도 만듦)<br>";

        // 주문이 없으면 하나 만듦 (테스트용)
        // 사용자 ID 1번이라고 가정
        $dummy_order = "INSERT INTO orders (user_id, status, total_amount, shipping_address, created_at) VALUES (1, 'shipped', 50000, '서울시 강남구', NOW())";
        if ($conn->query($dummy_order)) {
            echo "✅ 테스트 주문 1건을 새로 생성했습니다!<br>";
        }
    }
} else {
    echo "❌ 주문 상태 업데이트 오류: " . $conn->error . "<br>";
}

echo "<br><a href='index.php'>홈으로 돌아가기</a> | <a href='mypage.php'>마이페이지로 이동</a>";

$conn->close();
?>