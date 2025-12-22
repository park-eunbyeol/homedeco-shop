<?php
// 결제 성공 페이지
$page_title = '결제 완료';
require_once 'includes/db.php';
require_once 'includes/header.php';
require_once 'includes/functions.php';
require_once 'includes/NotificationService.php';
require_once 'includes/payment_config.php';

$tossOrderId = $_GET['orderId'] ?? '';
$paymentKey = $_GET['paymentKey'] ?? '';
$amount = $_GET['amount'] ?? 0;

if (empty($paymentKey) || empty($tossOrderId) || empty($amount)) {
    echo "<script>alert('잘못된 접근입니다. (필수 파라미터 누락)'); location.href='index.php';</script>";
    exit;
}

// ---------------------------------------------------------
// [핵심] 토스페이먼츠 결제 승인 API 호출
// 이 과정이 없으면 실제 결제가 완료되지 않고, 취소도 불가능합니다.
// ---------------------------------------------------------
$url = 'https://api.tosspayments.com/v1/payments/confirm';
$data = ['paymentKey' => $paymentKey, 'orderId' => $tossOrderId, 'amount' => $amount];
$credential = base64_encode($toss_secret_key . ':');

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => [
        'Authorization: Basic ' . $credential,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($data)
]);

$response_str = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

$json = json_decode($response_str, true);

if ($httpCode !== 200) {
    // 결제 승인 실패 처리
    $msg = $json['message'] ?? '알 수 없는 오류';
    $code = $json['code'] ?? 'UNKNOWN';
    echo "<div class='container' style='padding:50px; text-align:center;'>";
    echo "<h1>결제 승인 실패</h1>";
    echo "<p>오류 메시지: {$msg}<br>코드: {$code}</p>";
    echo "<a href='index.php' class='btn btn-primary'>메인으로 돌아가기</a>";
    echo "</div>";
    require_once 'includes/footer.php';
    exit;
}

// 승인 성공! 응답받은 정확한 키 사용
$paymentKey = $json['paymentKey'];

// ---------------------------------------------------------

if (!is_logged_in()) {
    $user_id = null; // 비회원 주문
} else {
    $user_id = $_SESSION['user_id'];
}

// 컬럼 추가 체크 (payment_key) - 없을 경우 자동 추가
$check_col = $conn->query("SHOW COLUMNS FROM orders LIKE 'payment_key'");
if ($check_col && $check_col->num_rows == 0) {
    try {
        $conn->query("ALTER TABLE orders ADD COLUMN payment_key VARCHAR(255) NULL AFTER total_amount");
    } catch (Exception $e) {
    }
}

// 1. 주문 처리 (DB 저장)
$conn->begin_transaction();

try {
    // 장바구니 조회
    if ($user_id) {
        $stmt = $conn->prepare("SELECT c.quantity, p.product_id, p.name, p.price FROM cart_items c JOIN products p ON c.product_id = p.product_id WHERE c.user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cart_items = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        // 비회원 장바구니
        $cart_items = $_SESSION['guest_cart'] ?? [];
        // 비회원 장바구니 데이터 구조 맞추기 (이미 product_id, price 등이 포함되어 있어야 함)
    }

    if (empty($cart_items)) {
        // 이미 처리되었거나 비어있음
    } else {
        // 세션에서 배송지 정보 가져오기
        $shipping_info = $_SESSION['temp_shipping'] ?? [];
        $s_name = $shipping_info['receiver_name'] ?? '';
        $s_phone = $shipping_info['receiver_phone'] ?? '';
        $s_addr = ($shipping_info['zipcode'] ?? '') . ' ' . ($shipping_info['address'] ?? '') . ' ' . ($shipping_info['address_detail'] ?? '');
        $s_msg = $shipping_info['request_msg'] ?? '';

        // 주문 생성
        $total_amount = 0;
        $order_name = $cart_items[0]['name'];
        if (count($cart_items) > 1) {
            $order_name .= ' 외 ' . (count($cart_items) - 1) . '건';
        }

        foreach ($cart_items as $item) {
            $total_amount += $item['price'] * $item['quantity'];
        }
        $shipping = $total_amount >= 50000 ? 0 : 3000;
        $grand_total = $total_amount + $shipping;

        // DB Insert
        $sql = "INSERT INTO orders (user_id, total_amount, payment_key, status, shipping_name, shipping_phone, shipping_address, created_at) 
                VALUES (?, ?, ?, 'paid', ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("idssss", $user_id, $grand_total, $paymentKey, $s_name, $s_phone, $s_addr);
        $stmt->execute();
        $new_order_id = $conn->insert_id;

        // 상세 Insert
        $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
        $item_stmt = $conn->prepare($item_sql);
        foreach ($cart_items as $item) {
            $item_stmt->bind_param("iiid", $new_order_id, $item['product_id'], $item['quantity'], $item['price']);
            $item_stmt->execute();
        }

        // 장바구니 및 임시 정보 비우기
        if ($user_id) {
            $del_stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
            $del_stmt->bind_param("i", $user_id);
            $del_stmt->execute();
        } else {
            unset($_SESSION['guest_cart']);
        }
        unset($_SESSION['temp_shipping']);

        $conn->commit();

        // 2. 알림톡(문자) 발송
        $notifier = new NotificationService();

        // 테스트를 위해 관리자 번호(내 번호)로 문자 수신
        $test_phone = '010-9450-1509';
        if (file_exists('includes/solapi_config.php')) {
            include 'includes/solapi_config.php';
            if (!empty($solapi_sender_phone))
                $test_phone = $solapi_sender_phone;
        }

        $notifier->sendOrderComplete($test_phone, $order_name, $new_order_id, $grand_total);
    }

    // 최종적으로 표시할 주문번호 식별
    $final_order_id = $new_order_id ?? 0;

    // 만약 새로고침 등으로 INSERT 로직을 건너뛰어서 $new_order_id가 없다면 DB에서 조회
    if (!$final_order_id && $paymentKey) {
        $stmt = $conn->prepare("SELECT order_id FROM orders WHERE payment_key = ?");
        $stmt->bind_param("s", $paymentKey);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $final_order_id = $row['order_id'];
        }
    }

} catch (Exception $e) {
    $conn->rollback();
    echo "<script>alert('주문 처리 중 오류가 발생했습니다: " . $e->getMessage() . "');</script>";
}
?>
<div class="container" style="text-align: center; padding: 100px 0;">
    <div style="font-size: 60px; color: #2ecc71; margin-bottom: 20px;">
        <i class="fas fa-check-circle"></i>
    </div>
    <h1>결제가 완료되었습니다!</h1>
    <p style="color: #666; margin-bottom: 30px;">
        주문번호: <strong><?php echo $final_order_id; ?></strong><br>
        결제금액: <?php echo number_format($amount); ?>원
    </p>
    <div style="display: flex; gap: 10px; justify-content: center;">
        <a href="mypage.php#orders" class="btn btn-primary">주문 내역 보기</a>
        <a href="index.php" class="btn btn-outline">쇼핑 계속하기</a>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>