<?php
require_once 'includes/db.php';

$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($product_id <= 0) {
    redirect('products.php');
}

// 상품 정보 조회
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        WHERE p.product_id = $product_id AND p.is_active = 1";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    redirect('products.php');
}

$product = $result->fetch_assoc();
$page_title = $product['name'];

// 조회수 증가
$conn->query("UPDATE products SET views = views + 1 WHERE product_id = $product_id");

// 리뷰 조회
$review_sql = "SELECT r.*, u.name as user_name 
               FROM reviews r 
               LEFT JOIN users u ON r.user_id = u.user_id 
               WHERE r.product_id = $product_id AND r.is_approved = 1 
               ORDER BY r.created_at DESC";
$reviews = $conn->query($review_sql);

// 찜하기 여부 확인
$is_wishlist = false;
if (is_logged_in()) {
    $user_id = $_SESSION['user_id'];
    $wishlist_check = $conn->query("SELECT wishlist_id FROM wishlist WHERE user_id = $user_id AND product_id = $product_id");
    $is_wishlist = $wishlist_check->num_rows > 0;
}

// 연관 상품 (같은 카테고리)
$related_sql = "SELECT * FROM products 
                WHERE category_id = {$product['category_id']} 
                AND product_id != $product_id 
                AND is_active = 1 
                ORDER BY RAND() 
                LIMIT 4";
$related_products = $conn->query($related_sql);

require_once 'includes/header.php';
?>

<div class="container">
    <div class="breadcrumb">
        <a href="/index.php">홈</a>
        <i class="fas fa-chevron-right"></i>
        <a
            href="/products.php?category=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a>
        <i class="fas fa-chevron-right"></i>
        <span><?php echo htmlspecialchars($product['name']); ?></span>
    </div>

    <div class="product-detail">
        <!-- 상품 이미지 -->
        <div class="product-images">
            <div class="main-image">
                <img id="mainImage" src="<?php echo htmlspecialchars($product['main_image']); ?>"
                    alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="handleImageError(this)">
            </div>
            <div class="thumbnail-images">
                <!-- 원본 -->
                <div class="thumb-wrapper active"
                    onclick="changeMainImage('<?php echo htmlspecialchars($product['main_image']); ?>', '')">
                    <img src="<?php echo htmlspecialchars($product['main_image']); ?>" alt="원본">
                </div>

                <!-- 2. 따뜻한 느낌 (Sepia) -->
                <div class="thumb-wrapper"
                    onclick="changeMainImage('<?php echo htmlspecialchars($product['main_image']); ?>', 'sepia(30%) contrast(110%)')">
                    <img src="<?php echo htmlspecialchars($product['main_image']); ?>" alt="따뜻한 느낌"
                        style="filter: sepia(30%) contrast(110%);">
                </div>

                <!-- 3. 분위기 있는 (Slightly Darker) -->
                <div class="thumb-wrapper"
                    onclick="changeMainImage('<?php echo htmlspecialchars($product['main_image']); ?>', 'brightness(90%) contrast(120%)')">
                    <img src="<?php echo htmlspecialchars($product['main_image']); ?>" alt="분위기"
                        style="filter: brightness(90%) contrast(120%);">
                </div>

                <!-- 4. 디테일 컷 (Zoomed) -->
                <div class="thumb-wrapper"
                    onclick="changeMainImage('<?php echo htmlspecialchars($product['main_image']); ?>', 'scale(1.5)')">
                    <img src="<?php echo htmlspecialchars($product['main_image']); ?>" alt="디테일"
                        style="transform: scale(1.5); transform-origin: center; object-position: center;">
                </div>
            </div>
        </div>

        <!-- 상품 정보 -->
        <div class="product-details-info">
            <div class="product-tags">
                <span class="tag"><?php echo htmlspecialchars($product['style_tag']); ?></span>
                <span class="tag"><?php echo htmlspecialchars($product['color_tag']); ?></span>
            </div>

            <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>

            <div class="product-rating-detail">
                <div class="stars">
                    <?php
                    $rating = $product['rating'];
                    for ($i = 1; $i <= 5; $i++) {
                        echo ($i <= $rating) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                    }
                    ?>
                </div>
                <span class="rating-number"><?php echo number_format($rating, 1); ?></span>
                <span class="review-count">(<?php echo $product['review_count']; ?>개 리뷰)</span>
            </div>

            <div class="product-price-detail">
                <span class="price"><?php echo format_price($product['price']); ?></span>
            </div>

            <div class="product-description">
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>

            <div class="product-stock">
                <?php if ($product['stock'] > 0): ?>
                    <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                    <span>재고 있음 (<?php echo $product['stock']; ?>개)</span>
                <?php else: ?>
                    <i class="fas fa-times-circle" style="color: var(--danger-color);"></i>
                    <span>품절</span>
                <?php endif; ?>
            </div>

            <div class="quantity-selector">
                <label>수량</label>
                <div class="quantity-controls">
                    <button onclick="updateQuantity(-1)">-</button>
                    <input type="number" id="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                    <button onclick="updateQuantity(1)">+</button>
                </div>
            </div>

            <div class="product-actions">
                <?php if ($product['stock'] > 0): ?>
                    <button class="btn btn-primary btn-large btn-block" onclick="addToCartDetail()">
                        장바구니 담기
                    </button>
                    <button class="btn btn-secondary btn-large btn-block" onclick="buyNow()">
                        <i class="fas fa-bolt"></i> 바로 구매하기
                    </button>
                <?php else: ?>
                    <button class="btn btn-outline btn-large btn-block" disabled>
                        품절
                    </button>
                <?php endif; ?>

                <button class="btn btn-icon-large wishlist-btn-detail <?php echo $is_wishlist ? 'active' : ''; ?>"
                    onclick="toggleWishlistDetail(<?php echo $product_id; ?>)">
                    <i class="<?php echo $is_wishlist ? 'fas' : 'far'; ?> fa-heart"></i>
                </button>
            </div>

            <div class="product-info-list">
                <div class="info-item">
                    <span class="label">배송</span>
                    <span class="value">무료배송 (5만원 이상 구매시)</span>
                </div>
                <div class="info-item">
                    <span class="label">배송기간</span>
                    <span class="value">2-3일 이내</span>
                </div>
                <div class="info-item">
                    <span class="label">반품/교환</span>
                    <span class="value">수령 후 7일 이내 가능</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 상세 설명 탭 -->
    <div class="detail-tabs">
        <div class="tab-buttons">
            <button class="tab-btn active" onclick="showTab('description')">상세설명</button>
            <button class="tab-btn" onclick="showTab('reviews')">리뷰 (<?php echo $product['review_count']; ?>)</button>
            <button class="tab-btn" onclick="showTab('delivery')">배송/반품</button>
        </div>

        <div class="tab-content active" id="description">
            <div class="detail-description">
                <h3>상품 상세 설명</h3>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='800' height='400' viewBox='0 0 800 400'%3E%3Crect width='800' height='400' fill='#f9f9f9'/%3E%3Ctext x='50%25' y='50%25' font-family='Arial' font-size='24' fill='#ccc' text-anchor='middle' dy='.3em'%3EProduct Detail Image%3C/text%3E%3C/svg%3E"
                    alt="Detail">
                <h4>상품 특징</h4>
                <ul>
                    <li>고품질 소재 사용</li>
                    <li>모던하고 세련된 디자인</li>
                    <li>다양한 인테리어와 매칭 가능</li>
                    <li>견고한 구조로 오래 사용 가능</li>
                </ul>
            </div>
        </div>

        <div class="tab-content" id="reviews">
            <div class="reviews-section">
                <div class="reviews-summary">
                    <div class="rating-overview">
                        <div class="rating-score">
                            <span class="score"><?php echo number_format($product['rating'], 1); ?></span>
                            <div class="stars">
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    echo ($i <= $rating) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                }
                                ?>
                            </div>
                            <p><?php echo $product['review_count']; ?>개 리뷰</p>
                        </div>
                    </div>

                    <?php if (is_logged_in()): ?>
                        <button class="btn btn-primary" onclick="showReviewForm()">
                            <i class="fas fa-edit"></i> 리뷰 작성하기
                        </button>
                    <?php else: ?>
                        <p class="login-notice">리뷰를 작성하려면 <a href="/login.php">로그인</a>해주세요.</p>
                    <?php endif; ?>
                </div>

                <!-- 리뷰 작성 폼 -->
                <?php if (is_logged_in()): ?>
                    <div class="review-form" id="reviewForm" style="display: none;">
                        <h3>리뷰 작성</h3>
                        <form id="reviewSubmitForm" onsubmit="submitReview(event)">
                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">

                            <div class="form-group">
                                <label>평점</label>
                                <div class="rating-input">
                                    <input type="radio" name="rating" value="5" id="star5" required checked>
                                    <label for="star5">★</label>
                                    <input type="radio" name="rating" value="4" id="star4">
                                    <label for="star4">★</label>
                                    <input type="radio" name="rating" value="3" id="star3">
                                    <label for="star3">★</label>
                                    <input type="radio" name="rating" value="2" id="star2">
                                    <label for="star2">★</label>
                                    <input type="radio" name="rating" value="1" id="star1">
                                    <label for="star1">★</label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="review_title">제목</label>
                                <input type="text" id="review_title" name="title" class="form-control"
                                    placeholder="리뷰 제목을 입력하세요" required>
                            </div>

                            <div class="form-group">
                                <label for="review_content">내용</label>
                                <textarea id="review_content" name="content" class="form-control"
                                    placeholder="상품에 대한 솔직한 리뷰를 작성해주세요" required></textarea>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn btn-outline" onclick="hideReviewForm()">취소</button>
                                <button type="submit" class="btn btn-primary">등록하기</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- 리뷰 목록 -->
                <div class="reviews-list">
                    <?php if ($reviews->num_rows > 0): ?>
                        <?php while ($review = $reviews->fetch_assoc()): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="reviewer-info">
                                        <div class="avatar" style="background-color: <?php
                                        $colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#ffeead', '#d4a5a5', '#9b59b6'];
                                        echo $colors[crc32($review['user_name']) % count($colors)];
                                        ?>;">
                                            <?php echo mb_substr($review['user_name'], 0, 1); ?>
                                        </div>
                                        <div>
                                            <div class="reviewer-name">
                                                <?php
                                                $name = $review['user_name'];
                                                if (mb_strlen($name) > 2) {
                                                    echo mb_substr($name, 0, 1) . str_repeat('*', mb_strlen($name) - 2) . mb_substr($name, -1);
                                                } else {
                                                    echo mb_substr($name, 0, 1) . '*';
                                                }
                                                ?>
                                                <span class="verified-badge"><i class="fas fa-check-circle"></i> 구매인증</span>
                                            </div>
                                            <div class="review-date"><?php echo format_date($review['created_at']); ?></div>
                                        </div>
                                    </div>
                                    <div class="review-rating">
                                        <?php
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo ($i <= $review['rating']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="review-body">
                                    <h4><?php echo htmlspecialchars($review['title']); ?></h4>
                                    <p><?php echo nl2br(htmlspecialchars($review['content'])); ?></p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-reviews">
                            <i class="far fa-comment-dots"></i>
                            <p>아직 작성된 리뷰가 없습니다.<br>첫 번째 리뷰를 작성해보세요!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="tab-content" id="delivery">
            <div class="info-section">
                <h3>배송 안내</h3>
                <ul>
                    <li>배송비: 기본 3,000원 (5만원 이상 구매시 무료)</li>
                    <li>배송기간: 주문 후 2-3일 이내 (영업일 기준)</li>
                    <li>배송지역: 전국 (일부 도서산간 지역 추가 비용 발생)</li>
                </ul>

                <h3>반품/교환 안내</h3>
                <ul>
                    <li>반품/교환 기간: 상품 수령 후 7일 이내</li>
                    <li>반품 비용: 고객 변심 시 왕복 배송비 부담</li>
                    <li>교환 불가: 상품 훼손, 포장 개봉, 사용 흔적이 있는 경우</li>
                    <li>반품 절차: 고객센터 문의 → 반품 승인 → 상품 반송</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- 연관 상품 -->
    <?php if ($related_products->num_rows > 0): ?>
        <section class="related-products">
            <h2>이런 상품은 어떠세요?</h2>
            <div class="product-grid">
                <?php while ($related = $related_products->fetch_assoc()): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <a href="/product-detail.php?id=<?php echo $related['product_id']; ?>">
                                <img src="<?php echo htmlspecialchars($related['main_image']); ?>"
                                    alt="<?php echo htmlspecialchars($related['name']); ?>"
                                    onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'300\' height=\'300\' viewBox=\'0 0 300 300\'%3E%3Crect width=\'300\' height=\'300\' fill=\'#f0f0f0\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' font-family=\'Arial\' font-size=\'16\' fill=\'#999\' text-anchor=\'middle\' dy=\'.3em\'%3ENo Image%3C/text%3E%3C/svg%3E'">
                            </a>
                        </div>
                        <div class="product-info">
                            <h3 class="product-name">
                                <a href="/product-detail.php?id=<?php echo $related['product_id']; ?>">
                                    <?php echo htmlspecialchars($related['name']); ?>
                                </a>
                            </h3>
                            <div class="product-price">
                                <?php echo format_price($related['price']); ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<style>
    .product-detail {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        margin: 40px 0;
    }

    .product-images {
        position: sticky;
        top: 100px;
        height: fit-content;
    }

    .main-image {
        width: 100%;
        aspect-ratio: 1;
        background: var(--light-gray);
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 20px;
    }

    .main-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .thumbnail-images {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
    }

    .thumb-wrapper {
        position: relative;
        width: 100%;
        aspect-ratio: 1;
        border-radius: 8px;
        overflow: hidden;
        cursor: pointer;
        border: 2px solid transparent;
        transition: all 0.3s;
    }

    .thumb-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .thumb-wrapper:hover,
    .thumb-wrapper.active {
        border-color: var(--secondary-color);
    }

    .product-details-info {
        padding: 20px 0;
    }

    .product-tags {
        display: flex;
        gap: 8px;
        margin-bottom: 15px;
    }

    .tag {
        display: inline-block;
        padding: 4px 12px;
        background: var(--light-gray);
        border-radius: 4px;
        font-size: 13px;
        color: #666;
    }

    .product-title {
        font-size: 32px;
        margin-bottom: 20px;
        color: var(--primary-color);
    }

    .product-rating-detail {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
    }

    .rating-number {
        font-weight: 600;
        color: var(--primary-color);
    }

    .product-price-detail {
        padding: 20px 0;
        border-top: 1px solid var(--border-color);
        border-bottom: 1px solid var(--border-color);
        margin-bottom: 20px;
    }

    .product-price-detail .price {
        font-size: 32px;
        font-weight: 700;
        color: var(--primary-color);
    }

    .product-description {
        margin-bottom: 30px;
        line-height: 1.8;
        color: #666;
    }

    .product-stock {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 30px;
        font-weight: 500;
    }

    .quantity-selector {
        margin-bottom: 30px;
    }

    .quantity-selector label {
        display: block;
        margin-bottom: 10px;
        font-weight: 500;
    }

    .quantity-controls {
        display: flex;
        align-items: center;
        width: fit-content;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        overflow: hidden;
    }

    .quantity-controls button {
        width: 40px;
        height: 40px;
        border: none;
        background: var(--light-gray);
        cursor: pointer;
        font-size: 18px;
        transition: background 0.3s;
    }

    .quantity-controls button:hover {
        background: var(--border-color);
    }

    .quantity-controls input {
        width: 60px;
        height: 40px;
        border: none;
        text-align: center;
        font-size: 16px;
        font-weight: 600;
    }

    .product-actions {
        display: grid;
        grid-template-columns: 1fr 1fr 60px;
        gap: 10px;
        margin-bottom: 30px;
    }

    .btn-icon-large {
        width: 60px;
        height: 60px;
        border-radius: 8px;
        font-size: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .wishlist-btn-detail.active {
        background: var(--danger-color);
        color: white;
        border-color: var(--danger-color);
    }

    .btn-large {
        height: 60px;
        font-size: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        white-space: nowrap;
    }

    @media (max-width: 480px) {
        .product-actions {
            gap: 8px;
        }

        .btn-large {
            font-size: 14px;
            height: 50px;
            padding: 0 10px;
        }

        .btn-icon-large {
            width: 50px;
            height: 50px;
            font-size: 20px;
        }

        .product-actions {
            grid-template-columns: 1fr 1fr 50px;
        }
    }

    .product-info-list {
        background: var(--light-gray);
        border-radius: 8px;
        padding: 20px;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid var(--border-color);
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-item .label {
        font-weight: 600;
        color: var(--primary-color);
    }

    /* 탭 */
    .detail-tabs {
        margin: 60px 0;
    }

    .tab-buttons {
        display: flex;
        gap: 20px;
        border-bottom: 2px solid var(--border-color);
        margin-bottom: 40px;
    }

    .tab-btn {
        padding: 15px 30px;
        background: none;
        border: none;
        font-size: 18px;
        font-weight: 500;
        cursor: pointer;
        color: #666;
        position: relative;
        transition: color 0.3s;
    }

    .tab-btn.active {
        color: var(--secondary-color);
    }

    .tab-btn.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 2px;
        background: var(--secondary-color);
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .detail-description {
        padding: 20px;
    }

    .detail-description img {
        width: 100%;
        border-radius: 12px;
        margin: 30px 0;
    }

    /* 리뷰 */
    .reviews-summary {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 30px;
        background: var(--light-gray);
        border-radius: 12px;
        margin-bottom: 30px;
    }

    .rating-overview {
        display: flex;
        gap: 40px;
    }

    .rating-score {
        text-align: center;
    }

    .rating-score .score {
        font-size: 48px;
        font-weight: 700;
        color: var(--primary-color);
    }

    .rating-score .stars {
        font-size: 24px;
        color: #ffa500;
        margin: 10px 0;
    }

    .review-form {
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 30px;
        margin-bottom: 30px;
    }

    .rating-input {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        gap: 5px;
    }

    .rating-input input {
        display: none;
    }

    .rating-input label {
        font-size: 32px;
        color: #ddd;
        cursor: pointer;
        transition: color 0.3s;
    }

    .rating-input input:checked~label,
    .rating-input label:hover,
    .rating-input label:hover~label {
        color: #ffa500;
    }

    .review-item {
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 20px;
    }

    .review-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .reviewer-info {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--secondary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 18px;
    }

    .reviewer-name {
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .verified-badge {
        font-size: 11px;
        color: #4CAF50;
        background: #e8f5e9;
        padding: 2px 6px;
        border-radius: 4px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 3px;
    }

    .review-date {
        font-size: 13px;
        color: #999;
        margin-top: 2px;
    }

    .review-rating {
        color: #ffa500;
    }

    .review-body h4 {
        margin-bottom: 10px;
        color: var(--primary-color);
        font-size: 16px;
    }

    .review-body p {
        color: #555;
        line-height: 1.6;
    }

    .no-reviews {
        text-align: center;
        padding: 60px 0;
        color: #999;
    }

    .no-reviews i {
        font-size: 48px;
        margin-bottom: 20px;
        opacity: 0.3;
    }

    padding: 80px 20px;
    color: #999;
    }

    .no-reviews i {
        font-size: 64px;
        margin-bottom: 20px;
    }

    .related-products {
        margin: 80px 0;
    }

    .related-products h2 {
        font-size: 28px;
        margin-bottom: 30px;
        color: var(--primary-color);
    }

    .related-products .product-grid {
        grid-template-columns: repeat(4, 1fr);
    }

    @media (max-width: 1024px) {
        .product-detail {
            grid-template-columns: 1fr;
        }

        .product-images {
            position: static;
        }
    }
</style>

<script>
    const productId = <?php echo $product_id; ?>;
    const maxStock = <?php echo $product['stock']; ?>;

    function changeMainImage(src, styleString) {
        const mainImg = document.getElementById('mainImage');
        mainImg.src = src;

        // 스타일 초기화
        mainImg.style.filter = 'none';
        mainImg.style.transform = 'none';
        mainImg.style.objectPosition = 'center';

        // 새로운 스타일 적용
        if (styleString) {
            if (styleString.includes('filter')) {
                mainImg.style.filter = styleString.replace('filter: ', '').replace(';', '');
            } else if (styleString.includes('scale')) {
                mainImg.style.transform = styleString;
                mainImg.style.transformOrigin = 'center'; // 확대 시 중심 기준
            } else {
                // 직접 스타일 속성으로 들어온 경우 (예: sepiac 등)
                // 간단하게 처리하기 위해 조건문 사용
                if (styleString.includes('sepia') || styleString.includes('brightness')) {
                    mainImg.style.filter = styleString;
                }
            }
        }

        document.querySelectorAll('.thumb-wrapper').forEach(wrapper => {
            wrapper.classList.remove('active');
        });
        event.currentTarget.classList.add('active');
    }

    function updateQuantity(change) {
        const input = document.getElementById('quantity');
        const current = parseInt(input.value);
        const newValue = current + change;

        if (newValue >= 1 && newValue <= maxStock) {
            input.value = newValue;
        }
    }

    function addToCartDetail() {
        const quantity = parseInt(document.getElementById('quantity').value);

        fetch('api/cart-add.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, quantity: quantity })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('장바구니에 추가되었습니다', 'success');
                    if (confirm('장바구니로 이동하시겠습니까?')) {
                        window.location.href = 'cart.php';
                    }
                } else {
                    if (data.message === 'not_logged_in') {
                        showNotification('로그인이 필요합니다', 'error');
                        setTimeout(() => {
                            window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                        }, 1000);
                    } else {
                        // 구체적인 에러 메시지 표시
                        showNotification(data.message || '오류가 발생했습니다', 'error');
                    }
                }
            })
            .catch(error => {
                console.error(error);
                showNotification('서버 통신 오류: ' + error.message, 'error');
            });
    }

    function buyNow() {
        const quantity = parseInt(document.getElementById('quantity').value);

        fetch('api/cart-add.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, quantity: quantity })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'cart.php';
                } else {
                    if (data.message === 'not_logged_in') {
                        showNotification('로그인이 필요합니다', 'error');
                        setTimeout(() => {
                            window.location.href = 'login.php?redirect=cart.php';
                        }, 1000);
                    } else {
                        // 구체적인 에러 메시지 표시
                        showNotification(data.message || '오류가 발생했습니다', 'error');
                    }
                }
            })
            .catch(error => {
                console.error(error);
                showNotification('서버 통신 오류: ' + error.message, 'error');
            });
    }

    function toggleWishlistDetail(productId) {
        fetch('./api/wishlist-toggle.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const btn = document.querySelector('.wishlist-btn-detail');
                    const icon = btn.querySelector('i');

                    if (data.action === 'added') {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        btn.classList.add('active');
                        showNotification('찜 목록에 추가되었습니다', 'success');
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        btn.classList.remove('active');
                        showNotification('찜 목록에서 제거되었습니다', 'info');
                    }
                }
            });
    }

    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;

        const bgColor = type === 'success' ? '#4CAF50' : (type === 'error' ? '#f44336' : '#2196F3');

        notification.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: ${bgColor};
            color: white;
            padding: 15px 25px;
            border-radius: 4px;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            font-size: 14px;
        `;

        document.body.appendChild(notification);

        // Trigger reflow
        notification.offsetHeight;
        notification.style.opacity = '1';

        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }

    function showTab(tabName) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

        event.target.classList.add('active');
        document.getElementById(tabName).classList.add('active');
    }

    function showReviewForm() {
        document.getElementById('reviewForm').style.display = 'block';
        document.getElementById('reviewForm').scrollIntoView({ behavior: 'smooth' });
    }

    function hideReviewForm() {
        document.getElementById('reviewForm').style.display = 'none';
    }

    function submitReview(event) {
        event.preventDefault();

        const form = document.getElementById('reviewSubmitForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Handle rating manually if radio buttons are tricky with FormData in some browsers/structures, 
        // but normally FormData works fine for radio groups.
        // Just in case, ensure rating is present.
        if (!data.rating) {
            data.rating = document.querySelector('input[name="rating"]:checked')?.value || 5;
        }

        fetch('/homedeco-shop/api/review-submit.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showNotification('리뷰가 등록되었습니다', 'success');
                    // Reload page to show new review
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(result.message || '리뷰 등록 실패', 'error');
                }
            })
            .catch(error => {
                showNotification('서버 통신 오류', 'error');
                console.error(error);
            });
    }
</script>

<?php require_once 'includes/footer.php'; ?>