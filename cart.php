<?php
$page_title = '장바구니';
require_once 'includes/functions.php';
require_once 'includes/db.php';

// 로그인 상태 확인 (쿠키 자동 로그인 포함)
$is_guest = !is_logged_in();
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $is_guest ? '게스트' : get_user_name();

// 장바구니 데이터
$cart_items_array = [];

if (!$is_guest) {
    $stmt = $conn->prepare("
        SELECT c.cart_id, c.quantity, c.created_at, p.product_id, p.name, p.price, p.main_image, p.stock
        FROM cart_items c
        LEFT JOIN products p ON c.product_id = p.product_id
        WHERE c.user_id=?
        ORDER BY c.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_items_array = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    if (!isset($_SESSION['guest_cart']))
        $_SESSION['guest_cart'] = [];
    $cart_items_array = $_SESSION['guest_cart'];
}

require_once 'includes/header.php';
?>

<link rel="stylesheet" href="cart.css">

<div class="container">
    <div class="page-header">
        <h1>장바구니</h1>
        <?php if (!$is_guest): ?>
            <p class="user-greeting">안녕하세요, <strong><?= htmlspecialchars($user_name) ?></strong>님 👋</p>
        <?php endif; ?>

        <?php if ($is_guest): ?>
            <div class="guest-info-box">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M10 0C4.48 0 0 4.48 0 10s4.48 10 10 10 10-4.48 10-10S15.52 0 10 0zm1 15H9v-2h2v2zm0-4H9V5h2v6z"
                        fill="#ff9800" />
                </svg>
                <div>
                    <strong>로그인하지 않은 상태입니다</strong>
                    <p>장바구니가 브라우저에 임시 저장됩니다. <a href="login.php?redirect=cart.php" class="login-link">로그인하면 계정에 저장됩니다</a>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php
    $total_price = 0;
    $total_items = 0;
    foreach ($cart_items_array as $item) {
        $total_price += $item['price'] * $item['quantity'];
        $total_items += $item['quantity'];
    }
    $item_count = count($cart_items_array);
    ?>

    <div class="cart-stats">
        <p>총 <strong><?= $item_count ?></strong>개 상품 · <strong><?= $total_items ?></strong>개 수량</p>
    </div>

    <?php if ($item_count > 0): ?>
        <div class="cart-layout">
            <div class="cart-items">
                <div class="cart-header">
                    <label><input type="checkbox" id="selectAll"> 전체 선택</label>
                    <button class="btn-text" id="deleteSelectedBtn">선택삭제</button>
                </div>

                <?php foreach ($cart_items_array as $item):
                    $cart_id = $item['cart_id'];
                    $item_total = $item['price'] * $item['quantity'];
                    ?>
                    <div class="cart-item" id="cart_item_<?= $cart_id ?>">
                        <input type="checkbox" class="item-checkbox" value="<?= $cart_id ?>">
                        <div class="item-image">
                            <img src="<?= $item['main_image'] ?>"
                                onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\' viewBox=\'0 0 100 100\'%3E%3Crect width=\'100\' height=\'100\' fill=\'#f0f0f0\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' font-family=\'Arial\' font-size=\'12\' fill=\'#999\' text-anchor=\'middle\' dy=\'.3em\'%3ENo Img%3C/text%3E%3C/svg%3E'">
                        </div>
                        <div class="item-info">
                            <h3><?= htmlspecialchars($item['name']) ?></h3>
                            <p class="price"><?= number_format($item['price']) ?>원</p>
                            <?php
                            $tomorrow = date('m/d', strtotime('+1 day'));
                            $day_kor = ['일', '월', '화', '수', '목', '금', '토'][date('w', strtotime('+1 day'))];
                            ?>
                            <p class="delivery-date"><i class="fas fa-truck"></i> 내일(<?= $day_kor ?>) <?= $tomorrow ?> 도착 보장</p>
                        </div>
                        <div class="item-quantity">
                            <button class="minus" data-id="<?= $cart_id ?>">-</button>
                            <input type="number" id="qty_<?= $cart_id ?>" value="<?= $item['quantity'] ?>" readonly>
                            <button class="plus" data-id="<?= $cart_id ?>">+</button>
                        </div>
                        <div class="item-total" id="item_total_<?= $cart_id ?>" data-price="<?= $item['price'] ?>">
                            <strong><?= number_format($item_total) ?>원</strong>
                        </div>
                        <button class="btn-delete" data-id="<?= $cart_id ?>">×</button>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <div class="summary-card">
                    <h3>주문 요약</h3>
                    <div class="summary-row">
                        <span>상품 금액</span>
                        <span id="summary_price"><?= number_format($total_price) ?>원</span>
                    </div>
                    <div class="summary-row">
                        <span>배송비</span>
                        <span id="summary_shipping"><?= ($total_price >= 50000 ? '무료' : '3,000원') ?></span>
                    </div>
                    <div class="summary-divider"></div>
                    <div class="summary-row total">
                        <span>총 결제금액</span>
                        <span
                            id="summary_total"><?= number_format($total_price + ($total_price >= 50000 ? 0 : 3000)) ?>원</span>
                    </div>
                    <button class="btn btn-primary btn-large" onclick="checkout()">주문하기</button>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="empty-cart">
            <div class="empty-icon">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#ddd" stroke-width="1.5">
                    <path
                        d="M9 2L7.17 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-3.17L15 2H9zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z" />
                </svg>
            </div>
            <h3>장바구니가 비어있습니다</h3>
            <p>마음에 드는 상품을 담아보세요</p>
            <a href="products.php" class="btn btn-primary">상품 둘러보기</a>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const isGuest = <?= $is_guest ? 'true' : 'false' ?>;

        function updateCart(cartId, change, action = 'update') {
            let url = isGuest ? './api/guest-cart-update.php' : './api/cart-update.php';
            let bodyData = JSON.stringify({ cart_id: cartId, change: change, action: action });

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: bodyData
            })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) { alert(data.error || '장바구니 업데이트 실패'); return; }

                    if (action === 'update') {
                        const qtyInput = document.querySelector('#qty_' + cartId);
                        if (qtyInput) qtyInput.value = parseInt(qtyInput.value) + change;

                        const itemTotal = document.querySelector('#item_total_' + cartId);
                        if (itemTotal) itemTotal.innerHTML = '<strong>' + new Intl.NumberFormat().format(qtyInput.value * parseInt(itemTotal.dataset.price)) + '원</strong>';
                    } else if (action === 'delete') {
                        const cartItem = document.querySelector('#cart_item_' + cartId);
                        if (cartItem) cartItem.remove();
                    }

                    let totalPrice = data.total_price;
                    let shipping = totalPrice >= 50000 ? 0 : 3000;
                    document.querySelector('#summary_price').textContent = new Intl.NumberFormat().format(totalPrice) + '원';
                    document.querySelector('#summary_shipping').textContent = shipping === 0 ? '무료' : '3,000원';
                    document.querySelector('#summary_total').textContent = new Intl.NumberFormat().format(totalPrice + shipping) + '원';

                    // 장바구니가 비었으면 페이지 새로고침
                    if (document.querySelectorAll('.cart-item').length === 0) {
                        location.reload();
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('오류가 발생했습니다.');
                });
        }

        // + 버튼
        document.querySelectorAll('.plus').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const cartId = e.currentTarget.dataset.id;
                console.log('Plus clicked, cartId:', cartId);
                updateCart(parseInt(cartId), 1, 'update');
            });
        });

        // - 버튼
        document.querySelectorAll('.minus').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const cartId = e.currentTarget.dataset.id;
                const qtyInput = document.querySelector('#qty_' + cartId);
                if (parseInt(qtyInput.value) > 1) {
                    console.log('Minus clicked, cartId:', cartId);
                    updateCart(parseInt(cartId), -1, 'update');
                }
            });
        });

        // 삭제 버튼
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const cartId = e.currentTarget.dataset.id;
                console.log('Delete clicked, cartId:', cartId);
                if (confirm('이 상품을 장바구니에서 삭제하시겠습니까?')) {
                    updateCart(parseInt(cartId), 0, 'delete');
                }
            });
        });

        // 전체 선택
        document.querySelector('#selectAll')?.addEventListener('change', (e) => {
            document.querySelectorAll('.item-checkbox').forEach(cb => {
                cb.checked = e.target.checked;
            });
        });

        // 선택 삭제
        document.querySelector('#deleteSelectedBtn')?.addEventListener('click', () => {
            const selected = Array.from(document.querySelectorAll('.item-checkbox:checked'));
            if (selected.length === 0) {
                alert('삭제할 상품을 선택해주세요.');
                return;
            }
            if (confirm(`선택한 ${selected.length}개 상품을 삭제하시겠습니까?`)) {
                selected.forEach(cb => {
                    updateCart(cb.value, 0, 'delete');
                });
            }
        });
    });

    function checkout() {
        location.href = 'checkout.php';
    }
</script>