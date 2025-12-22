<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once '../includes/db.php';

    // JSON 입력 처리
    $input_json = file_get_contents('php://input');
    $input = json_decode($input_json, true);

    if ($input) {
        $product_id = isset($input['product_id']) ? (int) $input['product_id'] : 0;
        $quantity = isset($input['quantity']) ? (int) $input['quantity'] : 1;
    } else {
        $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;
    }

    // 로그인 확인 및 게스트 처리
    if (!is_logged_in()) {
        if (!isset($_SESSION['guest_cart'])) {
            $_SESSION['guest_cart'] = [];
        }

        // 상품 정보 조회
        $product_check = $conn->prepare("SELECT product_id, name, price, main_image, stock FROM products WHERE product_id = ? AND is_active = 1");
        $product_check->bind_param("i", $product_id);
        $product_check->execute();
        $p = $product_check->get_result()->fetch_assoc();

        if (!$p) {
            echo json_encode(['success' => false, 'message' => '상품을 찾을 수 없습니다.']);
            exit;
        }

        // 이미 있으면 수량 증가
        $found = false;
        foreach ($_SESSION['guest_cart'] as &$item) {
            if ($item['product_id'] == $product_id) {
                $item['quantity'] += $quantity;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $_SESSION['guest_cart'][] = [
                'cart_id' => 'guest_' . time() . '_' . rand(100, 999),
                'product_id' => $p['product_id'],
                'name' => $p['name'],
                'price' => $p['price'],
                'main_image' => $p['main_image'],
                'quantity' => $quantity,
                'stock' => $p['stock']
            ];
        }

        echo json_encode(['success' => true, 'message' => '장바구니에 추가되었습니다. (게스트)']);
        exit;
    }

    $user_id = $_SESSION['user_id'];

    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => '잘못된 상품입니다.']);
        exit;
    }

    if ($quantity <= 0) {
        $quantity = 1;
    }

    // 상품 존재 여부 확인
    $product_check = $conn->prepare("SELECT product_id, name, price, stock FROM products WHERE product_id = ? AND is_active = 1");
    $product_check->bind_param("i", $product_id);
    $product_check->execute();
    $product = $product_check->get_result()->fetch_assoc();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => '상품을 찾을 수 없습니다.']);
        exit;
    }

    // 재고 확인
    if ($product['stock'] < $quantity) {
        echo json_encode(['success' => false, 'message' => '재고가 부족합니다.']);
        exit;
    }

    // 이미 장바구니에 있는지 확인
    $check_sql = "SELECT cart_id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $product_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // 이미 있으면 수량 증가
        $cart_item = $result->fetch_assoc();
        $new_quantity = $cart_item['quantity'] + $quantity;

        // 재고 확인
        if ($product['stock'] < $new_quantity) {
            echo json_encode(['success' => false, 'message' => '재고가 부족합니다.']);
            exit;
        }

        $update_sql = "UPDATE cart_items SET quantity = ? WHERE cart_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $new_quantity, $cart_item['cart_id']);

        if ($update_stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => '장바구니 수량이 업데이트되었습니다.'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => '업데이트 실패: ' . $update_stmt->error]);
        }
    } else {
        // 새로 추가
        $insert_sql = "INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);

        if ($insert_stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => '장바구니에 추가되었습니다.'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => '추가 실패: ' . $insert_stmt->error]);
        }
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '오류 발생: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>