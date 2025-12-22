<?php
$page_title = 'AI 스타일 추천';
require_once 'includes/db.php';
require_once 'includes/naver_api.php';

if (!is_logged_in()) {
    redirect('login.php?redirect=ai-recommend.php');
}

$user_id = $_SESSION['user_id'];

// 사용자 취향 정보 조회
$pref_sql = "SELECT * FROM user_preferences WHERE user_id = $user_id";
$pref_result = $conn->query($pref_sql);
$user_pref = $pref_result->num_rows > 0 ? $pref_result->fetch_assoc() : null;

// AI 추천 상품 (취향 기반 - 네이버 API 사용)
$recommended_products = [];
if ($user_pref) {
    $style = $user_pref['style_preference'];
    $color = $user_pref['color_preference'];
    $room = $user_pref['room_preference'];

    // 스타일별 키워드 매핑
    $style_keywords = [
        'modern' => '모던',
        'scandinavian' => '북유럽',
        'minimal' => '미니멀',
        'vintage' => '빈티지',
        'industrial' => '인더스트리얼'
    ];

    // 공간별 키워드 매핑
    $room_keywords = [
        'living' => '거실',
        'bedroom' => '침실',
        'dining' => '주방 식탁',
        'office' => '서재 책상'
    ];

    // 색상별 키워드 매핑
    $color_keywords = [
        'white' => '화이트',
        'black' => '블랙',
        'wood' => '우드',
        'gray' => '그레이',
        'multi' => ''
    ];

    // 검색 키워드 생성
    $search_query = ($style_keywords[$style] ?? '') . ' ' .
        ($color_keywords[$color] ?? '') . ' ' .
        ($room_keywords[$room] ?? '') . ' 인테리어';

    // 가격대 설정
    $min_price = 0;
    $max_price = PHP_INT_MAX;

    if (isset($user_pref['price_range'])) {
        switch ($user_pref['price_range']) {
            case 'low':
                $max_price = 100000;
                break;
            case 'mid':
                $min_price = 100000;
                $max_price = 500000;
                break;
            case 'high':
                $min_price = 500000;
                break;
        }
    }

    // 네이버 쇼핑 API로 상품 조회 (가격 필터링을 위해 더 많이 조회)
    $all_products = search_naver_products(trim($search_query), 50);

    // 가격대 필터링
    $recommended_products = array_filter($all_products, function ($product) use ($min_price, $max_price) {
        $price = (int) $product['price'];
        return $price >= $min_price && $price <= $max_price;
    });

    // 색상 필터링 (멀티컬러 제외)
    if ($color !== 'multi') {
        $color_filter_map = [
            'white' => ['화이트', '흰색', '아이보리', 'white'],
            'black' => ['블랙', '검정', 'black'],
            'wood' => ['우드', '원목', '브라운', 'brown', '오크', '월넛'],
            'gray' => ['그레이', '회색', 'gray', '차콜']
        ];

        if (isset($color_filter_map[$color])) {
            $keywords = $color_filter_map[$color];
            $recommended_products = array_filter($recommended_products, function ($product) use ($keywords) {
                foreach ($keywords as $k) {
                    if (stripos($product['name'], $k) !== false) {
                        return true;
                    }
                }
                return false;
            });
        }
    }

    // 최대 12개만 유지
    $recommended_products = array_slice($recommended_products, 0, 12);
}

require_once 'includes/header.php';
?>

<div class="container">
    <div class="ai-hero">
        <div class="ai-hero-content">
            <h1><i class="fas fa-magic"></i> AI 스타일 추천</h1>
            <p>당신의 취향을 분석하여 최적의 인테리어 상품을 추천합니다</p>
        </div>
    </div>

    <?php if (!$user_pref): ?>
        <!-- 취향 설정 -->
        <div class="preference-setup">
            <div class="setup-card">
                <h2>취향을 알려주세요</h2>
                <p>몇 가지 질문으로 당신만의 스타일을 찾아드립니다</p>

                <form method="POST" action="/homedeco-shop/api/save-preferences.php" class="preference-form">
                    <div class="question-group">
                        <h3><i class="fas fa-palette"></i> 선호하는 스타일은?</h3>
                        <div class="options-grid">
                            <label class="option-card">
                                <input type="radio" name="style_preference" value="modern" required>
                                <div class="option-content">
                                    <i class="fas fa-gem"></i>
                                    <span>모던</span>
                                    <p>깔끔하고 세련된</p>
                                </div>
                            </label>

                            <label class="option-card">
                                <input type="radio" name="style_preference" value="scandinavian">
                                <div class="option-content">
                                    <i class="fas fa-tree"></i>
                                    <span>북유럽</span>
                                    <p>자연스럽고 따뜻한</p>
                                </div>
                            </label>

                            <label class="option-card">
                                <input type="radio" name="style_preference" value="minimal">
                                <div class="option-content">
                                    <i class="fas fa-circle"></i>
                                    <span>미니멀</span>
                                    <p>심플하고 절제된</p>
                                </div>
                            </label>

                            <label class="option-card">
                                <input type="radio" name="style_preference" value="vintage">
                                <div class="option-content">
                                    <i class="fas fa-clock"></i>
                                    <span>빈티지</span>
                                    <p>클래식하고 감성적인</p>
                                </div>
                            </label>

                            <label class="option-card">
                                <input type="radio" name="style_preference" value="industrial">
                                <div class="option-content">
                                    <i class="fas fa-industry"></i>
                                    <span>인더스트리얼</span>
                                    <p>모던하고 개성있는</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="question-group">
                        <h3><i class="fas fa-fill-drip"></i> 선호하는 색상은?</h3>
                        <div class="options-grid">
                            <label class="option-card">
                                <input type="radio" name="color_preference" value="white" required>
                                <div class="option-content">
                                    <div class="color-circle" style="background: #ffffff; border: 1px solid #ddd;"></div>
                                    <span>화이트</span>
                                </div>
                            </label>

                            <label class="option-card">
                                <input type="radio" name="color_preference" value="black">
                                <div class="option-content">
                                    <div class="color-circle" style="background: #000000;"></div>
                                    <span>블랙</span>
                                </div>
                            </label>

                            <label class="option-card">
                                <input type="radio" name="color_preference" value="wood">
                                <div class="option-content">
                                    <div class="color-circle" style="background: #8B6F47;"></div>
                                    <span>우드</span>
                                </div>
                            </label>

                            <label class="option-card">
                                <input type="radio" name="color_preference" value="gray">
                                <div class="option-content">
                                    <div class="color-circle" style="background: #808080;"></div>
                                    <span>그레이</span>
                                </div>
                            </label>

                            <label class="option-card">
                                <input type="radio" name="color_preference" value="multi">
                                <div class="option-content">
                                    <div class="color-circle"
                                        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
                                    <span>멀티컬러</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="question-group">
                        <h3><i class="fas fa-door-open"></i> 가장 관심있는 공간은?</h3>
                        <div class="options-grid">
                            <label class="option-card">
                                <input type="radio" name="room_preference" value="living" required>
                                <div class="option-content">
                                    <i class="fas fa-couch"></i>
                                    <span>거실</span>
                                </div>
                            </label>

                            <label class="option-card">
                                <input type="radio" name="room_preference" value="bedroom">
                                <div class="option-content">
                                    <i class="fas fa-bed"></i>
                                    <span>침실</span>
                                </div>
                            </label>

                            <label class="option-card">
                                <input type="radio" name="room_preference" value="dining">
                                <div class="option-content">
                                    <i class="fas fa-utensils"></i>
                                    <span>주방/식당</span>
                                </div>
                            </label>

                            <label class="option-card">
                                <input type="radio" name="room_preference" value="office">
                                <div class="option-content">
                                    <i class="fas fa-laptop"></i>
                                    <span>서재/홈오피스</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="question-group">
                        <h3><i class="fas fa-won-sign"></i> 선호하는 가격대는?</h3>
                        <div class="options-grid">
                            <label class="option-card">
                                <input type="radio" name="price_range" value="low" required>
                                <div class="option-content">
                                    <span>10만원 이하</span>
                                </div>
                            </label>

                            <label class="option-card">
                                <input type="radio" name="price_range" value="mid">
                                <div class="option-content">
                                    <span>10~50만원</span>
                                </div>
                            </label>

                            <label class="option-card">
                                <input type="radio" name="price_range" value="high">
                                <div class="option-content">
                                    <span>50만원 이상</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-large">
                            <i class="fas fa-magic"></i> AI 추천 받기
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- 추천 결과 -->
        <div class="preference-summary">
            <h2>당신의 스타일</h2>
            <div class="style-tags">
                <span class="style-tag"><i class="fas fa-palette"></i> <?php echo $user_pref['style_preference']; ?></span>
                <span class="style-tag"><i class="fas fa-fill-drip"></i>
                    <?php echo $user_pref['color_preference']; ?></span>
                <span class="style-tag"><i class="fas fa-door-open"></i> <?php echo $user_pref['room_preference']; ?></span>
                <span class="style-tag"><i class="fas fa-won-sign"></i> <?php echo $user_pref['price_range']; ?></span>
            </div>
            <button class="btn btn-outline" onclick="location.href='/homedeco-shop/api/reset-preferences.php'">
                <i class="fas fa-redo"></i> 취향 다시 설정
            </button>
        </div>

        <?php if (!empty($recommended_products)): ?>
            <section class="recommended-section">
                <h2><i class="fas fa-stars"></i> 당신을 위한 추천 상품</h2>
                <p class="section-subtitle">AI가 분석한 당신의 취향에 딱 맞는 상품들입니다</p>

                <div class="product-grid">
                    <?php foreach ($recommended_products as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <a href="<?php echo htmlspecialchars($product['link']); ?>" target="_blank" rel="noopener">
                                    <img src="<?php echo htmlspecialchars($product['main_image']); ?>"
                                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                                        onerror="this.src='images/placeholder.jpg'">
                                </a>
                                <div class="match-badge">
                                    <i class="fas fa-check"></i> AI 추천
                                </div>
                            </div>
                            <div class="product-info">
                                <h3 class="product-name">
                                    <a href="<?php echo htmlspecialchars($product['link']); ?>" target="_blank" rel="noopener">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </h3>
                                <?php if (!empty($product['brand'])): ?>
                                    <p class="product-brand" style="font-size: 12px; color: #999; margin: 5px 0;">
                                        <?php echo htmlspecialchars($product['brand']); ?>
                                    </p>
                                <?php endif; ?>
                                <div class="product-price">
                                    <?php echo number_format($product['price']); ?>원
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php else: ?>
            <div class="no-recommendations">
                <i class="fas fa-search"></i>
                <h3>추천 상품을 찾을 수 없습니다</h3>
                <p>곧 새로운 상품이 업데이트됩니다</p>
                <a href="/products.php" class="btn btn-primary">전체 상품 보기</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
    .ai-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 20px;
        padding: 60px 40px;
        text-align: center;
        color: white;
        margin-bottom: 60px;
    }

    .ai-hero h1 {
        font-size: 42px;
        margin-bottom: 15px;
    }

    .ai-hero p {
        font-size: 18px;
        opacity: 0.9;
    }

    .preference-setup {
        max-width: 900px;
        margin: 0 auto 60px;
    }

    .setup-card {
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 50px;
    }

    .setup-card h2 {
        font-size: 32px;
        text-align: center;
        margin-bottom: 10px;
        color: var(--primary-color);
    }

    .setup-card>p {
        text-align: center;
        color: #666;
        margin-bottom: 50px;
    }

    .question-group {
        margin-bottom: 50px;
    }

    .question-group h3 {
        font-size: 20px;
        margin-bottom: 25px;
        color: var(--primary-color);
    }

    .question-group h3 i {
        margin-right: 10px;
        color: var(--secondary-color);
    }

    .options-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }

    .option-card {
        cursor: pointer;
    }

    .option-card input {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }

    .option-content {
        background: white;
        border: 2px solid var(--border-color);
        border-radius: 12px;
        padding: 30px 20px;
        text-align: center;
        transition: all 0.3s;
    }

    .option-card:hover .option-content {
        border-color: var(--secondary-color);
        transform: translateY(-3px);
    }

    .option-card input:checked+.option-content {
        border-color: var(--secondary-color);
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    }

    .option-content i {
        font-size: 32px;
        color: var(--secondary-color);
        margin-bottom: 15px;
    }

    .option-content span {
        display: block;
        font-weight: 600;
        font-size: 16px;
        margin-bottom: 5px;
    }

    .option-content p {
        font-size: 13px;
        color: #666;
    }

    .color-circle {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        margin: 0 auto 15px;
    }

    .form-actions {
        text-align: center;
        margin-top: 40px;
    }

    .preference-summary {
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 40px;
        margin-bottom: 60px;
        text-align: center;
    }

    .preference-summary h2 {
        font-size: 28px;
        margin-bottom: 25px;
        color: var(--primary-color);
    }

    .style-tags {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }

    .style-tag {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 25px;
        font-weight: 600;
        text-transform: capitalize;
    }

    .recommended-section {
        margin-bottom: 60px;
    }

    .recommended-section h2 {
        font-size: 32px;
        text-align: center;
        margin-bottom: 15px;
        color: var(--primary-color);
    }

    .section-subtitle {
        text-align: center;
        color: #666;
        margin-bottom: 40px;
    }

    .match-badge {
        position: absolute;
        top: 12px;
        left: 12px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        z-index: 1;
    }

    .no-recommendations {
        text-align: center;
        padding: 100px 20px;
    }

    .no-recommendations i {
        font-size: 80px;
        color: #ddd;
        margin-bottom: 30px;
    }

    .no-recommendations h3 {
        font-size: 28px;
        margin-bottom: 15px;
        color: var(--primary-color);
    }

    @media (max-width: 768px) {
        .setup-card {
            padding: 30px 20px;
        }

        .options-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<?php require_once 'includes/footer.php'; ?>