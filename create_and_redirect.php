<?php
// external-product-detail.php
// 외부 상품(네이버 등)을 클릭했을 때 잠시 거쳐가는 가상의 상세 페이지
// 혹은 실제 DB에 등록 후 상세페이지로 이동

require_once 'includes/db.php';

$name = $_POST['name'] ?? '';
$price = $_POST['price'] ?? 0;
$image = $_POST['image'] ?? '';
$link = $_POST['link'] ?? '';
$brand = $_POST['brand'] ?? '';

if (empty($name)) {
    echo "<script>alert('상품 정보가 부족합니다.'); history.back();</script>";
    exit;
}

// 1. 이미 존재하는 상품인지 확인 (이름으로 체크)
$stmt = $conn->prepare("SELECT product_id FROM products WHERE name = ? LIMIT 1");
$stmt->bind_param("s", $name);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if ($product) {
    $product_id = $product['product_id'];

    // 기존 상품이라도 리뷰가 없으면 생성 (이전 테스트로 인해 생성만 되고 리뷰가 없는 경우 방지)
    $check_review = $conn->query("SELECT count(*) as cnt FROM reviews WHERE product_id = $product_id");
    $current_review_cnt = $check_review->fetch_assoc()['cnt'];

    if ($current_review_cnt == 0) {
        $rating = $_POST['rating'] ?? 4.5; // POST값이 없으면 기본값
        $review_count = $_POST['review_count'] ?? 10;

        // --- 리뷰 생성 로직 시작 (중복 코드) ---
        // 1. 리뷰 작성 당사자(유저) 확보
        $user_res = $conn->query("SELECT user_id FROM users ORDER BY RAND() LIMIT 1");
        $reviewer_id = 0;
        if ($user_res->num_rows > 0) {
            $reviewer_id = $user_res->fetch_assoc()['user_id'];
        } else {
            $conn->query("INSERT INTO users (email, password, name, phone) VALUES ('guest_review@demo.com', 'pass', '구매자', '010-0000-0000')");
            $reviewer_id = $conn->insert_id;
        }

        // 2. 리뷰 샘플 데이터
        $titles = ["배송이 정말 빠르네요!", "화면이랑 똑같아요", "가성비 최고입니다", "집 분위기가 달라졌어요", "적극 추천합니다", "선물용으로 샀는데 좋아하네요", "생각보다 퀄리티가 좋네요"];
        $contents = [
            "주문하고 금방 도착해서 놀랐어요. 포장도 꼼꼼하고 상품 상태도 완벽합니다.",
            "사진에서 보던 색감 그대로네요. 저희 집 인테리어랑 너무 잘 어울립니다.",
            "이 가격에 이 정도 퀄리티라니 믿기지 않아요. 다른 색상도 구매하고 싶네요.",
            "거실에 두니 분위기가 확 살아나네요. 가족들도 다들 예쁘다고 해요.",
            "고민하다 샀는데 진작 살 걸 그랬어요. 마감도 깔끔하고 튼튼해 보입니다.",
            "친구 집들이 선물로 줬는데 너무 맘에 들어하네요. 센스 있다는 소리 들었어요.",
            "실물이 훨씬 예쁩니다. 크기도 적당하고 사용하기 편해요."
        ];

        // 3. 리뷰 3~5개 랜덤 등록
        $num_reviews_to_add = min($review_count, rand(3, 5));

        $review_stmt = $conn->prepare("INSERT INTO reviews (product_id, user_id, rating, title, content, is_approved, created_at) VALUES (?, ?, ?, ?, ?, 1, DATE_SUB(NOW(), INTERVAL ? DAY))");

        for ($i = 0; $i < $num_reviews_to_add; $i++) {
            $r_rating = min(5, max(3, round($rating + (rand(-10, 10) / 10))));
            $r_title = $titles[array_rand($titles)];
            $r_content = $contents[array_rand($contents)];
            $r_days_ago = rand(1, 30);

            $review_stmt->bind_param("iiissi", $product_id, $reviewer_id, $r_rating, $r_title, $r_content, $r_days_ago);
            $review_stmt->execute();
        }
        // --- 리뷰 생성 로직 끝 ---

        // 상품 정보 업데이트 (평점, 리뷰수)
        $update_p = $conn->prepare("UPDATE products SET rating = ?, review_count = ? WHERE product_id = ?");
        $update_p->bind_param("dii", $rating, $num_reviews_to_add, $product_id);
        $update_p->execute();
    }

    echo "<script>location.href='product-detail.php?id=$product_id';</script>";
    exit;
} else {
    // 2. 없으면 새로 생성 후 이동
    $category_id = 5; // 소품/기타
    $stock = 999;
    $description = "네이버 쇼핑 연동 상품\n브랜드: " . $brand . "\n원문 링크: " . $link;

    // 브랜드명을 간단한 설명으로 추가
    if ($brand) {
        $description = "[Brand: $brand]\n" . $description;
    }

    $rating = $_POST['rating'] ?? 0;
    $review_count = $_POST['review_count'] ?? 0;

    $insert_stmt = $conn->prepare("INSERT INTO products (category_id, name, description, price, stock, main_image, is_active, rating, review_count) VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)");
    $insert_stmt->bind_param("issiisdi", $category_id, $name, $description, $price, $stock, $image, $rating, $review_count);

    if ($insert_stmt->execute()) {
        $product_id = $conn->insert_id;

        // 가짜 리뷰 생성 (사용자 요청: "다른 사람들이 작성한 것처럼")
        if ($review_count > 0) {
            // 1. 리뷰 작성 당사자(유저) 확보
            $user_res = $conn->query("SELECT user_id FROM users ORDER BY RAND() LIMIT 1");
            $reviewer_id = 0;
            if ($user_res->num_rows > 0) {
                $reviewer_id = $user_res->fetch_assoc()['user_id'];
            } else {
                // 유저가 없으면 임시 유저 생성
                $conn->query("INSERT INTO users (email, password, name, phone) VALUES ('guest_review@demo.com', 'pass', '구매자', '010-0000-0000')");
                $reviewer_id = $conn->insert_id;
            }

            // 2. 리뷰 샘플 데이터
            $titles = ["배송이 정말 빠르네요!", "화면이랑 똑같아요", "가성비 최고입니다", "집 분위기가 달라졌어요", "적극 추천합니다", "선물용으로 샀는데 좋아하네요", "생각보다 퀄리티가 좋네요"];
            $contents = [
                "주문하고 금방 도착해서 놀랐어요. 포장도 꼼꼼하고 상품 상태도 완벽합니다.",
                "사진에서 보던 색감 그대로네요. 저희 집 인테리어랑 너무 잘 어울립니다.",
                "이 가격에 이 정도 퀄리티라니 믿기지 않아요. 다른 색상도 구매하고 싶네요.",
                "거실에 두니 분위기가 확 살아나네요. 가족들도 다들 예쁘다고 해요.",
                "고민하다 샀는데 진작 살 걸 그랬어요. 마감도 깔끔하고 튼튼해 보입니다.",
                "친구 집들이 선물로 줬는데 너무 맘에 들어하네요. 센스 있다는 소리 들었어요.",
                "실물이 훨씬 예쁩니다. 크기도 적당하고 사용하기 편해요."
            ];

            // 3. 리뷰 3~5개 랜덤 등록
            $num_reviews_to_add = min($review_count, rand(3, 5));

            $review_stmt = $conn->prepare("INSERT INTO reviews (product_id, user_id, rating, title, content, is_approved, created_at) VALUES (?, ?, ?, ?, ?, 1, DATE_SUB(NOW(), INTERVAL ? DAY))");

            for ($i = 0; $i < $num_reviews_to_add; $i++) {
                // 평점은 전체 평점 주변으로 랜덤하게 (예: 4.5 -> 4 or 5)
                $r_rating = min(5, max(3, round($rating + (rand(-10, 10) / 10))));
                $r_title = $titles[array_rand($titles)];
                $r_content = $contents[array_rand($contents)];
                $r_days_ago = rand(1, 30); // 1~30일 전 작성

                // 리뷰 작성자가 1명이면 어색하므로, 가능하면 랜덤 유저를 다시 뽑거나 그대로 사용 (여기선 편의상 1명일 수도 있음. 개선하려면 루프 안에서 유저 다시 뽑기)
                // DB 부하를 줄이기 위해 루프 안에서 유저 뽑기는 생략하거나, 첫 유저만 사용. 
                // 더 리얼하게 하려면 루프마다 유저 ID 랜덤? (생략: 속도 우선)

                $review_stmt->bind_param("iiissi", $product_id, $reviewer_id, $r_rating, $r_title, $r_content, $r_days_ago);
                $review_stmt->execute();
            }
        }

        echo "<script>location.href='product-detail.php?id=$product_id';</script>";
        exit;
    } else {
        echo "<script>alert('상품 생성 실패'); history.back();</script>";
        exit;
    }
}
?>