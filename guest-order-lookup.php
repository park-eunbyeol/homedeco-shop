<?php
$page_title = '비회원 주문조회';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$error = '';
$order_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id_raw = trim($_POST['order_id']);
    $phone = trim($_POST['phone']);

    // ORDER_12345678_... 형식 또는 숫자만 입력했을 경우 처리
    $order_id = $order_id_raw;
    if (strpos($order_id, 'ORDER_') === 0) {
        // 만약 입력한게 Toss OrderId(예: ORDER_TIMESTAMP_...) 라면, 
        // 우리 DB의 order_id(인덱스)로 조회하기 위해 분기가 필요할 수 있음.
        // 하지만 여기서는 사용자가 '주문번호 2'라고 알고 있을 것이므로 
        // 입력을 숫자로 유도하거나, ORDER_ 부분을 제거해봅니다.
        $order_id = preg_replace('/[^0-9]/', '', $order_id);
    }

    // 숫자가 아닌 문자 제거 (하이픈 등)
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // 주문 조회 (사용자 ID가 없는 주문 중 검색)
    // 실제로는 order_no와 phone으로 검색하는 전용 쿼리가 필요함
    // 여기서는 orders 테이블의 receiver_phone 필드와 id로 검색
    $sql = "SELECT * FROM orders WHERE order_id = ? AND REPLACE(shipping_phone, '-', '') = ? AND user_id IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $order_id, $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $order_data = $result->fetch_assoc();
        // 상세 조회를 위해 order-detail.php로 리다이렉트 (guest 토큰 등을 세션에 저장)
        $_SESSION['guest_order_view'] = $order_data['order_id'];
        header("Location: order-detail.php?id=" . $order_data['order_id'] . "&guest=1");
        exit;
    } else {
        $error = '주문 정보가 일치하지 않습니다. 주문번호와 연락처를 확인해주세요.';
    }
}

require_once 'includes/header.php';
?>

<link rel="stylesheet" href="auth.css">
<style>
    .lookup-container {
        max-width: 450px;
        margin: 60px auto;
        padding: 40px;
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    }

    .lookup-title {
        text-align: center;
        margin-bottom: 30px;
    }

    .lookup-title h2 {
        font-size: 24px;
        color: #333;
        margin-bottom: 10px;
    }

    .lookup-title p {
        color: #888;
        font-size: 14px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #555;
        font-size: 14px;
    }

    .form-group input {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 15px;
        transition: border-color 0.3s;
    }

    .form-group input:focus {
        border-color: #2c3e50;
        outline: none;
    }

    .btn-lookup {
        width: 100%;
        padding: 14px;
        background: #2c3e50;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s;
    }

    .btn-lookup:hover {
        background: #1a252f;
    }

    .alert-error {
        background: #fff5f5;
        color: #e53e3e;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
        text-align: center;
        border: 1px solid #fed7d7;
    }
</style>

<div class="container">
    <div class="lookup-container">
        <div class="lookup-title">
            <h2>비회원 주문조회</h2>
            <p>비회원으로 주문하신 상품을 확인하실 수 있습니다.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="order_id">주문번호</label>
                <input type="text" id="order_id" name="order_id" placeholder="숫자만 입력 (예: 123)" required>
            </div>
            <div class="form-group">
                <label for="phone">연락처</label>
                <input type="tel" id="phone" name="phone" placeholder="010-0000-0000" required>
            </div>
            <button type="submit" class="btn-lookup">조회하기</button>
        </form>

        <div style="margin-top: 30px; text-align: center; font-size: 13px; color: #999;">
            주문번호를 잊으셨나요? <a href="contact.php" style="color: #666; text-decoration: underline;">고객센터 문의</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>