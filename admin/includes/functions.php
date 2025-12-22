<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 로그인 상태 확인 (쿠키 자동 로그인 포함)
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        // 세션 확인
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            return true;
        }
        
        // 쿠키로 자동 로그인 시도
        if (isset($_COOKIE['remember_token'])) {
            require_once __DIR__ . '/db.php';
            $user_id = (int)$_COOKIE['remember_token'];
            
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if ($user) {
                // 세션 복원
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['name'] = $user['name'];
                return true;
            } else {
                // 유효하지 않은 쿠키 삭제
                setcookie('remember_token', '', time() - 3600, '/', '', false, true);
            }
        }
        
        return false;
    }
}

// 로그인 사용자 정보 가져오기
if (!function_exists('current_user')) {
    function current_user() {
        if (is_logged_in()) {
            return [
                'id' => $_SESSION['user_id'],
                'email' => $_SESSION['user_email'],
                'name' => $_SESSION['name'] ?? '사용자'
            ];
        }
        return null;
    }
}

// 사용자 이름 가져오기
if (!function_exists('get_user_name')) {
    function get_user_name() {
        if (is_logged_in()) {
            return $_SESSION['name'] ?? '사용자';
        }
        return '게스트';
    }
}

// 로그아웃
if (!function_exists('logout')) {
    function logout() {
        session_unset();
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
}

// 페이지 이동
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit;
    }
}
?>