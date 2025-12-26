<?php
session_start();
require_once '../includes/db.php';

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($name === '' || $email === '' || $password === '') {
    header("Location: register.php?error=empty");
    exit;
}

$hashed_pw = password_hash($password, PASSWORD_DEFAULT);

// 이메일 중복 체크
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    header("Location: register.php?error=exists");
    exit;
}

// 회원 저장 (기본적으로 중간 관리자로 등록)
$stmt = $conn->prepare(
    "INSERT INTO users (name, email, password, admin_level) VALUES (?, ?, ?, 1)"
);
$stmt->bind_param("sss", $name, $email, $hashed_pw);
$stmt->execute();

header("Location: login.php?success=1");
exit;
