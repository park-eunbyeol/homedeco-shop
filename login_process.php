<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // 사용자 조회
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // 로그인 성공 -> 세션 저장
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['name'] = $user['name'];  // 이름 저장 추가

        // 세션 보안 강화
        session_regenerate_id(true);

        // 30일간 유지되는 쿠키 설정 (Remember Me)
        setcookie('remember_token', $user['user_id'], time() + (86400 * 30), '/', '', false, true);

        redirect('index.php');
    } else {
        redirect('login.php?error=이메일 또는 비밀번호가 올바르지 않습니다.');
    }
} else {
    redirect('login.php');
}
?>