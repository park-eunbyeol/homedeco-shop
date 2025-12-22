<?php
/**
 * 공통 푸터
 * COZY-DECO Shopping Mall
 */
?>

<footer class="site-footer">
    <div class="container footer-container">
        <div class="footer-top">
            <div class="footer-brand">
                <h3 class="footer-logo">COZY-DECO</h3>
                <p class="footer-desc">
                    나만의 공간을 완성하는 홈데코 쇼핑몰<br>
                    감각적인 인테리어를 더 쉽게.
                </p>
            </div>

            <div class="footer-links">
                <h4>쇼핑</h4>
                <ul>
                    <li><a href="/products.php">전체 상품</a></li>
                    <li><a href="/products.php?sort=newest">신상품</a></li>
                    <li><a href="/ai-recommend.php">AI 추천</a></li>
                    <li><a href="/wishlist.php">위시리스트</a></li>
                </ul>
            </div>

            <div class="footer-links">
                <h4>고객지원</h4>
                <ul>
                    <li><a href="/contact.php">문의하기</a></li>
                    <li><a href="/about.php">회사소개</a></li>
                    <li><a href="#">이용약관</a></li>
                    <li><a href="#">개인정보처리방침</a></li>
                </ul>
            </div>

            <div class="footer-contact">
                <h4>고객센터</h4>
                <p class="tel">1588-0000</p>
                <p>평일 09:00 ~ 18:00</p>
                <p>점심 12:00 ~ 13:00</p>
                <div class="footer-sns">
                    <a href="#" aria-label="Instagram">📷</a>
                    <a href="#" aria-label="Facebook">📘</a>
                    <a href="#" aria-label="YouTube">▶️</a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>© <?= date('Y') ?> COZY-DECO. All rights reserved.</p>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <p style="margin-top: 10px;">
                    <a href="/homedeco-shop/admin/index.php"
                        style="color: #667eea; text-decoration: none; font-weight: 600;">
                        <i class="fas fa-user-shield"></i> Admin
                    </a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</footer>