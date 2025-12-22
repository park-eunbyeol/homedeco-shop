<?php
$page_title = '검색 결과';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/naver_api.php';

$is_logged_in = is_logged_in();

// 검색어 가져오기
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$products = [];

if (!empty($search_query)) {
    // 네이버 쇼핑 API로 검색
    $products = search_naver_products($search_query, 20);
}
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($search_query) ?> 검색 결과 - COZY-DECO</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <header class="site-header">
        <div class="container header-container">
            <div class="logo"><a href="index.php">COZY-DECO</a></div>
            <button class="hamburger-btn" id="hamburgerBtn">☰</button>
            <nav class="main-nav" id="mainNav">
                <ul>
                    <li><a href="index.php">홈</a></li>
                    <li><a href="products.php">상품</a></li>
                    <li><a href="ai-recommend.php">AI 추천</a></li>
                    <li><a href="about.php">소개</a></li>
                    <li><a href="contact.php">문의</a></li>
                </ul>
            </nav>
            <div class="header-icons">
                <form class="search-form" action="search.php" method="get">
                    <input type="text" name="q" placeholder="상품 검색..." value="<?= htmlspecialchars($search_query) ?>"
                        required>
                </form>
                <a href="wishlist.php" class="emoji-icon">❤️</a>
                <a href="cart.php" class="emoji-icon">🛒</a>
                <a href="<?= $is_logged_in ? 'mypage.php' : 'login.php' ?>" class="emoji-icon">👤</a>
                <?php if ($is_logged_in): ?>
                    <a href="logout.php" class="emoji-icon" title="로그아웃"><i class="fas fa-sign-out-alt"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container" style="margin-top: 40px; margin-bottom: 60px;">
        <div class="search-results-header" style="margin-bottom: 30px;">
            <h1 style="font-size: 28px; color: var(--primary-color); margin-bottom: 10px;">
                "<?= htmlspecialchars($search_query) ?>" 검색 결과
            </h1>
            <p style="color: #666; font-size: 16px;">
                네이버 쇼핑에서 <strong><?= count($products) ?>개</strong>의 상품을 찾았습니다.
            </p>
        </div>

        <?php if (!empty($products)): ?>
            <div class="product-grid"
                style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 30px;">
                <?php foreach ($products as $product): ?>
                    <div class="product-card"
                        style="background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; overflow: hidden; transition: all 0.3s;">
                        <div class="product-image"
                            style="position: relative; width: 100%; padding-top: 100%; overflow: hidden;">
                            <img src="<?= htmlspecialchars($product['main_image']) ?>"
                                alt="<?= htmlspecialchars($product['name']) ?>"
                                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;"
                                onerror="this.src='images/placeholder.jpg'">
                            <?php if (!empty($product['brand'])): ?>
                                <div
                                    style="position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,0.7); color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px;">
                                    <?= htmlspecialchars($product['brand']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info" style="padding: 15px;">
                            <h3 class="product-name"
                                style="font-size: 15px; font-weight: 600; color: #2c3e50; margin-bottom: 8px; line-height: 1.4; height: 42px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                <?= htmlspecialchars($product['name']) ?>
                            </h3>
                            <div class="product-price"
                                style="font-size: 18px; font-weight: 700; color: #e74c3c; margin-bottom: 10px;">
                                <?= number_format($product['price']) ?>원
                            </div>
                            <?php if (!empty($product['link'])): ?>
                                <a href="<?= htmlspecialchars($product['link']) ?>" target="_blank"
                                    class="btn btn-primary btn-block"
                                    style="display: block; text-align: center; padding: 10px; background: var(--primary-color); color: white; text-decoration: none; border-radius: 6px; font-size: 14px;">
                                    <i class="fas fa-shopping-cart"></i> 네이버에서 보기
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 80px 20px;">
                <i class="fas fa-search" style="font-size: 64px; color: #ddd; margin-bottom: 20px;"></i>
                <h3 style="font-size: 24px; margin-bottom: 10px; color: var(--primary-color);">검색 결과가 없습니다</h3>
                <p style="color: #666; margin-bottom: 30px;">다른 검색어를 시도해보세요.</p>
                <a href="index.php" class="btn btn-primary">홈으로 돌아가기</a>
            </div>
        <?php endif; ?>
    </div>

    <?php require_once 'includes/footer.php'; ?>

    <style>
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)) !important;
                gap: 15px !important;
            }
        }
    </style>
</body>

</html>