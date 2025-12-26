<?php
require_once '../includes/db.php';

if (!is_logged_in()) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $new_password_confirm = $_POST['new_password_confirm'] ?? '';

    // 입력값 검증
    if (empty($current_password) || empty($new_password) || empty($new_password_confirm)) {
        echo "<script>alert('모든 필드를 입력해주세요.'); history.back();</script>";
        exit();
    }

    if ($new_password !== $new_password_confirm) {
        echo "<script>alert('새 비밀번호가 일치하지 않습니다.'); history.back();</script>";
        exit();
    }

    // 현재 비밀번호 확인
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($current_password, $user['password'])) {
        // 비밀번호 업데이트
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $update_stmt->bind_param("si", $hashed_password, $user_id);

        if ($update_stmt->execute()) {
            echo "<script>alert('비밀번호가 변경되었습니다.'); location.href='../mypage.php#profile';</script>";
        } else {
            echo "<script>alert('비밀번호 변경 중 오류가 발생했습니다.'); history.back();</script>";
        }
    } else {
        echo "<script>alert('현재 비밀번호가 올바르지 않습니다.'); history.back();</script>";
    }
} else {
    header('Location: ../mypage.php');
    exit();
}
?>