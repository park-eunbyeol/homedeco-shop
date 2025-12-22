<?php
$page_title = '상품 목록';
require_once 'includes/db.php';

// 필터 및 정렬 파라미터
$category_id = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$sort = isset($_GET['sort']) ? clean_input($_GET['sort']) : 'newest';

// 카테고리 이름 매핑
$category_names = [
    1 => '거실',
    2 => '침실',
    3 => '주방/식당',
    4 => '조명',
    5 => '소품'
];

$category_name = $category_id > 0 ? ($category_names[$category_id] ?? '전체 상품') : '전체 상품';

// SQL 쿼리 구성
$where_conditions = ["is_active = 1"];
$params = [];
$types = "";

if ($category_id > 0) {
    $where_conditions[] = "category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

if (!empty($search)) {

    // 카테고리명 검색 지원
    $matched_category_ids = [];
    foreach ($category_names as $id => $name) {
        // 검색어가 카테고리명에 포함되거나, 카테고리명이 검색어에 포함되는 경우
        if (mb_strpos($name, $search) !== false || mb_strpos($search, $name) !== false) {
            $matched_category_ids[] = $id;
        }
    }

    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";

    if (!empty($matched_category_ids)) {
        $cat_in = implode(',', $matched_category_ids);
        $where_conditions[] = "(name LIKE ? OR description LIKE ? OR category_id IN ($cat_in))";
    } else {
        $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
    }

    $category_name = "'{$search}' 검색 결과";
}

$where_clause = implode(" AND ", $where_conditions);

// 정렬 옵션
$order_by = "created_at DESC"; // 기본값
if ($sort === 'price_low')
    $order_by = 'price ASC';
elseif ($sort === 'price_high')
    $order_by = 'price DESC';
elseif ($sort === 'popular')
    $order_by = 'views DESC';
elseif ($sort === 'rating')
    $order_by = 'rating DESC';

// 전체 상품 수 조회
$count_sql = "SELECT COUNT(*) as total FROM products WHERE {$where_clause}";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_items = $count_stmt->get_result()->fetch_assoc()['total'];

// 페이지네이션
$items_per_page = 12;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$total_pages = ceil($total_items / $items_per_page);
$offset = ($page - 1) * $items_per_page;

// 상품 조회
$sql = "SELECT * FROM products WHERE {$where_clause} ORDER BY {$order_by} LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$params[] = $items_per_page;
$params[] = $offset;
$types .= "ii";
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);

// 외부 상품 검색 (네이버 API)
$external_products = [];
if (!empty($search)) {
    require_once 'includes/naver_api.php';
    $external_products = search_naver_products($search, 12);
}

// 전체 표시 개수 (내부 + 외부)
$total_display_count = $total_items + count($external_products);

require_once 'includes/header.php';
?>

<div class="container">
    <div class="products-layout">
        <!-- 사이드바 필터 -->
        <aside class="filters-sidebar">
            <div class="filter-section">
                <h3>카테고리</h3>
                <ul class="filter-list">
                    <li><a href="products.php" class="<?php echo $category_id == 0 ? 'active' : ''; ?>">전체</a></li>
                    <li><a href="products.php?category=1"
                            class="<?php echo $category_id == 1 ? 'active' : ''; ?>">거실</a></li>
                    <li><a href="products.php?category=2"
                            class="<?php echo $category_id == 2 ? 'active' : ''; ?>">침실</a></li>
                    <li><a href="products.php?category=3"
                            class="<?php echo $category_id == 3 ? 'active' : ''; ?>">주방/식당</a></li>
                    <li><a href="products.php?category=4"
                            class="<?php echo $category_id == 4 ? 'active' : ''; ?>">조명</a></li>
                    <li><a href="products.php?category=5"
                            class="<?php echo $category_id == 5 ? 'active' : ''; ?>">소품</a></li>
                </ul>
            </div>

            <!-- 인기 검색어 -->
            <div class="filter-section">
                <h3>인기 검색어</h3>
                <div class="popular-keywords">
                    <a href="?search=북유럽 인테리어" class="keyword-tag">#북유럽</a>
                    <a href="?search=미니멀 가구" class="keyword-tag">#미니멀</a>
                    <a href="?search=무드등" class="keyword-tag">#무드등</a>
                    <a href="?search=소파" class="keyword-tag">#소파</a>
                    <a href="?search=침대" class="keyword-tag">#침대</a>
                    <a href="?search=식탁" class="keyword-tag">#식탁</a>
                    <a href="?search=수납장" class="keyword-tag">#수납</a>
                    <a href="?search=벽시계" class="keyword-tag">#시계</a>
                </div>
            </div>

            <!-- 추천 카테고리 (Premium Muted Design) -->
            <div class="filter-section premium-recommend"
                style="position: relative; overflow: hidden; height: 320px; padding: 0; border: none; background: #f8f9fa;">
                <div class="recommend-bg" style="
                    position: absolute; top:0; left:0; width:100%; height:100%;
                    background-image: url('images/weekly_recommend.png');
                    background-size: cover; background-position: center;
                    transition: transform 0.5s ease;
                "></div>
                <div class="recommend-overlay" style="
                    position: absolute; top:0; left:0; width:100%; height:100%;
                    background: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(0,0,0,0.5));
                    display: flex; flex-direction: column; justify-content: flex-end;
                    padding: 30px; box-sizing: border-box; color: white;
                ">
                    <h3
                        style="color: white; margin-bottom: 8px; font-size: 1.3rem; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                        이번 주 추천
                    </h3>
                    <p style="font-size: 14px; line-height: 1.5; margin-bottom: 20px; opacity: 0.95; font-weight: 400;">
                        따뜻한 조명 하나로 완성하는<br>나만의 아늑한 공간.
                    </p>
                    <a href="?category=4" class="btn-recommend" style="
                        background: rgba(255,255,255,0.9); color: #333; 
                        text-align: center; padding: 12px; border-radius: 8px; 
                        text-decoration: none; font-weight: 600; font-size: 14px;
                        transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
                    " onmouseover="this.style.background='#fff'; this.style.transform='translateY(-2px)';"
                        onmouseout="this.style.background='rgba(255,255,255,0.9)'; this.style.transform='translateY(0)';">
                        조명 컬렉션 보기
                    </a>
                </div>
            </div>

            <style>
                .premium-recommend:hover .recommend-bg {
                    transform: scale(1.05);
                }
            </style>

            <!-- 쇼핑 가이드 -->
            <div class="filter-section">
                <h3>쇼핑 팁</h3>
                <ul style="list-style: disc; padding-left: 18px; font-size: 13px; line-height: 1.8; color: #666;">
                    <li style="margin-bottom: 8px;">상품 비교는 여러 개 열어서 확인</li>
                    <li style="margin-bottom: 8px;">리뷰가 많은 상품 우선 확인</li>
                    <li style="margin-bottom: 8px;">배송비 포함 가격 체크</li>
                    <li style="margin-bottom: 8px;">교환/반품 정책 확인 필수</li>
                </ul>
            </div>
        </aside>

        <!-- 상품 목록 -->
        <div class="products-content">
            <div class="products-header">
                <div class="breadcrumb">
                    <a href="index.php">홈</a>
                    <i class="fas fa-chevron-right"></i>
                    <?php if ($category_id > 0): ?>
                        <span><?php echo $category_name; ?></span>
                    <?php else: ?>
                        <span>전체 상품</span>
                    <?php endif; ?>
                </div>

                <div class="products-info">
                    <h1>
                        <?php
                        if ($category_id > 0) {
                            echo $category_name;
                        } elseif (!empty($search)) {
                            echo "'" . htmlspecialchars($search) . "' 검색 결과";
                        } elseif (isset($_GET['sort']) && $_GET['sort'] === 'newest') {
                            echo "신상품";
                        } else {
                            echo "전체 상품";
                        }
                        ?>
                    </h1>
                    <p class="result-count">총 <strong><?php echo number_format($total_display_count); ?></strong>개의 상품
                    </p>
                </div>

                <div class="sort-options">
                    <select onchange="location.href=this.value">
                        <option value="?sort=newest<?php echo $category_id > 0 ? '&category=' . $category_id : ''; ?>"
                            <?php echo $sort == 'newest' ? 'selected' : ''; ?>>최신순</option>
                        <option value="?sort=popular<?php echo $category_id > 0 ? '&category=' . $category_id : ''; ?>"
                            <?php echo $sort == 'popular' ? 'selected' : ''; ?>>인기순</option>
                        <option
                            value="?sort=price_low<?php echo $category_id > 0 ? '&category=' . $category_id : ''; ?>"
                            <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>낮은 가격순</option>
                        <option
                            value="?sort=price_high<?php echo $category_id > 0 ? '&category=' . $category_id : ''; ?>"
                            <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>높은 가격순</option>
                        <option value="?sort=rating<?php echo $category_id > 0 ? '&category=' . $category_id : ''; ?>"
                            <?php echo $sort == 'rating' ? 'selected' : ''; ?>>평점순</option>
                    </select>
                </div>
            </div>




            <?php if (!empty($products)): ?>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <?php $is_sold_out = isset($product['stock']) && $product['stock'] <= 0; ?>
                        <div class="product-card <?php echo $is_sold_out ? 'sold-out' : ''; ?>">
                            <div class="product-image">
                                <a href="product-detail.php?id=<?php echo $product['product_id']; ?>">
                                    <img src="<?php echo htmlspecialchars($product['main_image']); ?>"
                                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                                        onerror="handleImageError(this)">
                                    <?php if ($is_sold_out): ?>
                                        <div class="sold-out-overlay"><span>품절</span></div>
                                    <?php endif; ?>
                                </a>
                                <div class="product-actions">
                                    <button class="btn-icon wishlist-btn"
                                        data-product-id="<?php echo $product['product_id']; ?>" title="찜하기">
                                        <i class="far fa-heart"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="product-info">
                                <h3 class="product-name">
                                    <a href="product-detail.php?id=<?php echo $product['product_id']; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </h3>
                                <div class="product-rating">
                                    <div class="stars">
                                        <?php
                                        $rating = $product['rating'] ?? 0;
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo ($i <= $rating) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                        }
                                        ?>
                                    </div>
                                    <span class="review-count">(<?php echo $product['review_count'] ?? 0; ?>)</span>
                                </div>
                                <div class="product-price">
                                    <?php echo number_format($product['price']); ?>원
                                </div>
                                <?php if (isset($product['stock']) && $product['stock'] > 0): ?>
                                    <button class="btn btn-primary btn-block add-to-cart-btn"
                                        data-product-id="<?php echo $product['product_id']; ?>">
                                        장바구니
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-block" disabled
                                        style="background-color: #ccc; border-color: #ccc; cursor: not-allowed;">
                                        <i class="fas fa-times-circle"></i> 품절
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- 페이지네이션 -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&category=<?php echo $category_id; ?>&sort=<?php echo $sort; ?>"
                                class="page-link">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                <a href="?page=<?php echo $i; ?>&category=<?php echo $category_id; ?>&sort=<?php echo $sort; ?>"
                                    class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                <span class="page-ellipsis">...</span>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&category=<?php echo $category_id; ?>&sort=<?php echo $sort; ?>"
                                class="page-link">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php elseif (empty($external_products)): ?>
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>검색 결과가 없습니다</h3>
                    <p>다른 검색어나 필터를 시도해보세요.</p>
                    <a href="/products.php" class="btn btn-primary">전체 상품 보기</a>
                </div>
            <?php endif; ?>

            <!-- 외부 상품 결과 표시 (네이버 쇼핑) -->
            <?php if (!empty($external_products)): ?>
                <div class="external-products-section"
                    style="margin-top: 60px; padding-top: 40px; border-top: 1px solid #eee;">
                    <h2 class="section-title" style="margin-bottom: 20px; font-size: 20px; color: #333;">
                        <i class="fas fa-search-plus" style="color: #2c3e50; margin-right: 8px;"></i>
                        '<?php echo htmlspecialchars($search); ?>' 관련 추천 상품
                    </h2>

                    <div class="product-grid">
                        <?php foreach ($external_products as $loop_index => $item): ?>
                            <div class="product-card">
                                <form action="create_and_redirect.php" method="post" id="ext_form_<?php echo $loop_index; ?>">
                                    <input type="hidden" name="name" value="<?php echo htmlspecialchars($item['name']); ?>">
                                    <input type="hidden" name="price" value="<?php echo $item['price']; ?>">
                                    <input type="hidden" name="image"
                                        value="<?php echo htmlspecialchars($item['main_image']); ?>">
                                    <input type="hidden" name="link" value="<?php echo htmlspecialchars($item['link']); ?>">
                                    <input type="hidden" name="brand"
                                        value="<?php echo htmlspecialchars($item['brand'] ?? ''); ?>">

                                    <div class="product-image">
                                        <a href="#"
                                            onclick="document.getElementById('ext_form_<?php echo $loop_index; ?>').submit(); return false;">
                                            <img src="<?php echo htmlspecialchars($item['main_image']); ?>"
                                                alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        </a>
                                    </div>
                                    <div class="product-info">
                                        <h3 class="product-name">
                                            <a href="#"
                                                onclick="document.getElementById('ext_form_<?php echo $loop_index; ?>').submit(); return false;">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </a>
                                        </h3>

                                        <?php
                                        // 랜덤 평점 및 리뷰 수 생성 (외부 상품 시뮬레이션)
                                        $random_rating = rand(35, 50) / 10; // 3.5 ~ 5.0
                                        $random_review_count = rand(10, 500);
                                        ?>
                                        <input type="hidden" name="rating" value="<?php echo $random_rating; ?>">
                                        <input type="hidden" name="review_count" value="<?php echo $random_review_count; ?>">

                                        <div class="product-rating">
                                            <div class="stars">
                                                <?php
                                                for ($i = 1; $i <= 5; $i++) {
                                                    echo ($i <= $random_rating) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                                }
                                                ?>
                                            </div>
                                            <span class="review-count">(<?php echo $random_review_count; ?>)</span>
                                        </div>

                                        <div class="product-price">
                                            <?php echo number_format($item['price']); ?>원
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>



<script>
    // 찜하기 버튼
    document.querySelectorAll('.wishlist-btn').forEach(btn => {
        btn.addEventListener('click', async function (e) {
            e.preventDefault();
            const productId = this.dataset.productId;

            try {
                const response = await fetch('/homedeco-shop/api/wishlist-toggle.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}`
                });

                const data = await response.json();

                if (data.success) {
                    // 아이콘 토글
                    const icon = this.querySelector('i');
                    if (data.action === 'added') {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        showNotification('찜 목록에 추가되었습니다', 'success');
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        showNotification('찜 목록에서 제거되었습니다', 'info');
                    }
                } else {
                    if (data.message.includes('로그인')) {
                        if (confirm('로그인이 필요합니다. 로그인 페이지로 이동하시겠습니까?')) {
                            window.location.href = '/homedeco-shop/login.php';
                        }
                    } else {
                        showNotification(data.message, 'error');
                    }
                }
            } catch (error) {
                showNotification('오류가 발생했습니다', 'error');
            }
        });
    });

    // 장바구니 버튼
    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
        btn.addEventListener('click', async function (e) {
            e.preventDefault();
            const productId = this.dataset.productId;

            try {
                const response = await fetch('/homedeco-shop/api/cart-add.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}&quantity=1`
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('장바구니에 추가되었습니다', 'success');
                    // 장바구니 개수 업데이트 (헤더에 있다면)
                    updateCartCount();
                } else {
                    if (data.message.includes('로그인')) {
                        if (confirm('로그인이 필요합니다. 로그인 페이지로 이동하시겠습니까?')) {
                            window.location.href = '/homedeco-shop/login.php';
                        }
                    } else {
                        showNotification(data.message, 'error');
                    }
                }
            } catch (error) {
                showNotification('오류가 발생했습니다', 'error');
            }
        });
    });

    // 알림 표시 함수
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : '#2196f3'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // 장바구니 개수 업데이트
    async function updateCartCount() {
        try {
            const response = await fetch('/homedeco-shop/api/cart-count.php');
            const data = await response.json();
            if (data.success) {
                const cartBadge = document.querySelector('.cart-count');
                if (cartBadge) {
                    cartBadge.textContent = data.count;
                }
            }
        } catch (error) {
            console.error('장바구니 개수 업데이트 실패:', error);
        }
    }

    // 애니메이션 CSS
    const style = document.createElement('style');
    style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
     }
    }
`;
    document.head.appendChild(style);
    // 외부(네이버) 상품 장바구니 추가
    async function addExternalToCart(product) {
        try {
            const response = await fetch('/homedeco-shop/api/cart-add-external.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(product)
            });

            const data = await response.json();

            if (data.success) {
                showNotification('장바구니에 추가되었습니다.', 'success');
                updateCartCount();

                // 버튼 스타일 변경 (피드백)
                if (event && event.target) {
                    const btn = event.target.closest('button');
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-check"></i> 담기 완료';
                    btn.classList.add('btn-success');
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.classList.remove('btn-success');
                    }, 2000);
                }
            } else {
                if (data.message.includes('로그인')) {
                    alert(data.message); // 게스트도 허용하므로 로그인 메시지는 없을 수 있음
                } else {
                    showNotification(data.message, 'error');
                }
            }
        } catch (error) {
            console.error(error);
            showNotification('오류가 발생했습니다.', 'error');
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>