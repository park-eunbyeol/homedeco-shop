<?php
session_start();
require_once '../includes/db.php';

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$stmt = $conn->prepare(
    "SELECT user_id, name, password, admin_level FROM users WHERE email = ? AND admin_level > 0"
);
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    // 관리자 세션 별도 분리
    $_SESSION['admin_id'] = $user['user_id'];
    $_SESSION['admin_name'] = $user['name'];
    $_SESSION['admin_level'] = $user['admin_level']; // 등급 저장 (1: 중간, 2: 최고)
    $_SESSION['admin_logged_in'] = true;

    header("Location: index.php");
    exit;
}

header("Location: login.php?error=1");
exit;
