<?php
$page_title = '마이페이지';
require_once 'includes/db.php';

if (!is_logged_in()) {
    redirect('login.php?redirect=mypage.php');
}

$user_id = $_SESSION['user_id'];

// 사용자 정보
$user_sql = "SELECT * FROM users WHERE user_id = $user_id";
$user_result = $conn->query($user_sql);
$user = $user_result->fetch_assoc();

// 주문 통계
$order_stats_sql = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders
    FROM orders WHERE user_id = $user_id";
$stats_result = $conn->query($order_stats_sql);
$stats = $stats_result->fetch_assoc();

// 최근 주문 내역
$orders_sql = "SELECT o.*, 
    (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count,
    (SELECT p.name FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = o.order_id LIMIT 1) as first_product_name
    FROM orders o 
    WHERE o.user_id = $user_id 
    ORDER BY o.created_at DESC 
    LIMIT 5";
$orders = $conn->query($orders_sql);

// 내가 작성한 리뷰 (내 리뷰만 조회)
$reviews_sql = "SELECT r.*, p.name as product_name, p.main_image, p.product_id 
    FROM reviews r 
    LEFT JOIN products p ON r.product_id = p.product_id 
    WHERE r.user_id = $user_id 
    ORDER BY r.created_at DESC";
$reviews = $conn->query($reviews_sql);

// 찜한 상품
$wishlist_sql = "SELECT w.*, p.* 
    FROM wishlist w 
    LEFT JOIN products p ON w.product_id = p.product_id 
    WHERE w.user_id = $user_id 
    ORDER BY w.created_at DESC";
$wishlist = $conn->query($wishlist_sql);

// 장바구니 목록
$cart_sql = "SELECT c.cart_id, c.quantity, p.* 
    FROM cart_items c 
    JOIN products p ON c.product_id = p.product_id 
    WHERE c.user_id = $user_id";
$cart_items = $conn->query($cart_sql);

require_once 'includes/header.php';
?>

<div class="container">
    <div class="mypage-layout">
        <!-- 사이드바 -->
        <aside class="mypage-sidebar">
            <div class="user-profile">
                <div class="user-avatar"><?php echo mb_substr($user['name'], 0, 1); ?></div>
                <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
            </div>

            <nav class="mypage-nav">
                <a href="#orders" class="nav-link active" onclick="showSection('orders')">
                    <i class="fas fa-shopping-bag"></i> 주문내역
                </a>
                <a href="#profile" class="nav-link" onclick="showSection('profile')">
                    <i class="fas fa-user"></i> 회원정보
                </a>
                <a href="#reviews" class="nav-link" onclick="showSection('reviews')">
                    <i class="fas fa-star"></i> 내 리뷰
                </a>
                <a href="#coupons" class="nav-link" onclick="showSection('coupons')">
                    <i class="fas fa-ticket-alt"></i> 내 쿠폰
                </a>
                <a href="#wishlist" class="nav-link" onclick="showSection('wishlist')">
                    <i class="fas fa-heart"></i> 찜한 상품
                </a>
                <a href="#cart" class="nav-link" onclick="showSection('cart')">
                    <i class="fas fa-shopping-cart"></i> 장바구니
                </a>
                <a href="logout.php" class="nav-link mobile-only-logout" style="color: #e74c3c;">
                    <i class="fas fa-sign-out-alt"></i> 로그아웃
                </a>
            </nav>
        </aside>

        <!-- 메인 콘텐츠 -->
        <div class="mypage-content">
            <!-- 주문 통계 -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #667eea;">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-info">
                        <p>전체 주문</p>
                        <h3><?php echo $stats['total_orders']; ?>건</h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #f093fb;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <p>진행중 주문</p>
                        <h3><?php echo $stats['pending_orders']; ?>건</h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #4facfe;">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="stat-info">
                        <p>배송중</p>
                        <h3><?php echo $stats['shipped_orders']; ?>건</h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #43e97b;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <p>배송완료</p>
                        <h3><?php echo $stats['delivered_orders']; ?>건</h3>
                    </div>
                </div>
            </div>

            <!-- 주문내역 섹션 -->
            <section id="orders" class="content-section active">
                <div class="section-header">
                    <h2>주문내역</h2>
                </div>

                <?php if ($orders->num_rows > 0): ?>
                    <div class="orders-list">
                        <?php while ($order = $orders->fetch_assoc()): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div>
                                        <span class="order-number">주문번호:
                                            #<?php echo str_pad($order['order_id'], 8, '0', STR_PAD_LEFT); ?></span>
                                        <span class="order-date"><?php echo format_date($order['created_at']); ?></span>
                                    </div>
                                    <span class="order-status status-<?php echo $order['status']; ?>">
                                        <?php
                                        $status_text = [
                                            'pending' => '결제대기',
                                            'paid' => '결제완료',
                                            'shipped' => '배송중',
                                            'delivered' => '배송완료',
                                            'cancelled' => '취소됨'
                                        ];
                                        echo $status_text[$order['status']] ?? $order['status'];
                                        ?>
                                    </span>
                                </div>
                                <div class="order-body">
                                    <p style="font-weight: bold; font-size: 16px; margin-bottom: 5px; color: #333;">
                                        <?php
                                        echo htmlspecialchars($order['first_product_name']);
                                        if ($order['item_count'] > 1) {
                                            echo ' 외 ' . ($order['item_count'] - 1) . '건';
                                        }
                                        ?>
                                    </p>
                                    <p class="order-total"><?php echo format_price($order['total_amount']); ?></p>
                                </div>
                                <div class="order-actions">
                                    <a href="order-detail.php?id=<?php echo $order['order_id']; ?>"
                                        class="btn btn-outline">상세보기</a>
                                    <?php if ($order['status'] == 'shipped' || $order['status'] == 'delivered'): ?>
                                        <button class="btn btn-outline" style="border-color: #667eea; color: #667eea;"
                                            onclick="openTracking('<?php echo $order['order_id']; ?>')">배송조회</button>
                                    <?php endif; ?>
                                    <?php if ($order['status'] == 'delivered'): ?>
                                        <button class="btn btn-primary">리뷰작성</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-section">
                        <i class="fas fa-shopping-bag"></i>
                        <p>주문 내역이 없습니다</p>
                        <a href="products.php" class="btn btn-primary">쇼핑하러 가기</a>
                    </div>
                <?php endif; ?>
            </section>

            <!-- 회원정보 섹션 -->
            <section id="profile" class="content-section">
                <div class="section-header">
                    <h2>회원정보</h2>
                </div>

                <form method="POST" action="api/update-profile.php" class="profile-form">
                    <div class="form-group">
                        <label>이메일</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>"
                            disabled>
                        <small class="form-text">이메일은 변경할 수 없습니다</small>
                    </div>

                    <div class="form-group">
                        <label>이름</label>
                        <input type="text" name="name" class="form-control"
                            value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>연락처</label>
                        <input type="tel" name="phone" class="form-control"
                            value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>

                    <div class="form-group">
                        <label>주소</label>
                        <textarea name="address"
                            class="form-control"><?php echo htmlspecialchars($user['address']); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">정보 수정</button>
                        <button type="button" class="btn btn-outline" onclick="showPasswordChange()">비밀번호 변경</button>
                    </div>
                </form>

                <div id="passwordChange" style="display: none; margin-top: 30px;">
                    <h3>비밀번호 변경</h3>
                    <form method="POST" action="api/change-password.php">
                        <div class="form-group">
                            <label>현재 비밀번호</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>새 비밀번호</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>새 비밀번호 확인</label>
                            <input type="password" name="new_password_confirm" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">비밀번호 변경</button>
                    </form>
                </div>
            </section>

            <!-- 리뷰 섹션 -->
            <section id="reviews" class="content-section">
                <div class="section-header">
                    <h2>내 리뷰</h2>
                </div>

                <?php if ($reviews->num_rows > 0): ?>
                    <div class="my-reviews-list">
                        <?php while ($review = $reviews->fetch_assoc()): ?>
                            <div class="my-review-card" id="review-<?php echo $review['review_id']; ?>">
                                <div class="review-product">
                                    <a href="product-detail.php?id=<?php echo $review['product_id']; ?>"
                                        style="display: flex; gap: 15px; text-decoration: none; color: inherit; width: 100%;">
                                        <img src="<?php echo htmlspecialchars($review['main_image']); ?>"
                                            alt="<?php echo htmlspecialchars($review['product_name']); ?>"
                                            onerror="this.src='https://placehold.co/80x80?text=No+Image'">
                                        <div>
                                            <h4><?php echo htmlspecialchars($review['product_name']); ?></h4>
                                            <p class="review-date"><?php echo format_date($review['created_at']); ?></p>
                                        </div>
                                    </a>
                                </div>
                                <div class="review-content" style="position: relative;">
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++) {
                                            echo ($i <= $review['rating']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                        } ?>
                                    </div>
                                    <div class="review-meta-right" style="position: absolute; right: 0; top: 0;">
                                        <button class="btn-delete-review"
                                            onclick="deleteReview(<?php echo $review['review_id']; ?>)" title="리뷰 삭제">
                                            <i class="fas fa-trash"></i> 삭제
                                        </button>
                                    </div>
                                    <h4><?php echo htmlspecialchars($review['title']); ?></h4>
                                    <p><?php echo nl2br(htmlspecialchars($review['content'])); ?></p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-section">
                        <i class="fas fa-star"></i>
                        <p>작성한 리뷰가 없습니다</p>
                    </div>
                <?php endif; ?>
            </section>

            <!-- 쿠폰 섹션 (NEW) -->
            <section id="coupons" class="content-section">
                <div class="section-header">
                    <h2>내 쿠폰함</h2>
                </div>

                <div class="coupon-list-grid">
                    <!-- 샘플 쿠폰 데이터 (DB 연동 시 대체) -->
                    <div class="my-coupon-card">
                        <button class="btn-cancel-coupon" onclick="cancelCoupon(this)">&times;</button>
                        <div class="c-left" style="background: #3498db;">
                            <span style="font-size: 18px; font-weight: bold;">10%</span>
                            <span style="font-size: 12px;">OFF</span>
                        </div>
                        <div class="c-right">
                            <h4>신규가입 웰컴 쿠폰</h4>
                            <p>전 상품 사용 가능 (최대 1만원)</p>
                            <span class="expiry">~ 2024.12.31 까지</span>
                        </div>
                    </div>

                    <div class="my-coupon-card">
                        <button class="btn-cancel-coupon" onclick="cancelCoupon(this)">&times;</button>
                        <div class="c-left" style="background: #2ecc71;">
                            <span style="font-size: 18px; font-weight: bold;">Free</span>
                            <span style="font-size: 12px;">Ship</span>
                        </div>
                        <div class="c-right">
                            <h4>배송비 0원 쿠폰</h4>
                            <p>3만원 이상 구매 시 무료배송</p>
                            <span class="expiry">~ 2024.12.31 까지</span>
                        </div>
                    </div>
                </div>

                <div class="coupon-info">
                    <h5>💡 쿠폰 사용 안내</h5>
                    <ul style="padding-left: 20px; margin: 0; line-height: 1.6;">
                        <li>쿠폰은 주문 결제 시 적용할 수 있습니다.</li>
                        <li>유효기간이 만료된 쿠폰은 자동으로 소멸됩니다.</li>
                        <li>일부 특가 상품에는 쿠폰 적용이 제한될 수 있습니다.</li>
                    </ul>
                </div>
            </section>

            <!-- 찜한 상품 섹션 -->
            <section id="wishlist" class="content-section">
                <div class="section-header">
                    <h2>찜한 상품</h2>
                </div>

                <?php if ($wishlist->num_rows > 0): ?>
                    <div class="wishlist-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                        <?php while ($item = $wishlist->fetch_assoc()): ?>
                            <div class="wishlist-card">
                                <div class="product-image">
                                    <a href="product-detail.php?id=<?php echo $item['product_id']; ?>" class="image-link">
                                        <div class="no-image-placeholder">
                                            <i class="fas fa-camera fa-2x"></i>
                                        </div>
                                        <img src="<?php echo htmlspecialchars($item['main_image']); ?>"
                                            alt="<?php echo htmlspecialchars($item['name']); ?>"
                                            onerror="this.style.display='none'">
                                    </a>
                                </div>
                                <div class="product-info">
                                    <h3 class="product-name">
                                        <a
                                            href="product-detail.php?id=<?php echo $item['product_id']; ?>"><?php echo htmlspecialchars($item['name']); ?></a>

                                    </h3>
                                    <div class="product-price">
                                        <?php echo number_format($item['price']); ?>원
                                    </div>
                                    <div class="product-actions">
                                        <button class="btn btn-outline btn-block btn-sm"
                                            onclick="location.href='product-detail.php?id=<?php echo $item['product_id']; ?>'">상세보기</button>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-section">
                        <i class="far fa-heart"></i>
                        <p>찜한 상품이 없습니다</p>
                        <a href="products.php" class="btn btn-primary">쇼핑하러 가기</a>
                    </div>
                <?php endif; ?>
            </section>

            <!-- 장바구니 섹션 -->
            <section id="cart" class="content-section">
                <div class="section-header">
                    <h2>장바구니</h2>
                </div>

                <?php if ($cart_items->num_rows > 0): ?>
                    <div class="cart-list">
                        <?php
                        $total_price = 0;
                        while ($item = $cart_items->fetch_assoc()):
                            $item_total = $item['price'] * $item['quantity'];
                            $total_price += $item_total;
                            ?>
                            <div class="order-card cart-item-row" id="mypage-cart-item-<?php echo $item['cart_id']; ?>"
                                style="display: flex; gap: 20px; align-items: center;">
                                <div style="width: 80px; height: 80px; flex-shrink: 0;">
                                    <img src="<?php echo htmlspecialchars($item['main_image']); ?>"
                                        alt="<?php echo htmlspecialchars($item['name']); ?>"
                                        style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;"
                                        onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'80\' height=\'80\' viewBox=\'0 0 80 80\'%3E%3Crect width=\'80\' height=\'80\' fill=\'#f9f9f9\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' font-family=\'Arial\' font-size=\'12\' fill=\'#ccc\' text-anchor=\'middle\' dy=\'.3em\'%3ENo Img%3C/text%3E%3C/svg%3E'">
                                </div>
                                <div style="flex-grow: 1;">
                                    <h4 style="margin: 0 0 5px;"><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <p style="color: #666; margin: 0; font-size: 14px;">
                                        <span class="item-price"
                                            data-price="<?php echo $item['price']; ?>"><?php echo number_format($item['price']); ?></span>원
                                        x
                                        <span class="item-qty"
                                            data-qty="<?php echo $item['quantity']; ?>"><?php echo $item['quantity']; ?></span>개
                                    </p>
                                </div>
                                <div style="text-align: right;">
                                    <p
                                        style="font-weight: bold; font-size: 18px; color: var(--primary-color); margin: 0 0 5px;">
                                        <span class="item-total"><?php echo number_format($item_total); ?></span>원
                                    </p>
                                    <button class="btn btn-outline btn-sm"
                                        onclick="deleteCartItem(<?php echo $item['cart_id']; ?>)"
                                        style="padding: 2px 8px; font-size: 12px; border-color: #ddd; color: #999;">
                                        <i class="fas fa-trash"></i> 삭제
                                    </button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <div class="cart-summary"
                        style="margin-top: 30px; text-align: right; padding: 20px; background: #f9f9f9; border-radius: 12px;">
                        <span style="font-size: 16px; color: #666; margin-right: 20px;">총 주문 금액</span>
                        <span style="font-size: 24px; font-weight: bold; color: var(--primary-color);"
                            id="cart-total-price"><?php echo number_format($total_price); ?>원</span>
                        <div style="margin-top: 20px;">
                            <a href="cart.php" class="btn btn-primary">장바구니 전체보기 / 결제하기</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-section">
                        <i class="fas fa-shopping-cart"></i>
                        <p>장바구니가 비어있습니다</p>
                        <a href="products.php" class="btn btn-primary">쇼핑하러 가기</a>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>

<!-- 배송조회 모달 -->
<div id="trackingModal" class="modal-overlay" style="display: none;">
    <div class="modal-content tracking-modal">
        <div class="modal-header">
            <h3>배송 조회</h3>
            <button class="close-btn" onclick="closeTracking()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="tracking-info">
                <p><strong>송장번호:</strong> <span id="tracking-number">1234567890</span> (CJ대한통운)</p>
            </div>
            <div class="tracking-timeline" id="tracking-timeline">
                <!-- 타임라인 아이템들이 JS로 들어갑니다 -->
            </div>
        </div>
    </div>
</div>

<style>
    /* 모달 스타일 */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 2000;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .tracking-modal {
        background: white;
        width: 90%;
        max-width: 500px;
        border-radius: 12px;
        overflow: hidden;
        animation: slideUp 0.3s ease;
    }

    .tracking-modal .modal-header {
        padding: 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .tracking-modal .modal-header h3 {
        margin: 0;
        font-size: 18px;
        color: #333;
    }

    .tracking-modal .close-btn {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #999;
    }

    .tracking-modal .modal-body {
        padding: 20px;
        max-height: 60vh;
        overflow-y: auto;
    }

    .tracking-timeline {
        margin-top: 20px;
        padding-left: 10px;
        border-left: 2px solid #eee;
        margin-left: 10px;
    }

    .tracking-step {
        position: relative;
        padding-left: 20px;
        padding-bottom: 25px;
    }

    .tracking-step::before {
        content: '';
        position: absolute;
        left: -6px;
        top: 5px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #ddd;
        border: 2px solid #fff;
    }

    .tracking-step.active::before {
        background: var(--primary-color);
    }

    .tracking-step:last-child {
        padding-bottom: 0;
    }

    .tracking-step h4 {
        margin: 0 0 5px;
        font-size: 15px;
        color: #333;
    }

    .tracking-step p {
        margin: 0;
        font-size: 13px;
        color: #777;
    }

    .tracking-step .time {
        font-size: 12px;
        color: #999;
        margin-top: 2px;
    }
</style>

<script>
    function openTracking(orderId) {
        // 임시 송장번호 생성
        const trackingNum = 'CJ' + Math.floor(1000000000 + Math.random() * 9000000000);
        document.getElementById('tracking-number').innerText = trackingNum;

        // 가짜 배송 흐름 생성
        const steps = [
            { title: '배송완료', location: '고객님의 주소', status: 'delivered' },
            { title: '배송출발', location: '서울강남캠프', status: 'shipped' },
            { title: '터미널도착', location: '옥천HUB', status: 'hub' },
            { title: '집화처리', location: '경기광주', status: 'picked' },
            { title: '상품준비', location: '판매처', status: 'ordered' }
        ];

        const timeline = document.getElementById('tracking-timeline');
        let html = '';

        // 현재 날짜 기준
        let date = new Date();

        // 예시로 첫 번째가 가장 최신(완료)라고 가정하고 역순으로 날짜를 뺌
        steps.forEach((step, index) => {
            // 날짜 계산 (하루씩 빼기)
            let stepDate = new Date(date);
            stepDate.setDate(date.getDate() - index);

            let dateStr = stepDate.toLocaleDateString() + ' ' + stepDate.getHours() + ':00';

            let activeClass = index === 0 ? 'active' : ''; // 가장 위가 현재 상태

            html += `
                <div class="tracking-step ${activeClass}">
                    <h4>${step.title}</h4>
                    <p>${step.location}</p>
                    <div class="time">${dateStr}</div>
                </div>
            `;
        });

        timeline.innerHTML = html;
        document.getElementById('trackingModal').style.display = 'flex';
    }

    function closeTracking() {
        document.getElementById('trackingModal').style.display = 'none';
    }
</script>

<style>
    .my-review-card {
        position: relative;
    }

    .btn-delete-review {
        background: none;
        border: 1px solid #eee;
        color: #999;
        cursor: pointer;
        padding: 4px 12px;
        border-radius: 4px;
        transition: all 0.3s;
        font-size: 13px;
    }

    .btn-delete-review:hover {
        color: #e74c3c;
        border-color: #e74c3c;
        background: #fff5f5;
    }

    .mypage-layout {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 40px;
        margin: 40px 0;
    }

    .mypage-sidebar {
        position: sticky;
        top: 100px;
        height: fit-content;
    }

    .user-profile {
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 30px;
        text-align: center;
        margin-bottom: 20px;
    }

    .user-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        font-weight: 700;
        margin: 0 auto 15px;
    }

    .user-profile h3 {
        margin-bottom: 5px;
        color: var(--primary-color);
    }

    .user-profile p {
        font-size: 14px;
        color: #666;
    }

    .mypage-nav {
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
    }

    .nav-link {
        display: block;
        padding: 15px 20px;
        color: var(--text-color);
        text-decoration: none;
        border-bottom: 1px solid var(--border-color);
        transition: all 0.3s;
    }

    .nav-link:last-child {
        border-bottom: none;
    }

    .nav-link:hover {
        background: var(--light-gray);
        color: var(--secondary-color);
    }

    .nav-link.active {
        background: var(--secondary-color);
        color: white;
    }

    .nav-link i {
        margin-right: 10px;
        width: 20px;
    }

    .mobile-only-logout {
        display: none !important;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 40px;
    }

    .stat-card {
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 25px;
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 28px;
    }

    .stat-info p {
        color: #666;
        margin-bottom: 5px;
        font-size: 14px;
    }

    .stat-info h3 {
        font-size: 24px;
        color: var(--primary-color);
    }

    .content-section {
        display: none;
    }

    .content-section.active {
        display: block;
    }

    .section-header {
        margin-bottom: 30px;
    }

    .section-header h2 {
        font-size: 28px;
        color: var(--primary-color);
    }

    .order-card {
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 20px;
    }

    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--border-color);
    }

    .order-number {
        font-weight: 600;
        color: var(--primary-color);
        margin-right: 15px;
    }

    .order-date {
        color: #666;
        font-size: 14px;
    }

    .order-status {
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-paid {
        background: #d1ecf1;
        color: #0c5460;
    }

    .status-shipped {
        background: #cce5ff;
        color: #004085;
    }

    .status-delivered {
        background: #d4edda;
        color: #155724;
    }

    .status-cancelled {
        background: #f8d7da;
        color: #721c24;
    }

    .order-body {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
    }

    .order-total {
        font-size: 20px;
        font-weight: 700;
        color: var(--primary-color);
    }

    .order-actions {
        display: flex;
        gap: 10px;
    }

    .empty-section {
        text-align: center;
        padding: 80px 20px;
    }

    .empty-section i {
        font-size: 64px;
        color: #ddd;
        margin-bottom: 20px;
    }

    .profile-form {
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 30px;
    }

    .my-review-card {
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 20px;
    }

    .review-product {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--border-color);
    }

    .review-product img {
        width: 80px;
        height: 80px;
        border-radius: 8px;
        object-fit: cover;
    }

    .review-product h4 {
        margin-bottom: 5px;
        color: var(--primary-color);
    }

    .review-content .review-rating {
        color: #ffa500;
        margin-bottom: 10px;
    }

    @media (max-width: 1024px) {
        .mypage-layout {
            grid-template-columns: 1fr;
            gap: 30px;
        }

        .mypage-sidebar {
            position: static;
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
    }

    .wishlist-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }

    .wishlist-card {
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
    }

    .wishlist-card .product-image {
        position: relative;
        padding-top: 100%;
        background: #f9f9f9;
        /* aspect-ratio 이슈 방지 */
    }

    .wishlist-card .image-link {
        display: block;
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }

    .wishlist-card .no-image-placeholder {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: #f0f0f0;
        display: flex;
        justify-content: center;
        align-items: center;
        color: #ccc;
        z-index: 0;
    }

    .wishlist-card .product-image img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: 1;
    }

    .wishlist-card .product-info {
        padding: 20px 15px;
        /* 상하 20px, 좌우 15px */
        text-align: center;
    }

    .wishlist-card .product-name {
        margin: 0 0 12px;
        font-size: 15px;
        line-height: 1.4;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: #333;
    }

    .wishlist-card .product-name a {
        color: inherit;
        text-decoration: none;
    }

    .wishlist-card .product-price {
        font-weight: 600;
        color: #333;
        margin-bottom: 15px;
        font-size: 16px;
    }

    /* 버튼 스타일 */
    .wishlist-card .btn-outline {
        width: 100%;
        background: white;
        color: #666;
        border: 1px solid #ddd;
        padding: 8px 0;
        border-radius: 6px;
        font-size: 14px;
        transition: all 0.2s;
        cursor: pointer;
    }

    .wishlist-card .btn-outline:hover {
        border-color: var(--primary-color);
        color: var(--primary-color);
        background: #f9f9f9;
    }

    @media (max-width: 768px) {
        .wishlist-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            text-align: left;
            gap: 20px;
            padding: 20px;
        }

        .user-avatar {
            margin: 0;
            width: 60px;
            height: 60px;
            font-size: 24px;
        }

        .mypage-nav {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            background: transparent;
            border: none;
        }

        .nav-link {
            background: white;
            border: 1px solid var(--border-color) !important;
            border-radius: 10px;
            padding: 12px 5px;
            text-align: center;
            font-size: 12px;
        }

        .nav-link i {
            display: block;
            margin: 0 auto 5px;
            font-size: 18px;
            width: auto;
        }

        .nav-link.active {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .order-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }

        .order-actions {
            flex-wrap: wrap;
        }

        .order-actions .btn {
            flex: 1;
            min-width: calc(50% - 5px);
        }

        .cart-item-row {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 15px !important;
        }

        .cart-item-row>div:last-child {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            text-align: left !important;
        }
    }

    /* 주문 현황 카드 스타일 개선 */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 40px;
    }

    .stat-card {
        background: #fff;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        gap: 15px;
        transition: transform 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: #fff;
        flex-shrink: 0;
    }

    .stat-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-width: 0;
    }

    .stat-info p {
        margin: 0 0 5px;
        font-size: 13px;
        color: #777;
        white-space: nowrap;
    }

    .stat-info h3 {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
        color: #333;
        line-height: 1;
    }



    /* 쿠폰함 스타일 */
    .coupon-list-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 25px;
    }

    .my-coupon-card {
        border: 1px solid #eee;
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        background: #fff;
        position: relative;
        transition: all 0.3s ease;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
    }

    .btn-cancel-coupon {
        position: absolute;
        top: 8px;
        right: 8px;
        background: none;
        border: none;
        color: #ccc;
        font-size: 20px;
        cursor: pointer;
        z-index: 10;
        padding: 0 5px;
        transition: color 0.2s;
    }

    .btn-cancel-coupon:hover {
        color: #ff6b6b;
    }

    .c-left {
        color: white;
        padding: 15px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        width: 85px;
        flex-shrink: 0;
    }

    .c-right {
        padding: 15px 18px;
        flex: 1;
        min-width: 0;
    }

    .c-right h4 {
        margin: 0 0 5px;
        color: #333;
        font-size: 15px;
        font-weight: 600;
    }

    .c-right p {
        margin: 0 0 10px;
        font-size: 12px;
        color: #777;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .c-right .expiry {
        display: inline-block;
        padding: 3px 8px;
        background: #f5f5f5;
        border-radius: 4px;
        font-size: 10px;
        color: #888;
    }

    .coupon-info {
        margin-top: 30px;
        background: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        font-size: 13px;
        color: #666;
    }

    .coupon-info h5 {
        margin: 0 0 10px;
        color: #333;
    }

    @media (max-width: 576px) {
        .mypage-nav {
            grid-template-columns: repeat(2, 1fr);
        }

        .stat-card {
            padding: 15px;
            gap: 10px;
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            font-size: 20px;
        }

        .stat-info h3 {
            font-size: 18px;
        }

        .coupon-list-grid {
            grid-template-columns: 1fr;
        }

        .section-header h2 {
            font-size: 22px;
        }

        .wishlist-grid {
            grid-template-columns: 1fr;
        }
    }

    /* 모달 스타일 */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 2000;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .tracking-modal {
        background: white;
        width: 90%;
        max-width: 500px;
        border-radius: 12px;
        overflow: hidden;
        animation: slideUp 0.3s ease;
    }

    .tracking-modal .modal-header {
        padding: 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .tracking-modal .modal-header h3 {
        margin: 0;
        font-size: 18px;
        color: #333;
    }

    .tracking-modal .close-btn {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #999;
    }

    .tracking-modal .modal-body {
        padding: 20px;
        max-height: 60vh;
        overflow-y: auto;
    }

    .tracking-timeline {
        margin-top: 20px;
        padding-left: 10px;
        border-left: 2px solid #eee;
        margin-left: 10px;
    }

    .tracking-step {
        position: relative;
        padding-left: 20px;
        padding-bottom: 25px;
    }

    .tracking-step::before {
        content: '';
        position: absolute;
        left: -6px;
        top: 5px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #ddd;
        border: 2px solid #fff;
    }

    .tracking-step.active::before {
        background: var(--primary-color);
    }

    .tracking-step:last-child {
        padding-bottom: 0;
    }

    .tracking-step h4 {
        margin: 0 0 5px;
        font-size: 15px;
        color: #333;
    }

    .tracking-step p {
        margin: 0;
        font-size: 13px;
        color: #777;
    }

    .tracking-step .time {
        font-size: 12px;
        color: #999;
        margin-top: 2px;
    }
</style>

<!-- 배송조회 모달 HTML -->
<div id="trackingModal" class="modal-overlay" style="display: none;">
    <div class="modal-content tracking-modal">
        <div class="modal-header">
            <h3>배송 조회</h3>
            <button class="close-btn" onclick="closeTracking()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="tracking-info">
                <p><strong>송장번호:</strong> <span id="tracking-number">1234567890</span> (CJ대한통운)</p>
            </div>
            <div class="tracking-timeline" id="tracking-timeline">
                <!-- 타임라인 아이템들이 JS로 들어갑니다 -->
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // URL 해시가 있으면 해당 섹션 열기
        const hash = window.location.hash.substring(1);
        if (hash && document.getElementById(hash)) {
            showSection(hash);
        }

        // 쿠폰 로드
        loadMyCoupons();
    });

    function showSection(sectionId) {
        document.querySelectorAll('.content-section').forEach(section => {
            section.classList.remove('active');
        });
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });

        const targetSection = document.getElementById(sectionId);
        if (targetSection) {
            targetSection.classList.add('active');
            const navLink = document.querySelector(`.nav-link[href="#${sectionId}"]`);
            if (navLink) {
                navLink.classList.add('active');
            }
        }
    }

    function showPasswordChange() {
        const pc = document.getElementById('passwordChange');
        if (pc) pc.style.display = 'block';
    }

    function openTracking(orderId) {
        const trackingNum = 'CJ' + Math.floor(1000000000 + Math.random() * 9000000000);
        const tnElem = document.getElementById('tracking-number');
        if (tnElem) tnElem.innerText = trackingNum;

        const steps = [
            { title: '배송완료', location: '고객님의 주소' },
            { title: '배송출발', location: '서울강남캠프' },
            { title: '터미널도착', location: '옥천HUB' },
            { title: '집화처리', location: '경기광주' },
            { title: '상품준비', location: '판매처' }
        ];

        const timeline = document.getElementById('tracking-timeline');
        if (timeline) {
            let html = '';
            let date = new Date();
            steps.forEach((step, index) => {
                let stepDate = new Date(date);
                stepDate.setDate(date.getDate() - index);
                let dateStr = stepDate.toLocaleDateString() + ' ' + stepDate.getHours() + ':00';
                let activeClass = index === 0 ? 'active' : '';
                html += `
                    <div class="tracking-step ${activeClass}">
                        <h4>${step.title}</h4>
                        <p>${step.location}</p>
                        <div class="time">${dateStr}</div>
                    </div>
                `;
            });
            timeline.innerHTML = html;
        }
        const modal = document.getElementById('trackingModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeTracking() {
        const modal = document.getElementById('trackingModal');
        if (modal) modal.style.display = 'none';
    }

    function deleteCartItem(cartId) {
        if (!confirm('장바구니에서 삭제하시겠습니까?')) return;
        fetch('api/cart-update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cart_id: cartId, change: 0, action: 'delete' })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const itemRow = document.getElementById('mypage-cart-item-' + cartId);
                    if (itemRow) itemRow.remove();
                    updateTotal();
                    if (document.querySelectorAll('.cart-item-row').length === 0) location.reload();
                } else {
                    alert('삭제 실패: ' + (data.error || '알 수 없는 오류'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('오류가 발생했습니다.');
            });
    }

    function updateTotal() {
        let total = 0;
        document.querySelectorAll('.cart-item-row').forEach(row => {
            const priceElem = row.querySelector('.item-price');
            const qtyElem = row.querySelector('.item-qty');
            if (priceElem && qtyElem) {
                total += parseInt(priceElem.dataset.price) * parseInt(qtyElem.dataset.qty);
            }
        });
        const totalElem = document.getElementById('cart-total-price');
        if (totalElem) {
            totalElem.textContent = new Intl.NumberFormat('ko-KR').format(total) + '원';
        }
    }

    function loadMyCoupons() {
        const list = document.querySelector('.coupon-list-grid');
        if (!list) return;
        const myCoupons = JSON.parse(localStorage.getItem('homedeco_my_coupons') || '[]');
        if (myCoupons.length === 0) {
            list.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #999;"><i class="fas fa-ticket-alt" style="font-size: 40px; margin-bottom: 20px;"></i><p>보유한 쿠폰이 없습니다.</p></div>';
            return;
        }
        let html = '';
        myCoupons.forEach(coupon => {
            const bgStyle = coupon.badgeColor ? `background: ${coupon.badgeColor};` : 'background: #3498db;';
            html += `
                <div class="my-coupon-card" data-id="${coupon.id}">
                    <button class="btn-cancel-coupon" onclick="cancelCoupon(this)">&times;</button>
                    <div class="c-left" style="${bgStyle}">
                        <span style="font-size: 18px; font-weight: bold;">${coupon.price}</span>
                        <span style="font-size: 12px;">COUPON</span>
                    </div>
                    <div class="c-right">
                        <h4>${coupon.title}</h4>
                        <p>${coupon.condition}</p>
                        <span class="expiry">~ 2024.12.31 까지</span>
                    </div>
                </div>
            `;
        });
        list.innerHTML = html;
    }

    function cancelCoupon(btn) {
        if (!confirm('이 쿠폰을 삭제하시겠습니까? (복구할 수 없습니다)')) return;
        const card = btn.closest('.my-coupon-card');
        const couponId = card.dataset.id;
        card.style.opacity = '0';
        card.style.transform = 'scale(0.9)';
        setTimeout(() => {
            card.remove();
            let myCoupons = JSON.parse(localStorage.getItem('homedeco_my_coupons') || '[]');
            myCoupons = myCoupons.filter(c => String(c.id) !== String(couponId));
            localStorage.setItem('homedeco_my_coupons', JSON.stringify(myCoupons));
            if (myCoupons.length === 0) {
                const list = document.querySelector('.coupon-list-grid');
                if (list) list.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #999;"><i class="fas fa-ticket-alt" style="font-size: 40px; margin-bottom: 20px;"></i><p>보유한 쿠폰이 없습니다.</p></div>';
            }
        }, 300);
    }

    function deleteReview(reviewId) {
        if (!confirm('정말로 이 리뷰를 삭제하시겠습니까?')) return;
        fetch('/homedeco-shop/api/review-delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ review_id: reviewId })
        })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('리뷰가 삭제되었습니다.');
                    window.location.reload();
                } else {
                    alert(result.message || '리뷰 삭제 실패');
                }
            })
            .catch(error => {
                alert('서버 통신 오류가 발생했습니다.');
                console.error(error);
            });
    }
</script>