<?php
// 데이터베이스 설정
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'homedeco_shop');

// 데이터베이스 연결
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("데이터베이스 연결 실패: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================
// 보안 함수
// ==========================
function clean_input($data)
{
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// ==========================
// 로그인/관리자 체크
// ==========================
if (!function_exists('is_logged_in')) {
    function is_logged_in()
    {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('is_admin')) {
    function is_admin()
    {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }
}

if (!function_exists('get_admin_level')) {
    function get_admin_level()
    {
        return $_SESSION['admin_level'] ?? 0;
    }
}

if (!function_exists('is_super_admin')) {
    function is_super_admin()
    {
        return get_admin_level() >= 2;
    }
}

// ==========================
// 페이지 리다이렉트
// ==========================
if (!function_exists('redirect')) {
    function redirect($url)
    {
        header("Location: $url");
        exit();
    }
}

// ==========================
// 장바구니 수량 가져오기
// ==========================
if (!function_exists('get_cart_count')) {
    function get_cart_count($conn = null)
    {
        global $conn;
        if (is_logged_in()) {
            if (!$conn)
                return 0;
            $user_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE user_id=?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return $result['total'] ?? 0;
        } else {
            // 게스트 세션 장바구니
            return isset($_SESSION['guest_cart']) ? array_sum(array_column($_SESSION['guest_cart'], 'quantity')) : 0;
        }
    }
}

// ==========================
// 찜하기 수량 가져오기
// ==========================
if (!function_exists('get_wishlist_count')) {
    function get_wishlist_count($conn = null)
    {
        global $conn;
        if (is_logged_in()) {
            if (!$conn)
                return 0;
            $user_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM wishlist WHERE user_id=?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return $result['total'] ?? 0;
        } else {
            return isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;
        }
    }
}

// ==========================
// 가격/날짜 포맷
// ==========================
if (!function_exists('format_price')) {
    function format_price($price)
    {
        return number_format($price) . '원';
    }
}

if (!function_exists('format_date')) {
    function format_date($date)
    {
        return date('Y.m.d', strtotime($date));
    }
}
