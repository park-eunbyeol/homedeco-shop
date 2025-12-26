<?php
session_start();
require_once '../includes/db.php';

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header("Location: login.php?error=empty");
    exit;
}

// admin_level이 1(중간) 또는 2(최고)인 계정만 조회
$stmt = $conn->prepare(
    "SELECT user_id, name, password, admin_level FROM users WHERE email = ? AND admin_level > 0"
);
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    // 세션 초기화 (기존 일반 회원 세션과 충돌 방지)
    // session_regenerate_id(true); // 필수는 아니지만 보안상 권장

    // 관리자 전용 세션 설정
    $_SESSION['admin_id'] = $user['user_id'];
    $_SESSION['admin_name'] = $user['name'];
    $_SESSION['admin_level'] = $user['admin_level'];
    $_SESSION['admin_logged_in'] = true;

    header("Location: index.php");
    exit;
}

header("Location: login.php?error=1");
exit;
