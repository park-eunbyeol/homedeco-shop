<?php
require_once '../includes/db.php';

if (!is_logged_in()) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $name = clean_input($_POST['name']);
    $phone = clean_input($_POST['phone']);
    $address = clean_input($_POST['address']);

    // 입력값 검증
    if (empty($name)) {
        echo "<script>alert('이름을 입력해주세요.'); history.back();</script>";
        exit();
    }

    $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE user_id = ?");
    $stmt->bind_param("sssi", $name, $phone, $address, $user_id);

    if ($stmt->execute()) {
        $_SESSION['name'] = $name; // 세션 이름 업데이트
        echo "<script>alert('회원정보가 수정되었습니다.'); location.href='../mypage.php#profile';</script>";
    } else {
        echo "<script>alert('정보 수정 중 오류가 발생했습니다.'); history.back();</script>";
    }
} else {
    header('Location: ../mypage.php');
    exit();
}
?>