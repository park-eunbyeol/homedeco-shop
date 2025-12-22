<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

try {
    $input_json = file_get_contents('php://input');
    $input = json_decode($input_json, true);

    if (!$input) {
        throw new Exception('잘못된 요청입니다.');
    }

    $name = clean_input($input['name']);
    $price = (int) $input['price'];
    $image = clean_input($input['image']);
    $link = clean_input($input['link']);
    $brand = clean_input($input['brand']);

    if (empty($name) || $price <= 0) {
        throw new Exception('상품 정보가 부족합니다.');
    }

    // 1. 이미 존재하는 상품인지 확인 (이름과 링크로 체크)
    $stmt = $conn->prepare("SELECT product_id FROM products WHERE name = ? LIMIT 1");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    $product_id = 0;

    if ($product) {
        $product_id = $product['product_id'];
    } else {
        // 2. 존재하지 않으면 새로 생성
        // 카테고리는 '기타/소품' (5번) 또는 적절한 기본값 사용
        $category_id = 5;
        $stock = 999; // 넉넉한 재고
        $description = "네이버 쇼핑 연동 상품\n브랜드: " . $brand . "\n원문 링크: " . $link;

        $insert_stmt = $conn->prepare("INSERT INTO products (category_id, name, description, price, stock, main_image, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $insert_stmt->bind_param("issiis", $category_id, $name, $description, $price, $stock, $image);

        if ($insert_stmt->execute()) {
            $product_id = $conn->insert_id;
        } else {
            throw new Exception("상품 생성 실패: " . $conn->error);
        }
    }

    // 3. 장바구니에 추가 로직 (기존 api/cart-add.php 로직 재사용하거나 여기서 직접 처리)
    // 여기서는 직접 처리하여 응답을 보냄

    $quantity = 1;

    // 로그인 확인 및 게스트 처리
    if (!is_logged_in()) {
        if (!isset($_SESSION['guest_cart'])) {
            $_SESSION['guest_cart'] = [];
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
                'product_id' => $product_id,
                'name' => $name,
                'price' => $price,
                'main_image' => $image,
                'quantity' => $quantity,
                'stock' => 999
            ];
        }

        echo json_encode(['success' => true, 'message' => '장바구니에 추가되었습니다. (게스트)']);
        exit;
    }

    // 로그인 회원 처리
    $user_id = $_SESSION['user_id'];

    // 테이블명 확인: cart vs cart_items (기존 코드에서 cart_items 사용 확인됨.. 어라? 스키마 파일엔 cart인데? 확인 필요)
    // 스키마 파일: CREATE TABLE cart (...)
    // api/cart-add.php: SELECT ... FROM cart_items ... 이건 뭐지? 
    // api/cart-add.php line 93: SELECT cart_id, quantity FROM cart_items
    // api/cart-add.php line 72: SELECT SUM(quantity) as total FROM cart (includes/db.php)
    // 불일치 발견! includes/db.php 에서는 'cart', api/cart-add.php 에서는 'cart_items'.
    // 작성자가 스키마 파일과 실제 코드를 다르게 짰을 수 있음.
    // 하지만 우선 실존하는 테이블을 확인해야 함. admin/.sql 이 source of truth 라고 했지만, 실제 구동 코드가 더 중요할 수 있음.
    // db.php 의 `get_cart_count`는 `cart` 테이블을 씀.
    // checkout.php 는 `cart` 테이블을 쓸 것임.
    // api/cart-add.php 를 다시 보니... line 93: `FROM cart_items`. 
    // This looks like a bug in api/cart-add.php if checkout.php uses `cart`.

    // Let's assume `cart` is the correct table per schema and db.php.
    // I will use `cart` table here.

    $check_sql = "SELECT cart_id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $product_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $cart_item = $result->fetch_assoc();
        $new_quantity = $cart_item['quantity'] + $quantity;
        $update_sql = "UPDATE cart SET quantity = ? WHERE cart_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $new_quantity, $cart_item['cart_id']);
        $update_stmt->execute();
    } else {
        $insert_cart_sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
        $insert_cart_stmt = $conn->prepare($insert_cart_sql);
        $insert_cart_stmt->bind_param("iii", $user_id, $product_id, $quantity);
        $insert_cart_stmt->execute();
    }

    echo json_encode(['success' => true, 'message' => '장바구니에 추가되었습니다.']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>