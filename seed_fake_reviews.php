<?php
require_once 'includes/db.php';

echo "<h1>리뷰 데이터 초기화 및 신규 생성</h1>";

// 1. 기존 리뷰 전체 삭제 (선택적일 수 있으나 리얼한 느낌을 위해 초기화)
$conn->query("DELETE FROM reviews");
echo "🗑️ 기존 리뷰 데이터가 초기화되었습니다.<br>";

// 2. 더미 유저 생성 (없을 경우만)
$dummy_users = [
    ['email' => 'kim@example.com', 'name' => '김태희', 'password' => 'password123'],
    ['email' => 'lee@example.com', 'name' => '이보검', 'password' => 'password123'],
    ['email' => 'park@example.com', 'name' => '박소담', 'password' => 'password123'],
    ['email' => 'choi@example.com', 'name' => '최준', 'password' => 'password123'],
    ['email' => 'jung@example.com', 'name' => '정해인', 'password' => 'password123'],
    ['email' => 'kang@example.com', 'name' => '강하늘', 'password' => 'password123'],
    ['email' => 'yoon@example.com', 'name' => '한소희', 'password' => 'password123'],
    ['email' => 'song@example.com', 'name' => '송중기', 'password' => 'password123'],
    ['email' => 'baek@example.com', 'name' => '백종원', 'password' => 'password123'],
    ['email' => 'iu@example.com', 'name' => '이지은', 'password' => 'password123']
];

$user_ids = [];
foreach ($dummy_users as $u) {
    $check = $conn->query("SELECT user_id FROM users WHERE email = '{$u['email']}'");
    if ($check->num_rows == 0) {
        $hashed = password_hash($u['password'], PASSWORD_DEFAULT);
        $conn->query("INSERT INTO users (email, name, password) VALUES ('{$u['email']}', '{$u['name']}', '$hashed')");
        $user_ids[] = $conn->insert_id;
        echo "👤 유저 '{$u['name']}' 생성됨<br>";
    } else {
        $user_ids[] = $check->fetch_assoc()['user_id'];
    }
}

// 3. 더미 리뷰 템플릿
$review_templates = [
    ['rating' => 5, 'title' => '너무 만족스러워요!', 'content' => '사진보다 실물이 훨씬 예쁘네요. 집 분위기가 확 살아요. 배송도 빠르고 포장도 꼼꼼해서 좋았습니다.'],
    ['rating' => 5, 'title' => '가성비 최고입니다.', 'content' => '이 가격에 이 퀄리티라니 믿기지가 않네요. 마감 처리도 깔끔하고 디자인이 세련됐어요. 강력 추천합니다!'],
    ['rating' => 4, 'title' => '생각보다 괜찮네요.', 'content' => '색감이 화면이랑 아주 살짝 다르긴 한데 그래도 예뻐요. 설치하기 편하고 튼튼해 보여서 만족합니다.'],
    ['rating' => 5, 'title' => '재구매 의사 100%!', 'content' => '친구 추천으로 샀는데 너무 마음에 들어요. 다른 색상도 구매하고 싶네요. 배송 기사님도 친절하셨어요.'],
    ['rating' => 5, 'title' => '인테리어 최고 아이템', 'content' => '유튜브 보고 예뻐서 샀는데 정말 후회 없습니다. 거실 한켠에 두니까 카페 온 것 같아요. 감사합니다!'],
    ['rating' => 4, 'title' => '배송은 조금 느렸지만 만족', 'content' => '주문 폭주라 그런지 배송은 며칠 걸렸네요. 그래도 상품 상태가 너무 좋아서 기다린 보람이 있습니다.'],
    ['rating' => 5, 'title' => '모던하고 깔끔해요', 'content' => '어떤 가구랑도 잘 어울리는 디자인이에요. 소재도 탄탄해 보이고 오염에도 강할 것 같아서 좋네요.'],
    ['rating' => 5, 'title' => '부모님 선물로 드렸는데 좋아하세요', 'content' => '부모님 댁 거실에 놔드렸는데 너무 고급스럽다고 좋아하시네요. 효도한 기분입니다.'],
    ['rating' => 3, 'title' => '보통이에요', 'content' => '생각했던 사이즈보다 약간 작긴 한데 그래도 쓰기 나쁘지 않네요. 배송은 빨랐습니다.'],
    ['rating' => 5, 'title' => '완전 감성 돋아요', 'content' => '인스타 감성 뿜뿜입니다. 여기서만 파는 디자인인 것 같아서 더 유니크하고 좋네요. 대만족!']
];

// 4. 모든 상품에 대해 랜덤하게 리뷰 삽입
$products = $conn->query("SELECT product_id FROM products");
$review_count = 0;

while ($p = $products->fetch_assoc()) {
    $product_id = $p['product_id'];

    // 상품당 2~5개의 리뷰 랜덤 삽입
    $num_to_add = rand(2, 5);
    for ($i = 0; $i < $num_to_add; $i++) {
        $user_id = $user_ids[array_rand($user_ids)];
        $template = $review_templates[array_rand($review_templates)];

        $stmt = $conn->prepare("INSERT INTO reviews (user_id, product_id, rating, title, content, is_approved, created_at) VALUES (?, ?, ?, ?, ?, 1, DATE_SUB(NOW(), INTERVAL ? HOUR))");
        $hours_ago = rand(1, 168); // 최근 일주일 사이
        $stmt->bind_param("iiissi", $user_id, $product_id, $template['rating'], $template['title'], $template['content'], $hours_ago);

        if ($stmt->execute()) {
            $review_count++;
        }
    }

    // 상품의 rating 및 review_count 업데이트
    $conn->query("UPDATE products p 
                 SET rating = (SELECT AVG(rating) FROM reviews WHERE product_id = $product_id),
                     review_count = (SELECT COUNT(*) FROM reviews WHERE product_id = $product_id)
                 WHERE product_id = $product_id");
}

echo "<h3>🎉 성공적으로 {$review_count}개의 고퀄리티 리뷰가 생성되었습니다.</h3>";
echo "<p>이제 상품 목록이나 상세 페이지에서 리얼한 리뷰를 확인하실 수 있습니다.</p>";
echo "<a href='index.php'>메인 페이지로 이동</a>";
