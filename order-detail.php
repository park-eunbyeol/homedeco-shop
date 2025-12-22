<?php
// order-detail.php
require_once 'includes/functions.php';
require_once 'includes/db.php';

$order_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$is_guest_view = isset($_GET['guest']) && $_GET['guest'] == 1;

if (!is_logged_in()) {
    if (!$is_guest_view || !isset($_SESSION['guest_order_view']) || $_SESSION['guest_order_view'] != $order_id) {
        redirect('login.php');
    }
}

$page_title = '주문 상세 내역';
require_once 'includes/header.php';

// 주문 정보 조회
if (is_logged_in()) {
    $user_id = $_SESSION['user_id'];
    $order_sql = "SELECT * FROM orders WHERE order_id = ? AND user_id = ?";
    $stmt = $conn->prepare($order_sql);
    $stmt->bind_param("ii", $order_id, $user_id);
} else {
    $order_sql = "SELECT * FROM orders WHERE order_id = ? AND user_id IS NULL";
    $stmt = $conn->prepare($order_sql);
    $stmt->bind_param("i", $order_id);
}
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo "<div class='container' style='padding:50px;text-align:center;'><h3>주문 정보를 찾을 수 없습니다.</h3></div>";
    require_once 'includes/footer.php';
    exit;
}

// 주문 상품 조회
$items_sql = "SELECT oi.*, p.name, p.main_image 
              FROM order_items oi 
              JOIN products p ON oi.product_id = p.product_id 
              WHERE oi.order_id = ?";
$stmt = $conn->prepare($items_sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result();
?>

<div class="container" style="max-width: 800px; margin: 40px auto; padding: 0 20px;">
    <div class="page-header" style="margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 15px;">
        <h1 style="font-size: 24px;">주문 상세 내역</h1>
        <p style="color: #666; margin-top: 5px;">
            주문번호: <strong>#<?= str_pad($order['order_id'], 8, '0', STR_PAD_LEFT) ?></strong>
            <span style="margin: 0 10px;">|</span>
            주문일자: <?= date('Y.m.d H:i', strtotime($order['created_at'])) ?>
        </p>
    </div>

    <!-- 주문 상품 목록 -->
    <section class="order-items">
        <h2 style="font-size: 18px; margin-bottom: 15px;">주문 상품</h2>
        <div class="items-list" style="border-top: 1px solid #eee;">
            <?php while ($item = $items->fetch_assoc()): ?>
                <div class="order-item"
                    style="display: flex; gap: 20px; padding: 20px 0; border-bottom: 1px solid #eee; align-items: center;">
                    <div class="item-img" style="width: 80px; height: 80px; flex-shrink: 0;">
                        <img src="<?= htmlspecialchars($item['main_image']) ?>" alt=""
                            style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px; border: 1px solid #eee;">
                    </div>
                    <div class="item-info" style="flex-grow: 1;">
                        <h3 style="font-size: 16px; margin: 0 0 5px;"><?= htmlspecialchars($item['name']) ?></h3>
                        <p style="color: #666; font-size: 14px; margin: 0;">
                            <?= number_format($item['price']) ?>원 / <?= $item['quantity'] ?>개
                        </p>
                    </div>
                    <div class="item-action">
                        <!-- 네이버 쇼핑 리뷰 보기로 대체 -->
                        <!-- 내부 리뷰 보기로 변경 -->
                        <a href="product-detail.php?id=<?= $item['product_id'] ?>#reviews" class="btn btn-outline"
                            style="padding: 8px 15px; font-size: 14px; color: #333; border-color: #ddd;">
                            <i class="fas fa-comment-alt"></i> 리뷰 보기
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </section>

    <!-- 결제 정보 -->
    <section class="payment-info" style="margin-top: 40px; background: #f9f9f9; padding: 25px; border-radius: 8px;">
        <h2 style="font-size: 18px; margin-bottom: 15px;">결제 정보</h2>
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
            <span>주문 상태</span>
            <span style="font-weight: bold; color: #2196f3;"><?= $order['status'] ?></span>
        </div>
        <div
            style="display: flex; justify-content: space-between; font-size: 18px; font-weight: bold; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 20px;">
            <span>총 결제금액</span>
            <span style="color: #e53935;"><?= number_format($order['total_amount']) ?>원</span>
        </div>
    </section>

    <div class="actions" style="margin-top: 30px; text-align: center;">
        <?php if (is_logged_in()): ?>
            <a href="mypage.php#orders" class="btn btn-outline" style="padding: 10px 30px;">목록으로</a>
        <?php else: ?>
            <a href="index.php" class="btn btn-primary" style="padding: 10px 30px;">메인으로</a>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>