<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>HomeDeco Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link rel="stylesheet" href="/homedeco-shop/css/style.css?v=<?php echo time(); ?>">
    <script>
        // 이미지 로드 실패 시 품절 처리하는 공통 함수
        function handleImageError(img) {
            // 1. 이미지 대체 (무한 루프 방지)
            img.onerror = null;
            img.src = 'https://placehold.co/600x400/333/FFF.png?text=Sold+Out'; // 명확한 품절 이미지

            // 0. 상품 ID 추출 및 서버에 품절 처리 요청 (재고 0으로 변경)
            let productId = null;

            // Case A: 상품 목록 카드
            const cardLink = img.closest('.product-card')?.querySelector('a');
            if (cardLink && cardLink.href.includes('?')) {
                const urlParams = new URLSearchParams(cardLink.href.split('?')[1]);
                productId = urlParams.get('id');
            }

            // Case B: 상세 페이지
            if (!productId && document.querySelector('.product-detail')) {
                const urlParams = new URLSearchParams(window.location.search);
                productId = urlParams.get('id');
            }

            // API 호출: 재고 0으로 업데이트
            if (productId) {
                fetch('api/set_stock_zero.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId })
                }).then(res => res.json()).then(data => {
                    console.log('Stock updated to 0 for missing image:', productId, data);
                }).catch(err => console.error('Error updating stock', err));
            }

            // 2. 상세 페이지 처리
            const detailContainer = img.closest('.product-detail');
            if (detailContainer) {
                // 재고 상태 텍스트 변경
                const stockEl = document.querySelector('.product-stock');
                if (stockEl) {
                    stockEl.innerHTML = '<i class="fas fa-times-circle" style="color: var(--danger-color);"></i> <span>품절 (이미지 누락)</span>';
                }

                // 버튼 비활성화 (장바구니, 바로구매)
                const btns = document.querySelectorAll('.product-actions button:not(.wishlist-btn-detail)');
                btns.forEach(btn => {
                    btn.disabled = true;
                    btn.className = 'btn btn-secondary btn-large btn-block'; // 회색 스타일 적용
                    btn.innerHTML = '품절';
                    btn.style.cursor = 'not-allowed';
                    btn.onclick = null; // 이벤트 제거
                });
                return;
            }

            // 3. 상품 목록(카드) 처리
            const card = img.closest('.product-card');
            if (card) {
                card.classList.add('sold-out');

                // 오버레이가 없으면 추가
                const link = card.querySelector('.product-image a');
                if (link && !card.querySelector('.sold-out-overlay')) {
                    const overlay = document.createElement('div');
                    overlay.className = 'sold-out-overlay';
                    overlay.innerHTML = '<span>품절</span>';
                    link.appendChild(overlay);
                }

                // 링크 차단
                const links = card.querySelectorAll('a');
                links.forEach(l => {
                    l.href = 'javascript:void(0)';
                    l.onclick = (e) => { e.preventDefault(); alert('품절된 상품입니다 (이미지 누락).'); return false; };
                });

                // 장바구니 버튼 비활성화
                const btn = card.querySelector('.add-to-cart-btn');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-times-circle"></i> 품절';
                    btn.className = 'btn btn-secondary btn-block';
                    btn.style.backgroundColor = '#ccc';
                    btn.style.borderColor = '#ccc';
                    btn.style.cursor = 'not-allowed';
                }
            }
        }
    </script>
</head>

<body class="<?php echo isset($body_class) ? $body_class : ''; ?>">
    <header class="site-header">
        <div class="container header-container">
            <div class="logo"><a href="/homedeco-shop/index.php">COZY-DECO</a></div>
            <nav class="main-nav" id="mainNav">
                <ul>
                    <li><a href="/homedeco-shop/index.php">홈</a></li>
                    <li><a href="/homedeco-shop/products.php">상품</a></li>
                    <li><a href="/homedeco-shop/ai-recommend.php">AI 추천</a></li>
                    <li><a href="/homedeco-shop/about.php">브랜드 소개</a></li>
                    <li><a
                            href="<?php echo is_admin() ? '/homedeco-shop/admin/index.php' : '/homedeco-shop/contact.php'; ?>">문의</a>
                    </li>
                </ul>
            </nav>
            <div class="header-icons">
                <form class="search-form" action="/homedeco-shop/products.php" method="get">
                    <input type="text" name="search" placeholder="상품 검색..."
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" required>
                </form>
                <a href="/homedeco-shop/wishlist.php" class="emoji-icon" title="위시리스트"
                    style="text-decoration: none; color: #444; margin: 0 8px;"><i class="fa-regular fa-heart"
                        style="font-size: 18px;"></i></a>
                <a href="/homedeco-shop/cart.php" class="emoji-icon" title="장바구니"
                    style="text-decoration: none; color: #444; margin: 0 8px;"><i class="fa-solid fa-cart-shopping"
                        style="font-size: 18px;"></i></a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/homedeco-shop/mypage.php" class="user-name-link" title="마이페이지" style="margin-left: 10px;">
                        <span class="user-greeting"
                            style="font-size: 14px; color: #333;"><strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong>님</span>
                    </a>
                <?php else: ?>
                    <a href="/homedeco-shop/login.php" class="emoji-icon" title="로그인"
                        style="text-decoration: none; color: #444; margin: 0 8px;"><i class="fa-regular fa-user"
                            style="font-size: 18px;"></i></a>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/homedeco-shop/logout.php" class="emoji-icon" title="로그아웃"
                        style="text-decoration: none; color: #444; margin: 0 8px;"><i
                            class="fa-solid fa-arrow-right-from-bracket" style="font-size: 18px;"></i></a>
                <?php endif; ?>
            </div>
            <button class="hamburger-btn" id="hamburgerBtn"
                style="font-size: 24px; background:none; border:none; cursor:pointer;"><i
                    class="fas fa-bars"></i></button>
        </div>
    </header>