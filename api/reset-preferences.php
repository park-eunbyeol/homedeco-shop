<?php
// api/reset-preferences.php
require_once '../includes/db.php';

if (!is_logged_in()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];

$sql = "DELETE FROM user_preferences WHERE user_id = $user_id";
if ($conn->query($sql)) {
    $_SESSION['success'] = '취향 설정이 초기화되었습니다.';
}

redirect('../ai-recommend.php');
?>