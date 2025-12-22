<?php
// seed_products.php - 샘플 상품 데이터 삽입
require_once 'includes/db.php';

// 카테고리별 샘플 데이터
$samples = [
    // 1: 거실
    [
        'category_id' => 1,
        'name' => '모던 패브릭 3인용 소파',
        'price' => 359000,
        'description' => '편안함과 스타일을 동시에 잡은 모던 소파입니다.',
        'main_image' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=500&q=80',
        'brand' => 'COZY HOME'
    ],
    [
        'category_id' => 1,
        'name' => '원목 거실 테이블',
        'price' => 129000,
        'description' => '내추럴한 감성의 원목 테이블',
        'main_image' => 'https://images.unsplash.com/photo-1533090481720-856c6e3c1fdc?w=500&q=80',
        'brand' => 'Woody'
    ],
    [
        'category_id' => 1,
        'name' => '북유럽 스타일 러그',
        'price' => 45000,
        'description' => '거실 분위기를 바꿔주는 포근한 러그',
        'main_image' => 'https://images.unsplash.com/photo-1575414003591-ece8d0416c7a?w=500&q=80',
        'brand' => 'Nordic'
    ],

    // 2: 침실
    [
        'category_id' => 2,
        'name' => '프리미엄 호텔 침구 세트',
        'price' => 89000,
        'description' => '매일 호텔에서 자는 듯한 편안함',
        'main_image' => 'https://images.unsplash.com/photo-1522771753035-4a50c95b9386?w=500&q=80',
        'brand' => 'SleepWell'
    ],
    [
        'category_id' => 2,
        'name' => '원목 협탁',
        'price' => 55000,
        'description' => '침대 옆 필수 아이템',
        'main_image' => 'https://images.unsplash.com/photo-1532323544230-7191fd51bc1b?w=500&q=80',
        'brand' => 'Woody'
    ],

    // 3: 주방
    [
        'category_id' => 3,
        'name' => '화이트 원형 식탁',
        'price' => 159000,
        'description' => '카페 같은 주방을 위한 선택',
        'main_image' => 'https://images.unsplash.com/photo-1533090368676-1fd25485db88?w=500&q=80',
        'brand' => 'KitchenArt'
    ],
    [
        'category_id' => 3,
        'name' => '라탄 의자',
        'price' => 68000,
        'description' => '시원하고 감성적인 라탄 소재',
        'main_image' => 'https://images.unsplash.com/photo-1519947486511-46149fa0a254?w=500&q=80',
        'brand' => 'Rattan'
    ],

    // 4: 조명
    [
        'category_id' => 4,
        'name' => '감성 무드등',
        'price' => 29900,
        'description' => '밤을 아름답게 밝혀주는 무드등',
        'main_image' => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?w=500&q=80',
        'brand' => 'LightUp'
    ],
    [
        'category_id' => 4,
        'name' => '플로어 스탠드',
        'price' => 79000,
        'description' => '거실이나 침실 포인트 조명',
        'main_image' => 'https://images.unsplash.com/photo-1513506003013-d5347e0f95d1?w=500&q=80',
        'brand' => 'LightUp'
    ],

    // 5: 소품
    [
        'category_id' => 5,
        'name' => '모던 화병',
        'price' => 15000,
        'description' => '꽃 한 송이로 분위기 전환',
        'main_image' => 'https://images.unsplash.com/photo-1581783342308-f792ca11df53?w=500&q=80',
        'brand' => 'Deco'
    ],
    [
        'category_id' => 5,
        'name' => '인테리어 포스터',
        'price' => 9900,
        'description' => '벽면을 채우는 감성 아트',
        'main_image' => 'https://images.unsplash.com/photo-1582201942988-13e60e4556ee?w=500&q=80',
        'brand' => 'ArtWall'
    ]
];

// 데이터 삽입
echo "<h2>샘플 데이터 삽입 시작</h2>";
$cnt = 0;
foreach ($samples as $p) {
    // 중복 체크 (이름 기준)
    $check = $conn->query("SELECT product_id FROM products WHERE name = '" . $conn->real_escape_string($p['name']) . "'");
    if ($check->num_rows == 0) {
        $sql = "INSERT INTO products (category_id, name, price, description, main_image, is_active, created_at) VALUES (
            {$p['category_id']},
            '" . $conn->real_escape_string($p['name']) . "',
            {$p['price']},
            '" . $conn->real_escape_string($p['description']) . "',
            '" . $conn->real_escape_string($p['main_image']) . "',
            1,
            NOW()
        )";

        if ($conn->query($sql)) {
            $cnt++;
            echo "추가됨: {$p['name']}<br>";
        } else {
            echo "오류: " . $conn->error . "<br>";
        }
    } else {
        echo "이미 존재함: {$p['name']}<br>";
    }
}

echo "<h3>총 {$cnt}개의 상품이 추가되었습니다.</h3>";
echo "<a href='index.php'>메인으로 돌아가기</a>";
?>