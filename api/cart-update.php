<?php
// api/cart-update.php
header('Content-Type: application/json');
require_once '../includes/db.php';
error_reporting(E_ALL);
ini_set('display_errors', 0); // JSON 응답을 위해 화면 에러 출력 방지

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'error' => '로그인이 필요합니다.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// POST 데이터 받기 (JSON 또는 Form 데이터)
$raw_input = file_get_contents('php://input');
$input_data = json_decode($raw_input, true);

if ($input_data) {
    $cart_id = isset($input_data['cart_id']) ? (int) $input_data['cart_id'] : 0;
    $change = isset($input_data['change']) ? (int) $input_data['change'] : 0;
    $action = isset($input_data['action']) ? $input_data['action'] : '';
} else {
    $cart_id = isset($_POST['cart_id']) ? (int) $_POST['cart_id'] : 0;
    $change = isset($_POST['change']) ? (int) $_POST['change'] : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
}

if ($cart_id <= 0) {
    // 디버깅 정보 포함
    $debug_info = [
        'received_raw' => $raw_input,
        'parsed_json' => $input_data,
        'post_data' => $_POST,
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'unknown'
    ];
    echo json_encode(['success' => false, 'error' => '잘못된 요청입니다. (Cart ID Missing)', 'debug' => $debug_info]);
    exit;
}

// 1. 현재 수량 및 상품 정보 조회
$check_sql = "SELECT c.quantity, p.stock FROM cart_items c 
              LEFT JOIN products p ON c.product_id = p.product_id 
              WHERE c.cart_id = ? AND c.user_id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("ii", $cart_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => '장바구니 아이템을 찾을 수 없습니다.']);
    exit;
}

$row = $result->fetch_assoc();
$current_qty = $row['quantity'];
$stock = $row['stock'];

// 2. 동작 수행 (Update or Delete)
if ($action === 'delete') {
    // 삭제
    $del_sql = "DELETE FROM cart_items WHERE cart_id = ? AND user_id = ?";
    $del_stmt = $conn->prepare($del_sql);
    $del_stmt->bind_param("ii", $cart_id, $user_id);
    if ($del_stmt->execute()) {
        // 총 가격 계산
        $total_price = calculate_total_price($conn, $user_id);
        echo json_encode(['success' => true, 'total_price' => $total_price]);
    } else {
        echo json_encode(['success' => false, 'error' => '삭제 실패']);
    }

} else if ($action === 'update') {
    // 수량 업데이트
    $new_qty = $current_qty + $change;

    if ($new_qty <= 0) {
        // 수량이 0 이하가 되면 삭제? -> 아니면 최소 1 유지 (여기선 1 유지)
        $new_qty = 1;
    }

    if ($new_qty > $stock) {
        echo json_encode(['success' => false, 'error' => '재고가 부족합니다. (남은 재고: ' . $stock . '개)']);
        exit;
    }

    $update_sql = "UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("iii", $new_qty, $cart_id, $user_id);

    if ($update_stmt->execute()) {
        $total_price = calculate_total_price($conn, $user_id);
        echo json_encode(['success' => true, 'total_price' => $total_price]);
    } else {
        echo json_encode(['success' => false, 'error' => '업데이트 실패']);
    }

} else {
    echo json_encode(['success' => false, 'error' => '알 수 없는 동작입니다.']);
}

// 총 가격 계산 함수
function calculate_total_price($conn, $user_id)
{
    $sql = "SELECT SUM(c.quantity * p.price) as total 
            FROM cart_items c 
            JOIN products p ON c.product_id = p.product_id 
            WHERE c.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return (int) $row['total'];
}
?>