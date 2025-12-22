<?php
$page_title = '주문/결제';
require_once 'includes/functions.php';
require_once 'includes/db.php';
require_once 'includes/payment_config.php';

$is_guest = !is_logged_in();
$user_id = $_SESSION['user_id'] ?? null;

// 장바구니 아이템 가져오기
$cart_items = [];
if ($is_guest) {
    if (isset($_SESSION['guest_cart'])) {
        $cart_items = $_SESSION['guest_cart'];
    }
} else {
    $stmt = $conn->prepare("
        SELECT c.*, p.name, p.price, p.main_image 
        FROM cart_items c 
        JOIN products p ON c.product_id = p.product_id 
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_items = $result->fetch_all(MYSQLI_ASSOC);
}

// 총 금액 계산
$total_price = 0;
foreach ($cart_items as $item) {
    $total_price += $item['price'] * $item['quantity'];
}
$shipping_fee = $total_price >= 50000 ? 0 : 3000;
$final_price = $total_price + $shipping_fee;

require_once 'includes/header.php';
?>

<div class="container" style="max-width: 1000px; margin: 40px auto; padding: 0 20px;">
    <h1>주문/결제</h1>

    <div class="checkout-layout" style="display: grid; grid-template-columns: 1fr 350px; gap: 40px; margin-top: 30px;">
        <!-- 왼쪽: 배송지 정보 -->
        <div class="shipping-info">
            <h2 style="font-size: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px;">배송지
                정보</h2>
            <form id="orderForm">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">받는 분</label>
                    <input type="text" name="receiver_name" class="form-control"
                        value="<?php echo $is_guest ? '' : htmlspecialchars(get_user_name()); ?>"
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" required>
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">연락처</label>
                    <input type="tel" name="receiver_phone" class="form-control"
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
                        placeholder="010-0000-0000" required>
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">주소</label>
                    <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                        <input type="text" name="zipcode" placeholder="우편번호"
                            style="width: 120px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" readonly>
                        <button type="button" class="btn btn-outline"
                            style="padding: 10px 15px; background: white; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">주소
                            검색</button>
                    </div>
                    <input type="text" name="address" placeholder="기본 주소"
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px;"
                        readonly>
                    <input type="text" name="address_detail" placeholder="상세 주소"
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div class="form-group">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">배송 요청사항</label>
                    <select name="request_msg"
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">배송 요청사항을 선택해주세요</option>
                        <option value="문 앞에 놓고 가주세요">문 앞에 놓고 가주세요</option>
                        <option value="경비실에 맡겨주세요">경비실에 맡겨주세요</option>
                        <option value="배송 전 연락바랍니다">배송 전 연락바랍니다</option>
                        <option value="direct">직접 입력</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- 결제 UI (Toss Payments) -->
        <div class="payment-info"
            style="grid-column: 1 / 2; background: #f9f9f9; padding: 20px; border-radius: 8px; text-align: center;">
            <p style="margin: 0; font-weight: bold; color: #333;">💳 결제는 토스페이먼츠 보안 창에서 안전하게 진행됩니다.</p>
        </div>

        <!-- 오른쪽: 주문 상품 및 결제 -->
        <div class="order-summary">
            <h2 style="font-size: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px;">주문 상품
            </h2>
            <div class="summary-items"
                style="max-height: 300px; overflow-y: auto; margin-bottom: 20px; border: 1px solid #eee; padding: 15px; border-radius: 8px;">
                <?php if (empty($cart_items)): ?>
                    <p style="text-align: center; color: #999;">장바구니가 비어있습니다.</p>
                <?php else: ?>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="item"
                            style="display: flex; gap: 15px; margin-bottom: 15px; border-bottom: 1px solid #f5f5f5; padding-bottom: 15px;">
                            <img src="<?= htmlspecialchars($item['main_image']) ?>" alt=""
                                style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #eee;">
                            <div>
                                <p style="margin: 0 0 5px; font-size: 14px; font-weight: bold;">
                                    <?= htmlspecialchars($item['name']) ?>
                                </p>
                                <p style="margin: 0; color: #888; font-size: 13px;"><?= number_format($item['price']) ?>원 ×
                                    <?= $item['quantity'] ?>개
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="price-summary" style="background: #f9f9f9; padding: 20px; border-radius: 8px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span>총 상품금액</span>
                    <span><?= number_format($total_price) ?>원</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span>배송비</span>
                    <span><?= $shipping_fee == 0 ? '무료' : number_format($shipping_fee) . '원' ?></span>
                </div>
                <div
                    style="display: flex; justify-content: space-between; margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; font-weight: bold; font-size: 18px;">
                    <span>총 결제금액</span>
                    <span style="color: #e53935;"><?= number_format($final_price) ?>원</span>
                </div>
            </div>

            <button class="btn btn-primary btn-block btn-large"
                style="width: 100%; margin-top: 20px; padding: 15px; background: #333; color: white; border: none; border-radius: 4px; font-size: 16px; font-weight: bold; cursor: pointer;"
                onclick="requestPayment()">
                <?= number_format($final_price) ?>원 결제하기
            </button>
        </div>
    </div>
</div>

<script src="https://js.tosspayments.com/v2/standard"></script>
<script>
    const clientKey = "<?php echo $toss_client_key; ?>";
    const customerKey = "<?php echo $user_id ? 'user_' . $user_id : 'guest_' . uniqid(); ?>";
    const amount = <?php echo $final_price; ?>;

    // V2 SDK 초기화 (결제창 방식)
    const tossPayments = TossPayments(clientKey);
    const payment = tossPayments.payment({ customerKey });

    async function requestPayment() {
        if (<?= empty($cart_items) ? 'true' : 'false' ?>) {
            alert('주문할 상품이 없습니다.');
            return;
        }

        // 필수 입력 체크
        const form = document.getElementById('orderForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const orderName = "<?php echo !empty($cart_items) ? $cart_items[0]['name'] . (count($cart_items) > 1 ? ' 외 ' . (count($cart_items) - 1) . '건' : '') : ''; ?>";
        const customerName = formData.get('receiver_name');
        const customerMobilePhone = formData.get('receiver_phone');

        // 배송지 정보 세션 저장 (비회원 대비)
        await fetch('api/save-shipping-session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                receiver_name: customerName,
                receiver_phone: customerMobilePhone,
                zipcode: formData.get('zipcode'),
                address: formData.get('address'),
                address_detail: formData.get('address_detail'),
                request_msg: formData.get('request_msg')
            })
        });

        try {
            // 결제창 열기
            await payment.requestPayment({
                method: "CARD", // 카드 결제
                amount: {
                    currency: "KRW",
                    value: amount,
                },
                orderId: "ORDER_" + new Date().getTime() + "_" + Math.random().toString(36).substring(2, 9),
                orderName: orderName,
                successUrl: window.location.origin + "/homedeco-shop/payment_success.php",
                failUrl: window.location.origin + "/homedeco-shop/payment_fail.php",
                customerEmail: "customer@example.com",
                customerName: customerName,
                customerMobilePhone: customerMobilePhone
            });
        } catch (err) {
            console.error(err);
            if (err.code === "USER_CANCEL") {
                // 사용자가 취소함
            } else {
                alert("결제 요청 중 오류가 발생했습니다: " + err.message);
            }
        }
    }

    // 주소 검색 버튼 (데모용 간단 동작)
    document.querySelector('.btn-outline').addEventListener('click', () => {
        new daum.Postcode({
            oncomplete: function (data) {
                document.querySelector('input[name="zipcode"]').value = data.zonecode;
                document.querySelector('input[name="address"]').value = data.address;
                document.querySelector('input[name="address_detail"]').focus();
            }
        }).open();
    });
</script>
<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>