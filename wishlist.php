<?php
$page_title = '찜한 상품';
require_once 'includes/db.php';

if (!is_logged_in()) {
    redirect('login.php?redirect=wishlist.php');
}

$user_id = $_SESSION['user_id'];

// 찜한 상품 조회
$sql = "SELECT w.*, p.* 
        FROM wishlist w 
        LEFT JOIN products p ON w.product_id = p.product_id 
        WHERE w.user_id = $user_id 
        ORDER BY w.created_at DESC";
$wishlist_items = $conn->query($sql);

require_once 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-heart text-danger"></i> 찜한 상품</h1>
        <p>총 <strong><?php echo $wishlist_items->num_rows; ?></strong>개의 상품을 찜했습니다.</p>
    </div>

    <?php if ($wishlist_items->num_rows > 0): ?>
        <div class="wishlist-grid">
            <?php while ($item = $wishlist_items->fetch_assoc()): ?>
                <div class="wishlist-card">
                    <div class="product-image">
                        <a href="product-detail.php?id=<?php echo $item['product_id']; ?>" class="image-link">
                            <!-- 기본 배경 (이미지 없을 때 보임) -->
                            <div class="no-image-placeholder">
                                <i class="fas fa-camera"></i>
                            </div>
                            <!-- 상품 이미지 -->
                            <?php if (!empty($item['main_image'])): ?>
                                <img src="<?php echo htmlspecialchars($item['main_image']); ?>"
                                    alt="<?php echo htmlspecialchars($item['name']); ?>" onerror="this.style.display='none'">
                            <?php endif; ?>
                        </a>
                        <button class="btn-remove" onclick="removeFromWishlist(<?php echo $item['product_id']; ?>)"
                            title="찜 목록에서 삭제">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="product-info">
                        <h3 class="product-name">
                            <a href="product-detail.php?id=<?php echo $item['product_id']; ?>">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </a>
                        </h3>

                        <div class="product-price">
                            <?php echo number_format($item['price']); ?>원
                        </div>

                        <div class="product-stock">
                            <?php if ($item['stock'] > 0): ?>
                                <span class="in-stock"><i class="fas fa-check-circle"></i> 재고 있음</span>
                            <?php else: ?>
                                <span class="out-of-stock"><i class="fas fa-times-circle"></i> 품절</span>
                            <?php endif; ?>
                        </div>

                        <div class="product-actions">
                            <?php if ($item['stock'] > 0): ?>
                                <button class="btn btn-primary btn-block add-to-cart-btn"
                                    data-product-id="<?php echo $item['product_id']; ?>"
                                    onclick="addToCart(<?php echo $item['product_id']; ?>)">
                                    장바구니 담기
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-block" disabled>품절</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-wishlist">
            <div class="empty-icon">
                <i class="far fa-heart"></i>
            </div>
            <h3>찜한 상품이 없습니다</h3>
            <p>마음에 드는 상품을 찾아보세요!</p>
            <a href="products.php" class="btn btn-primary btn-large">상품 보러가기</a>
        </div>
    <?php endif; ?>
</div>

<style>
    /* 찜하기 페이지 스타일 */
    .wishlist-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 30px;
        margin: 30px 0;
    }

    .wishlist-card {
        background: white;
        border: 1px solid #eee;
        border-radius: 12px;
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
        position: relative;
    }

    .wishlist-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
    }

    .product-image {
        position: relative;
        padding-top: 100%;
        /* 1:1 비율 */
        background: #f9f9f9;
    }

    /* 이미지 링크 (전체 영역 클릭 가능) */
    .image-link {
        display: block;
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }

    /* 이미지 없음 플레이스홀더 */
    .no-image-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 40px;
        color: #ddd;
        background: #f5f5f5;
        position: absolute;
        top: 0;
        left: 0;
        z-index: 0;
    }

    .product-image img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: 1;
        /* 플레이스홀더 위에 표시 */
        transition: opacity 0.3s;
    }

    .btn-remove {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.9);
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        color: #999;
    }

    .btn-remove:hover {
        background: #ff4444;
        color: white;
        transform: scale(1.1);
    }

    .product-info {
        padding: 20px;
    }

    .product-name h3 {
        font-size: 16px;
        margin-bottom: 8px;
        font-weight: 500;
        color: #333;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .product-name a {
        text-decoration: none;
        color: inherit;
    }

    .product-price {
        font-size: 18px;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 12px;
    }

    .product-stock {
        font-size: 13px;
        margin-bottom: 15px;
    }

    .in-stock {
        color: #008f35;
    }

    .out-of-stock {
        color: #ff4444;
    }

    .btn-block {
        width: 100%;
        padding: 10px;
        border-radius: 6px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
    }

    .btn-primary {
        background: #1a1a1a;
        color: white;
    }

    .btn-primary:hover {
        background: #333;
    }

    .btn-secondary {
        background: #eee;
        color: #999;
        cursor: not-allowed;
    }

    .empty-wishlist {
        text-align: center;
        padding: 80px 0;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
    }

    .empty-icon i {
        font-size: 60px;
        color: #ddd;
        margin-bottom: 20px;
    }

    .empty-wishlist h3 {
        font-size: 24px;
        color: #333;
        margin-bottom: 10px;
    }

    .empty-wishlist p {
        color: #666;
        margin-bottom: 30px;
    }

    .text-danger {
        color: #ff4444;
    }
</style>

<script>
    // 찜 목록 삭제
    function removeFromWishlist(productId) {
        if (!confirm('찜 목록에서 삭제하시겠습니까?')) return;

        fetch('/homedeco-shop/api/wishlist-toggle.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('삭제되었습니다.');
                    location.reload();
                } else {
                    alert(data.message || '오류가 발생했습니다.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('서버 통신 오류가 발생했습니다.');
            });
    }

    // 장바구니 담기
    function addToCart(productId) {
        fetch('/homedeco-shop/api/cart-add.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `product_id=${productId}&quantity=1`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (confirm('장바구니에 담겼습니다. 장바구니로 이동하시겠습니까?')) {
                        location.href = 'cart.php';
                    }
                } else {
                    alert(data.message || '오류가 발생했습니다.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('서버 통신 오류가 발생했습니다.');
            });
    }
</script>