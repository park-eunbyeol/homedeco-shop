<?php
// ------------------------
// index.php 쇼핑 검색 통합 예제
// ------------------------
$page_title = '홈';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 네이버 쇼핑 API 설정
$client_id = "YOUR_CLIENT_ID";
$client_secret = "YOUR_CLIENT_SECRET";

// 검색어 GET 변수
$search_query = $_GET['query'] ?? '';
$shop_items = [];
$shop_error_msg = '';

// API 호출
if (!empty($search_query)) {
    $encoded_query = urlencode($search_query);
    $url = "https://openapi.naver.com/v1/search/shop.json?query={$encoded_query}&display=12";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-Naver-Client-Id: {$client_id}",
        "X-Naver-Client-Secret: {$client_secret}"
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $shop_error_msg = "API 연결 실패: " . curl_error($ch);
        $shop_items = [];
    } else {
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_status != 200) {
            $shop_error_msg = "API 요청 실패: HTTP 상태 코드 {$http_status}";
            $shop_items = [];
        } else {
            $result = json_decode($response, true);
            $shop_items = $result['items'] ?? [];
        }
    }
    curl_close($ch);
}

// 임의 별점/리뷰/할인율 생성
function shop_generateRating() { return rand(30,50)/10; }
function shop_generateReviews() { return rand(1,500); }
function shop_generateDiscount() { return rand(5,50); }

// 이미지 처리
function shop_imageUrl($url) {
    $url = strip_tags($url);
    if (empty($url)) return '/images/default_product.png'; // 기본 이미지
    if (strpos($url, 'http://') === 0) $url = 'https://' . substr($url, 7);
    return $url;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title><?php echo $page_title; ?></title>
<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f8f9fa; }
    h1 { text-align: center; margin-bottom: 20px; }
    form { text-align: center; margin-bottom: 30px; }
    input[type="text"] { padding: 10px; width: 250px; border-radius: 5px; border: 1px solid #ccc; }
    button { padding: 10px 20px; border-radius: 5px; border: none; background: #2c3e50; color: #fff; cursor: pointer; }
    button:hover { background: #34495e; }

    .shop-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-top:20px; }
    .shop-card { background:#fff; border-radius:12px; box-shadow:0 2px 6px rgba(0,0,0,0.1); overflow:hidden; position:relative; transition: transform 0.2s, box-shadow 0.2s; }
    .shop-card:hover { transform:translateY(-5px); box-shadow:0 6px 15px rgba(0,0,0,0.2); }
    .shop-card img { width:100%; height:180px; object-fit:cover; display:block; }
    .shop-card-body { padding:12px; }
    .shop-card-title { font-size:15px; margin:0 0 6px; height:40px; overflow:hidden; line-height:1.2em; }
    .shop-card-price { font-weight:bold; color:#e74c3c; font-size:14px; }
    .shop-new-badge { position:absolute; top:10px; left:10px; background:#e74c3c; color:#fff; font-size:11px; font-weight:bold; padding:3px 8px; border-radius:12px; }
    .shop-card-info { font-size:12px; color:#555; margin-top:5px; }
    .shop-star { color:#f39c12; }
    @media(max-width:600px){ .shop-cards{grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:15px;} .shop-card img{height:140px;} }
</style>
</head>
<body>

<h1>홈페이지 콘텐츠</h1>
<p>여기에 기존 index.php 내용이 들어갑니다.</p>

<form method="get">
    <input type="text" name="query" placeholder="검색어 입력" value="<?php echo htmlspecialchars($search_query); ?>" required>
    <button type="submit">검색</button>
</form>

<?php if (!empty($shop_items)): ?>
<div class="shop-cards">
    <?php foreach($shop_items as $item): 
        $rating = shop_generateRating();
        $reviews = shop_generateReviews();
        $discount = shop_generateDiscount();
        $image_url = shop_imageUrl($item['image']);
    ?>
    <a href="<?php echo $item['link']; ?>" target="_blank">
        <div class="shop-card">
            <div class="shop-new-badge">NEW</div>
            <img src="<?php echo $image_url; ?>" alt="<?php echo strip_tags($item['title']); ?>">
            <div class="shop-card-body">
                <div class="shop-card-title"><?php echo strip_tags($item['title']); ?></div>
                <div class="shop-card-price"><?php echo number_format($item['lprice']); ?>원</div>
                <div class="shop-card-info">
                    <span class="shop-star"><?php echo str_repeat('★', floor($rating)); ?></span>
                    <span class="shop-star"><?php echo str_repeat('☆', 5-floor($rating)); ?></span>
                    (<?php echo $reviews; ?> 리뷰) | 할인 <?php echo $discount; ?>%
                </div>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php elseif(!empty($search_query)): ?>
<p style="text-align:center; color:red;"><?php echo $shop_error_msg ?? "검색 결과가 없습니다."; ?></p>
<?php endif; ?>

</body>
</html>
