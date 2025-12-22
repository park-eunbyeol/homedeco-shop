<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

$conn->begin_transaction();

try {
    // 1. 장바구니 확인
    $stmt = $conn->prepare("SELECT c.quantity, p.product_id, p.price FROM cart_items c JOIN products p ON c.product_id = p.product_id WHERE c.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_items = $result->fetch_all(MYSQLI_ASSOC);

    if (empty($cart_items)) {
        throw new Exception("장바구니가 비어있습니다.");
    }

    // 2. 총액 계산
    $total_amount = 0;
    foreach ($cart_items as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }
    $shipping = $total_amount >= 50000 ? 0 : 3000;
    $grand_total = $total_amount + $shipping;

    // 3. 주문 생성
    // receiver_name 등 컬럼 존재 여부를 모르므로 status, total_amount 위주로 저장
    // created_at은 DB 기본값일 수 있으나 명시
    $sql = "INSERT INTO orders (user_id, total_amount, status, created_at) VALUES (?, ?, 'paid', NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("id", $user_id, $grand_total);

    if (!$stmt->execute()) {
        throw new Exception("주문 생성 실패: " . $stmt->error);
    }

    $order_id = $conn->insert_id;

    // 4. 주문 상세 생성
    $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
    $item_stmt = $conn->prepare($item_sql);

    foreach ($cart_items as $item) {
        $item_stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
        if (!$item_stmt->execute()) {
            throw new Exception("주문 상품 저장 실패");
        }
    }

    // 5. 장바구니 비우기
    $del_stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
    $del_stmt->bind_param("i", $user_id);
    $del_stmt->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'order_id' => $order_id]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>