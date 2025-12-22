<?php
require_once 'includes/db.php';

echo "<h1>데이터 정리 시작 (샘플 제거)</h1>";

// 1. 명확한 샘플 ID들 (1~6번)
$sample_ids = [1, 2, 3, 4, 5, 6];

// 2. 혹시 이름으로도 찾아서 제거 (공백이나 약간의 차이 대응)
$sample_names = [
    '모던 패브릭 3인 소파',
    '모던 패브릭 3인용 소파',
    '북유럽 원목 커피 테이블',
    '원목 거실 테이블',
    '북유럽 스타일 러그',
    '프리미엄 호텔 침구 세트',
    '미니멀 퀸사이즈 침대 프레임',
    '원목 협탁',
    '화이트 원형 식탁',
    '4인용 원목 식탁 세트',
    '라탄 의자',
    '감성 무드등',
    '펜던트 조명 - 블랙',
    '플로어 스탠드',
    '모던 화병',
    '인테리어 포스터',
    '북유럽 스타일 쿠션 세트'
];

$deleted_count = 0;

// ID로 삭제
foreach ($sample_ids as $id) {
    // 관련 데이터 선삭제
    $conn->query("DELETE FROM reviews WHERE product_id = $id");
    $conn->query("DELETE FROM cart WHERE product_id = $id");
    $conn->query("DELETE FROM wishlist WHERE product_id = $id");

    if ($conn->query("DELETE FROM products WHERE product_id = $id")) {
        if ($conn->affected_rows > 0) {
            echo "🗑️ ID $id 삭제 완료<br>";
            $deleted_count++;
        }
    }
}

// 이름으로 삭제 (ID로 안 지워진 것들)
foreach ($sample_names as $name) {
    $safe_name = $conn->real_escape_string($name);
    $res = $conn->query("SELECT product_id FROM products WHERE name = '$safe_name'");
    while ($row = $res->fetch_assoc()) {
        $pid = $row['product_id'];
        $conn->query("DELETE FROM reviews WHERE product_id = $pid");
        $conn->query("DELETE FROM cart WHERE product_id = $pid");
        $conn->query("DELETE FROM wishlist WHERE product_id = $pid");
        $conn->query("DELETE FROM products WHERE product_id = $pid");
        echo "🗑️ 이름 '$name' (ID $pid) 삭제 완료<br>";
        $deleted_count++;
    }
}

echo "<h3>총 {$deleted_count}개의 데이터가 정리되었습니다.</h3>";
echo "<p>이제 남은 상품들에 대해 리얼한 리뷰를 생성하려면 <a href='seed_fake_reviews.php'>여기</a>를 눌러주세요.</p>";
echo "<a href='index.php'>메인으로 돌아가기</a>";
