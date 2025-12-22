<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/functions.php';

$raw_input = file_get_contents('php://input');
$input_data = json_decode($raw_input, true);

$cart_id = $input_data['cart_id'] ?? ($_POST['cart_id'] ?? '');
$change = (int) ($input_data['change'] ?? ($_POST['change'] ?? 0));
$action = $input_data['action'] ?? ($_POST['action'] ?? '');

if (!isset($_SESSION['guest_cart'])) {
    $_SESSION['guest_cart'] = [];
}

$success = false;
$error = '';

if ($action === 'delete') {
    foreach ($_SESSION['guest_cart'] as $key => $item) {
        if ($item['cart_id'] == $cart_id) {
            unset($_SESSION['guest_cart'][$key]);
            $_SESSION['guest_cart'] = array_values($_SESSION['guest_cart']); // Re-index
            $success = true;
            break;
        }
    }
} else if ($action === 'update') {
    foreach ($_SESSION['guest_cart'] as &$item) {
        if ($item['cart_id'] == $cart_id) {
            $new_qty = $item['quantity'] + $change;
            if ($new_qty <= 0)
                $new_qty = 1;

            if ($new_qty > $item['stock']) {
                echo json_encode(['success' => false, 'error' => '재고가 부족합니다.']);
                exit;
            }

            $item['quantity'] = $new_qty;
            $success = true;
            break;
        }
    }
}

if ($success) {
    $total_price = 0;
    foreach ($_SESSION['guest_cart'] as $item) {
        $total_price += $item['price'] * $item['quantity'];
    }
    echo json_encode(['success' => true, 'total_price' => $total_price]);
} else {
    echo json_encode(['success' => false, 'error' => '요청을 처리할 수 없습니다.']);
}
