<?php
$page_title = 'COZY-DECO - 당신의 공간을 특별하게';
require_once 'includes/db.php';
require_once 'includes/naver_api.php';

// 신상품 조회 (DB) - 최신순 8개
$sql = "SELECT * FROM products WHERE is_active = 1 ORDER BY created_at DESC LIMIT 8";
$result = $conn->query($sql);
$new_products_data = $result->fetch_all(MYSQLI_ASSOC);

$body_class = 'page-index';
require_once 'includes/header.php';
?>

<!-- Hero Slider -->
<div class="hero-slider">
    <div class="swiper heroSwiper">
        <div class="swiper-wrapper">
            <div class="swiper-slide hero-slide" onclick="location.href='/homedeco-shop/products.php?category=1'"
                style="cursor: pointer;">
                <img src="https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?w=1920&h=600&fit=crop&crop=center"
                    alt="Modern Living Room">
                <div class="hero-overlay">
                    <h2>공간을 채우는 따뜻함</h2>
                    <p>당신의 거실을 COZY-DECO와 함께 특별하게 만들어보세요</p>
                </div>
            </div>
            <div class="swiper-slide hero-slide" onclick="location.href='/homedeco-shop/products.php?category=2'"
                style="cursor: pointer;">
                <img src="https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?w=1920&h=600&fit=crop&crop=center"
                    alt="Elegant Bedroom">
                <div class="hero-overlay">
                    <h2>가장 프라이빗한 휴식</h2>
                    <p>포근함이 가득한 침실 인테리어 제안</p>
                </div>
            </div>
            <div class="swiper-slide hero-slide" onclick="location.href='/homedeco-shop/products.php?category=4'"
                style="cursor: pointer;">
                <img src="https://images.unsplash.com/photo-1615529328331-f8917597711f?w=1920&h=600&fit=crop&crop=center"
                    alt="Stylish Dining Room">
                <div class="hero-overlay">
                    <h2>빛으로 완성하는 무드</h2>
                    <p>공간의 분위기를 결정짓는 감각적인 조명 컬렉션</p>
                </div>
            </div>
        </div>
        <div class="swiper-pagination"></div>
    </div>
</div>

<div class="layout-spacer"></div>

<div class="container">
    <!-- 비회원 환영 배너 (로그인 안 했을 때만 표시) -->
    <?php if (!is_logged_in()): ?>
        <section class="guest-welcome-banner"
            style="margin: 60px auto; max-width: 1200px; background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%); padding: 30px; border-radius: 20px; text-align: center; border: 1px solid #e0e0e0; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
            <h3 style="color: #2c3e50; margin-bottom: 10px; font-size: 20px;">🚪 처음이신가요? 가입 없이 바로 쇼핑해보세요!</h3>
            <p style="color: #7f8c8d; font-size: 15px; margin-bottom: 20px;">로그인 없이도 장바구니 이용 및 비회원 주문이 가능합니다.</p>
            <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
                <a href="/homedeco-shop/products.php" class="btn btn-primary"
                    style="padding: 10px 25px; border-radius: 30px; white-space: nowrap;">상품 둘러보기</a>
                <a href="/homedeco-shop/login.php" class="btn btn-outline"
                    style="padding: 10px 25px; border-radius: 30px; border-color: #2c3e50; color: #2c3e50; white-space: nowrap;">로그인/회원가입</a>
            </div>
        </section>
    <?php endif; ?>

    <!-- 카테고리 탭 섹션 -->
    <section class="collection-section categories">
        <div class="section-title-wrapper" style="text-align: center; margin-bottom: 40px;">
            <h2 class="section-title">카테고리</h2>
            <p class="section-subtitle">Shop by Category - 당신의 취향을 저격할 특별한 공간 큐레이션</p>
        </div>

        <div class="category-tabs">
            <button class="tab-item active" data-category="new">
                <span class="tab-label">NEW</span>
                <span class="tab-sub">신상품</span>
            </button>
            <button class="tab-item" data-category="1">
                <span class="tab-label">LIVING</span>
                <span class="tab-sub">거실</span>
            </button>
            <button class="tab-item" data-category="2">
                <span class="tab-label">BEDROOM</span>
                <span class="tab-sub">침실</span>
            </button>
            <button class="tab-item" data-category="3">
                <span class="tab-label">KITCHEN</span>
                <span class="tab-sub">주방</span>
            </button>
            <button class="tab-item" data-category="4">
                <span class="tab-label">LIGHTING</span>
                <span class="tab-sub">조명</span>
            </button>
            <button class="tab-item" data-category="5">
                <span class="tab-label">DECOR</span>
                <span class="tab-sub">소품</span>
            </button>
        </div>

        <div id="product-display-area" class="product-grid-container">
            <!-- 동적으로 로드될 영역 -->
            <div class="product-grid active" id="collection-grid">
                <?php if (!empty($new_products_data)): ?>
                    <?php
                    $now = time();
                    $new_count = 0;
                    foreach ($new_products_data as $product):
                        // 14일 이내 등록 상품만 "NEW"로 간주
                        $created_time = strtotime($product['created_at']);
                        $is_new = ($now - $created_time) <= (14 * 24 * 60 * 60);
                        if (!$is_new)
                            continue;
                        $new_count++;
                        ?>
                        <div class="product-card">
                            <div class="product-image">
                                <a href="/homedeco-shop/product-detail.php?id=<?= $product['product_id']; ?>">
                                    <img src="<?= htmlspecialchars($product['main_image']); ?>"
                                        alt="<?= htmlspecialchars($product['name']); ?>" onerror="handleImageError(this)">
                                </a>
                                <div class="product-badges">
                                    <span class="badge-new">NEW</span>
                                </div>
                            </div>
                            <div class="product-info">
                                <h3 class="product-name">
                                    <a href="/homedeco-shop/product-detail.php?id=<?= $product['product_id']; ?>">
                                        <?= htmlspecialchars($product['name']); ?>
                                    </a>
                                </h3>
                                <div class="product-price">
                                    <span class="amount"><?= number_format($product['price']); ?></span>원
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($new_count === 0): ?>
                        <div class="no-items">최근 등록된 신상품이 없습니다.</div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-items">등록된 상품이 없습니다.</div>
                <?php endif; ?>
            </div>

            <div class="view-more-wrapper" style="text-align: center; margin-top: 50px;">
                <a href="/homedeco-shop/products.php" id="active-category-link" class="btn btn-outline"
                    style="min-width: 220px; border-radius: 50px; font-weight: 600; padding: 12px 30px;">전체 상품 보기 <i
                        class="fas fa-arrow-right" style="margin-left:10px;"></i></a>
            </div>
        </div>
    </section>

    <!-- 쿠폰 섹션 -->
    <section class="coupon-section" style="background: #fcfcfc; padding: 80px 0; margin: 100px 0; border-radius: 50px;">
        <div class="section-header" style="text-align: center; margin-bottom: 60px;">
            <h2 class="section-title">🎁 COZY-DECO 특별 혜택</h2>
            <p style="color: #888; margin-top: 10px;">쇼핑이 더 즐거워지는 오늘의 쿠폰팩을 받아가세요</p>
        </div>

        <div class="coupon-grid" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <!-- 1. 신규회원 -->
            <div class="coupon-card">
                <div class="coupon-badge">WELCOME</div>
                <div class="coupon-content">
                    <h3>신규가입 웰컴 쿠폰</h3>
                    <p>전 상품 10% 할인 (최대 1만원)</p>
                    <div class="coupon-price">10% OFF</div>
                </div>
                <div class="coupon-action">
                    <button class="btn-download" onclick="downloadCoupon(this)">
                        <i class="fas fa-download"></i> 받기
                    </button>
                </div>
                <div class="coupon-deco"></div>
            </div>

            <!-- 2. 무료배송 -->
            <div class="coupon-card">
                <div class="coupon-badge" style="color: #2ecc71;">SHIPPING</div>
                <div class="coupon-content">
                    <h3>배송비 0원 쿠폰</h3>
                    <p>3만원 이상 구매 시 무료배송</p>
                    <div class="coupon-price">Free</div>
                </div>
                <div class="coupon-action">
                    <button class="btn-download" onclick="downloadCoupon(this)">
                        <i class="fas fa-download"></i> 받기
                    </button>
                </div>
                <div class="coupon-deco"></div>
            </div>

            <!-- 3. 깜짝 할인 -->
            <div class="coupon-card">
                <div class="coupon-badge" style="color: #e67e22;">SPECIAL</div>
                <div class="coupon-content">
                    <h3>깜짝 할인 쿠폰</h3>
                    <p>5만원 이상 구매 시 즉시 할인</p>
                    <div class="coupon-price">5,000원</div>
                </div>
                <div class="coupon-action">
                    <button class="btn-download" onclick="downloadCoupon(this)">
                        <i class="fas fa-download"></i> 받기
                    </button>
                </div>
                <div class="coupon-deco"></div>
            </div>

            <!-- 4. 재구매 감사 -->
            <div class="coupon-card">
                <div class="coupon-badge" style="color: #9b59b6;">THANK YOU</div>
                <div class="coupon-content">
                    <h3>재구매 감사 쿠폰</h3>
                    <p>마지막 주문 후 30일 이내 재구매</p>
                    <div class="coupon-price">3,000원</div>
                </div>
                <div class="coupon-action">
                    <button class="btn-download" onclick="downloadCoupon(this)">
                        <i class="fas fa-download"></i> 받기
                    </button>
                </div>
                <div class="coupon-deco"></div>
            </div>
        </div>
    </section>
</div>

<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
    // Hero Slider 초기화
    const heroSwiper = new Swiper('.heroSwiper', {
        loop: true,
        speed: 1000,
        effect: 'fade',
        fadeEffect: { crossFade: true },
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
    });

    // 탭 클릭 이벤트
    document.querySelectorAll('.tab-item').forEach(tab => {
        tab.addEventListener('click', function () {
            const category = this.dataset.category;

            // 활성 탭 표시
            document.querySelectorAll('.tab-item').forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            // 데이터 로드
            loadTabProducts(category);
        });
    });

    function loadTabProducts(category) {
        const grid = document.getElementById('collection-grid');
        const viewAllLink = document.getElementById('active-category-link');

        // 페이드 아웃 효과
        grid.style.opacity = '0';
        grid.style.transform = 'translateY(10px)';

        setTimeout(() => {
            // AJAX 요청
            fetch(`/homedeco-shop/api/category_products.php?category=${category}&limit=8`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.products.length > 0) {
                        let html = '';
                        const now = Math.floor(Date.now() / 1000);
                        const fourteenDays = 14 * 24 * 60 * 60;
                        let visibleCount = 0;

                        data.products.forEach(p => {
                            const createdTime = Math.floor(new Date(p.created_at).getTime() / 1000);
                            const isNew = (now - createdTime) <= fourteenDays;

                            // 'new' 카테고리일 경우, 진짜 신상품이 아니면 건너뜀
                            if (category === 'new' && !isNew) return;

                            visibleCount++;
                            const price = new Intl.NumberFormat('ko-KR').format(p.price);
                            const badgeHtml = (category === 'new' && isNew) ? `<div class="product-badges"><span class="badge-new">NEW</span></div>` : '';

                            html += `
                                <div class="product-card">
                                    <div class="product-image">
                                        <a href="/homedeco-shop/product-detail.php?id=${p.product_id}">
                                            <img src="${p.main_image}" alt="${p.name}" onerror="handleImageError(this)">
                                        </a>
                                        ${badgeHtml}
                                    </div>
                                    <div class="product-info">
                                        <h3 class="product-name">
                                            <a href="/homedeco-shop/product-detail.php?id=${p.product_id}">${p.name}</a>
                                        </h3>
                                        <div class="product-price">
                                            <span class="amount">${price}</span>원
                                        </div>
                                    </div>
                                </div>
                            `;
                        });

                        if (visibleCount > 0) {
                            grid.innerHTML = html;
                        } else {
                            grid.innerHTML = `<div class="no-items">${category === 'new' ? '최근 등록된 신상품이 없습니다.' : '해당 카테고리에 상품이 없습니다.'}</div>`;
                        }
                        viewAllLink.href = category === 'new' ? '/homedeco-shop/products.php?sort=newest' : `/homedeco-shop/products.php?category=${category}`;
                    } else {
                        grid.innerHTML = '<div class="no-items">해당 카테고리에 상품이 없습니다.</div>';
                    }

                    // 페이드 인 효과
                    grid.style.opacity = '1';
                    grid.style.transform = 'translateY(0)';
                })
                .catch(err => {
                    console.error(err);
                    grid.innerHTML = '<div class="no-items" style="color: #f44336;">데이터를 불러오는 데 실패했습니다.</div>';
                    grid.style.opacity = '1';
                });
        }, 300);
    }

    function downloadCoupon(btn) {
        if (btn.classList.contains('downloaded')) return;

        // 로그인 여부 체크
        const isLoggedIn = <?php echo is_logged_in() ? 'true' : 'false'; ?>;
        if (!isLoggedIn) {
            if (confirm('쿠폰을 받으려면 로그인이 필요합니다. 로그인 페이지로 이동하시겠습니까?')) {
                location.href = '/homedeco-shop/login.php?redirect=mypage.php#coupons';
            }
            return;
        }

        const card = btn.closest('.coupon-card');
        const title = card.querySelector('h3').innerText;
        const condition = card.querySelector('p').innerText;
        const price = card.querySelector('.coupon-price').innerText;
        const badge = card.querySelector('.coupon-badge');
        const badgeColor = badge.style.color || '#3498db';

        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        setTimeout(() => {
            // localStorage 발급 처리 (mypage.php의 loadMyCoupons와 연동)
            const myCoupons = JSON.parse(localStorage.getItem('homedeco_my_coupons') || '[]');

            // 중복 발급 방지
            if (myCoupons.some(c => c.title === title)) {
                alert('이미 발급된 쿠폰입니다.');
                btn.innerHTML = '<i class="fas fa-check"></i> 발급완료';
                btn.classList.add('downloaded');
                btn.style.background = '#eee';
                btn.style.color = '#888';
                return;
            }

            myCoupons.push({
                id: Date.now(),
                title: title,
                condition: condition,
                price: price,
                badgeColor: badgeColor
            });
            localStorage.setItem('homedeco_my_coupons', JSON.stringify(myCoupons));

            btn.innerHTML = '<i class="fas fa-check"></i> 발급완료';
            btn.classList.add('downloaded');
            btn.style.background = '#eee';
            btn.style.color = '#888';

            alert('쿠폰이 성공적으로 발급되었습니다! 마이페이지 쿠폰함으로 이동합니다.');
            location.href = '/homedeco-shop/mypage.php#coupons';
        }, 800);
    }
</script>

<?php require_once 'includes/footer.php'; ?>