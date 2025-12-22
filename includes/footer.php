<?php
/* ------------------------------------
   로그인/회원가입 페이지는 푸터 숨김
------------------------------------ */
$current_page = basename($_SERVER['PHP_SELF']);
$auth_pages = ['login.php', 'register.php', 'contact.php', 'contact_write.php', 'contact_view.php'];
$hide_footer = in_array($current_page, $auth_pages);
?>

<?php if (!$hide_footer): ?>
    <footer class="site-footer">
        <div class="footer-wrapper">

            <div class="footer-top">
                <!-- 로고 (왼쪽) -->
                <div class="footer-brand">
                    <h2>COZY-DECO</h2>
                </div>

                <!-- 메뉴 (오른쪽) -->
                <div class="footer-links">
                    <div class="footer-section">
                        <h4>SHOP</h4>
                        <ul>
                            <li><a href="products.php?category_id=1">거실 (Living)</a></li>
                            <li><a href="products.php?category_id=2">침실 (Bedroom)</a></li>
                            <li><a href="products.php?category_id=3">주방 (Kitchen)</a></li>
                            <li><a href="products.php?category_id=4">조명 (Lighting)</a></li>
                        </ul>
                    </div>

                    <div class="footer-section">
                        <h4>CUSTOMER</h4>
                        <ul>
                            <li><a href="contact.php">1:1 문의</a></li>
                            <li><a href="faq.php">자주 묻는 질문</a></li>
                            <li><a href="notice.php">공지사항</a></li>
                            <li><a href="qna.php">Q&A</a></li>
                        </ul>
                    </div>

                    <div class="footer-section">
                        <h4>COMPANY</h4>
                        <ul>
                            <li><a href="about.php">브랜드 소개</a></li>
                            <li><a href="#">이용약관</a></li>
                            <li><a href="#">개인정보처리방침</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <hr>

            <div class="footer-bottom">
                <div class="footer-copy">
                    © 2025 COZY-DECO. All Rights Reserved.
                </div>
                <div class="footer-admin">
                    <?php
                    $is_admin_check = (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
                    $admin_link = $is_admin_check ? '/homedeco-shop/admin/index.php' : '/homedeco-shop/admin/login.php';
                    ?>
                    <a href="<?php echo $admin_link; ?>" title="관리자 페이지">
                        <i class="fas fa-user-shield"></i> Admin
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <style>
        .site-footer {
            background: #f9f9f9;
            padding: 60px 0 30px;
            border-top: 1px solid #eaeaea;
            margin-top: 80px;
            font-family: 'Pretendard', sans-serif;
        }

        .footer-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-top {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: start;
            margin-bottom: 50px;
        }

        .footer-brand {
            margin-bottom: 0;
            text-align: left;
        }

        .footer-brand h2 {
            font-size: 28px;
            font-weight: 800;
            color: #2c3e50;
            margin: 0;
            letter-spacing: -1px;
        }

        .footer-links {
            grid-column: 2;
            display: flex;
            gap: 80px;
            text-align: left;
        }

        .footer-section h4 {
            font-size: 14px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 20px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-section ul li {
            margin-bottom: 12px;
        }

        .footer-section ul li a {
            color: #7f8c8d;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s ease;
        }

        .footer-section ul li a:hover {
            color: #2c3e50;
            text-decoration: underline;
        }

        hr {
            border: none;
            border-top: 1px solid #eaeaea;
            margin-bottom: 25px;
        }

        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-copy {
            color: #95a5a6;
            font-size: 13px;
        }

        .footer-admin a {
            color: #b0c4de;
            font-size: 12px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: color 0.2s;
        }

        .footer-admin a:hover {
            color: #667eea;
        }

        /* 모바일 */
        @media (max-width: 768px) {

            /* 모바일에서 푸터 숨김 */
            .site-footer {
                display: none !important;
            }

            /* 하단 고정바 공간 확보 */
            body {
                padding-bottom: 70px;
            }

            /* 모바일 하단 이모지 바 (PC 영향 없음) */
            .mobile-emoji-bar {
                display: flex !important;
            }
        }

        /* 기본적으로 숨김 (PC) */
        .mobile-emoji-bar {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 60px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-top: 1px solid #eee;
            justify-content: space-around;
            align-items: center;
            z-index: 9999;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.05);
        }

        .mobile-emoji-bar a {
            font-size: 24px;
            text-decoration: none;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: transform 0.2s;
        }

        .mobile-emoji-bar a:active {
            transform: scale(0.9);
        }
    </style>

    <!-- 모바일 하단 이모지 바 -->
    <div class="mobile-emoji-bar">
        <a href="index.php" title="홈"><i class="fas fa-home"></i></a>
        <a href="#" id="mobileSearchBtn" title="검색"><i class="fas fa-search"></i></a>
        <a href="wishlist.php" title="위시리스트"><i class="far fa-heart"></i></a>
        <a href="cart.php" title="장바구니"><i class="fas fa-shopping-cart"></i></a>
        <a href="mypage.php" title="마이페이지"><i class="far fa-user"></i></a>
    </div>

    <!-- 검색 오버레이 -->
    <div id="searchOverlay" class="search-overlay">
        <div class="search-overlay-content">
            <form action="products.php" method="get">
                <input type="text" name="search" placeholder="검색어를 입력하세요..." required>
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
            <button class="close-search-btn" id="closeSearchBtn">&times;</button>
        </div>
    </div>

<?php endif; ?>

<style>
    /* 검색 오버레이 스타일 */
    .search-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.98);
        z-index: 20000;
        justify-content: center;
        align-items: center;
        backdrop-filter: blur(10px);
    }

    .search-overlay.active {
        display: flex;
        animation: fadeIn 0.3s ease;
    }

    .search-overlay-content {
        width: 90%;
        max-width: 500px;
        position: relative;
    }

    .search-overlay-content form {
        display: flex;
        border-bottom: 2px solid #333;
        padding-bottom: 5px;
    }

    .search-overlay-content input {
        flex: 1;
        border: none;
        background: none;
        font-size: 24px;
        padding: 10px;
        outline: none;
        font-family: 'Pretendard', sans-serif;
    }

    .search-overlay-content button {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        padding: 0 10px;
        color: #333;
    }

    .close-search-btn {
        position: absolute;
        top: -80px;
        right: 0;
        font-size: 40px;
        background: none;
        border: none;
        cursor: pointer;
        color: #333;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 전역 햄버거 메뉴 토글
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const mainNav = document.getElementById('mainNav');

        if (hamburgerBtn && mainNav) {
            hamburgerBtn.addEventListener('click', () => {
                mainNav.classList.toggle('active');
            });
        }

        // 검색 오버레이 토글
        const mobileSearchBtn = document.getElementById('mobileSearchBtn');
        const searchOverlay = document.getElementById('searchOverlay');
        const closeSearchBtn = document.getElementById('closeSearchBtn');

        if (mobileSearchBtn && searchOverlay) {
            mobileSearchBtn.addEventListener('click', (e) => {
                e.preventDefault();
                searchOverlay.classList.add('active');
                setTimeout(() => {
                    searchOverlay.querySelector('input').focus();
                }, 100);
            });
        }

        if (closeSearchBtn && searchOverlay) {
            closeSearchBtn.addEventListener('click', () => {
                searchOverlay.classList.remove('active');
            });
        }

        // 오버레이 바깥 클릭 시 닫기
        if (searchOverlay) {
            searchOverlay.addEventListener('click', (e) => {
                if (e.target === searchOverlay) {
                    searchOverlay.classList.remove('active');
                }
            });
        }

        // ESC 키로 닫기
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && searchOverlay && searchOverlay.classList.contains('active')) {
                searchOverlay.classList.remove('active');
            }
        });
    });
</script>