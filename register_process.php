<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('register.php?error=잘못된 접근입니다');
}

$email = clean_input($_POST['email']);
$name = clean_input($_POST['name']);
$password = $_POST['password'];
$confirm = $_POST['confirm'];

if (empty($email) || empty($name) || empty($password) || empty($confirm)) {
    redirect('register.php?error=모든 항목을 입력해주세요.');
}

// 비밀번호 확인
if ($password !== $confirm) {
    redirect('register.php?error=비밀번호가 일치하지 않습니다.');
}

// 이메일 중복 확인
$sql = "SELECT * FROM users WHERE email = '$email'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    redirect('register.php?error=이미 사용 중인 이메일입니다.');
}

// 암호화
$hashed = password_hash($password, PASSWORD_DEFAULT);

// 회원 저장
$sql = "INSERT INTO users (email, name, password)
        VALUES ('$email', '$name', '$hashed')";

if ($conn->query($sql)) {
    redirect('login.php?success=회원가입이 완료되었습니다.');
} else {
    redirect('register.php?error=회원가입 중 오류가 발생했습니다.');
}
