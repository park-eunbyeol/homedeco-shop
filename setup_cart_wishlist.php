<?php
require_once 'includes/db.php';

echo "<h2>장바구니 & 위시리스트 테이블 생성</h2>";

// cart_items 테이블 생성
$cart_sql = "CREATE TABLE IF NOT EXISTS `cart_items` (
  `cart_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cart_id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($cart_sql)) {
    echo "<p style='color: green;'>✅ cart_items 테이블 생성 성공!</p>";
} else {
    echo "<p style='color: red;'>❌ cart_items 테이블 생성 실패: " . $conn->error . "</p>";
}

// wishlist 테이블 생성
$wishlist_sql = "CREATE TABLE IF NOT EXISTS `wishlist` (
  `wishlist_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`wishlist_id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  UNIQUE KEY `user_product` (`user_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($wishlist_sql)) {
    echo "<p style='color: green;'>✅ wishlist 테이블 생성 성공!</p>";
} else {
    echo "<p style='color: red;'>❌ wishlist 테이블 생성 실패: " . $conn->error . "</p>";
}

echo "<hr>";
echo "<p><strong>완료!</strong> 이제 <a href='products.php'>상품 페이지</a>로 이동하여 찜하기/장바구니를 테스트하세요.</p>";
echo "<p><small>이 파일은 삭제하셔도 됩니다.</small></p>";
?>