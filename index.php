<?php
$page_title = 'COZY-DECO - 당신의 공간을 특별하게';
require_once 'includes/db.php';
require_once 'includes/naver_api.php';

/*
 * 닷홈 안정 버전
 * - 시간 계산 없음
 * - 최신 상품 8개만 출력
 */
$sql = "SELECT * FROM products WHERE is_active = 1 ORDER BY product_id DESC LIMIT 8";
$result = $conn->query($sql);

$new_products_data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $new_products_data[] = $row;
    }
}

$body_class = 'page-index';
require_once 'includes/header.php';
?>

<!-- Hero Slider -->
<div class="hero-slider">
    <div class="swiper heroSwiper">
        <div class="swiper-wrapper">
            <div class="swiper-slide hero-slide"
                 onclick="location.href='/homedeco-shop/products.php?category=1'"
                 style="cursor:pointer;">
                <img src="https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?w=1920&h=600&fit=crop&crop=center"
                     alt="Modern Living Room">
                <div class="hero-overlay">
                    <h2>공간을 채우는 따뜻함</h2>
                    <p>당신의 거실을 COZY-DECO와 함께 특별하게 만들어보세요</p>
                </div>
            </div>

            <div class="swiper-slide hero-slide"
                 onclick="location.href='/homedeco-shop/products.php?category=2'"
                 style="cursor:pointer;">
                <img src="https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?w=1920&h=600&fit=crop&crop=center"
                     alt="Elegant Bedroom">
                <div class="hero-overlay">
                    <h2>가장 프라이빗한 휴식</h2>
                    <p>포근함이 가득한 침실 인테리어 제안</p>
                </div>
            </div>

            <div class="swiper-slide hero-slide"
                 onclick="location.href='/homedeco-shop/products.php?category=4'"
                 style="cursor:pointer;">
                <img src="https://images.unsplash.com/photo-1615529328331-f8917597711f?w=1920&h=600&fit=crop&crop=center"
                     alt="Lighting">
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

<?php if (!is_logged_in()): ?>
<section class="guest-welcome-banner">
    <h3>처음이신가요? 가입 없이 바로 쇼핑해보세요</h3>
    <p>로그인 없이도 장바구니 이용 및 비회원 주문이 가능합니다.</p>
    <div class="banner-actions">
        <a href="/homedeco-shop/products.php" class="btn btn-primary">상품 둘러보기</a>
        <a href="/homedeco-shop/login.php" class="btn btn-outline">로그인 / 회원가입</a>
    </div>
</section>
<?php endif; ?>

<section class="collection-section categories">
    <div class="section-title-wrapper" style="text-align:center;margin-bottom:40px;">
        <h2 class="section-title">Category</h2>
        <p class="section-subtitle">당신의 취향을 담은 특별한 공간 큐레이션</p>
    </div>

    <div class="category-tabs">
        <button class="tab-item active" data-category="new">
            <span class="tab-label">New Arrivals</span>
            <span class="tab-sub">신상품</span>
        </button>
        <button class="tab-item" data-category="1"><span class="tab-label">Living</span><span class="tab-sub">거실</span></button>
        <button class="tab-item" data-category="2"><span class="tab-label">Bedroom</span><span class="tab-sub">침실</span></button>
        <button class="tab-item" data-category="3"><span class="tab-label">Kitchen</span><span class="tab-sub">주방</span></button>
        <button class="tab-item" data-category="4"><span class="tab-label">Lighting</span><span class="tab-sub">조명</span></button>
        <button class="tab-item" data-category="5"><span class="tab-label">Objects</span><span class="tab-sub">소품</span></button>
    </div>

    <div id="product-display-area" class="product-grid-container">
        <div class="product-grid active" id="collection-grid">
            <?php if (!empty($new_products_data)): ?>
                <?php foreach ($new_products_data as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <a href="/homedeco-shop/product-detail.php?id=<?= $product['product_id']; ?>">
                                <img src="<?= htmlspecialchars($product['main_image']); ?>"
                                     alt="<?= htmlspecialchars($product['name']); ?>"
                                     onerror="handleImageError(this)">
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
            <?php else: ?>
                <div class="no-items">등록된 상품이 없습니다.</div>
            <?php endif; ?>
        </div>

        <div class="view-more-wrapper" style="text-align:center;margin-top:50px;">
            <a href="/homedeco-shop/products.php"
               id="active-category-link"
               class="btn btn-outline"
               style="min-width:220px;border-radius:50px;">
                전체 상품 보기 →
            </a>
        </div>
    </div>
</section>

</div>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
const heroSwiper = new Swiper('.heroSwiper', {
    loop:true,
    speed:1000,
    effect:'fade',
    autoplay:{ delay:5000, disableOnInteraction:false },
    pagination:{ el:'.swiper-pagination', clickable:true }
});

document.querySelectorAll('.tab-item').forEach(tab=>{
    tab.addEventListener('click',function(){
        document.querySelectorAll('.tab-item').forEach(t=>t.classList.remove('active'));
        this.classList.add('active');
        loadTabProducts(this.dataset.category);
    });
});

function loadTabProducts(category){
    const grid = document.getElementById('collection-grid');
    const viewAll = document.getElementById('active-category-link');

    grid.style.opacity='0';
    setTimeout(()=>{
        fetch(`/homedeco-shop/api/category_products.php?category=${category}&limit=8`)
        .then(r=>r.json())
        .then(d=>{
            if(d.success && d.products.length){
                let html='';
                const now = Math.floor(Date.now()/1000);
                const limit = 14*24*60*60;

                d.products.forEach(p=>{
                    const created = Math.floor(new Date(p.created_at).getTime()/1000);
                    if(category==='new' && (now-created)>limit) return;

                    html+=`
                    <div class="product-card">
                        <div class="product-image">
                            <a href="/homedeco-shop/product-detail.php?id=${p.product_id}">
                                <img src="${p.main_image}" alt="${p.name}">
                            </a>
                            ${category==='new' ? '<div class="product-badges"><span class="badge-new">NEW</span></div>' : ''}
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><a href="/homedeco-shop/product-detail.php?id=${p.product_id}">${p.name}</a></h3>
                            <div class="product-price"><span class="amount">${new Intl.NumberFormat('ko-KR').format(p.price)}</span>원</div>
                        </div>
                    </div>`;
                });

                grid.innerHTML = html || `<div class="no-items">상품이 없습니다.</div>`;
                viewAll.href = category==='new'
                    ? '/homedeco-shop/products.php?sort=newest'
                    : `/homedeco-shop/products.php?category=${category}`;
            } else {
                grid.innerHTML = '<div class="no-items">상품이 없습니다.</div>';
            }
            grid.style.opacity='1';
        });
    },300);
}
</script>

<?php require_once 'includes/footer.php'; ?>
