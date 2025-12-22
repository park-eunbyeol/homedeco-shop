<?php
// api/save-preferences.php
require_once '../includes/db.php';

// 로그인 확인
if (!is_logged_in()) {
    redirect('../login.php');
    exit;
}

// POST 요청 확인
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    redirect('../ai-recommend.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$style = clean_input($_POST['style_preference']);
$color = clean_input($_POST['color_preference']);
$room = clean_input($_POST['room_preference']);
$price_range = clean_input($_POST['price_range']);

// 기존 취향 정보 확인
$check_sql = "SELECT preference_id FROM user_preferences WHERE user_id = $user_id";
$check_result = $conn->query($check_sql);

if ($check_result && $check_result->num_rows > 0) {
    // 업데이트
    $sql = "UPDATE user_preferences SET 
            style_preference = '$style',
            color_preference = '$color',
            room_preference = '$room',
            price_range = '$price_range'
            WHERE user_id = $user_id";
} else {
    // 새로 삽입
    $sql = "INSERT INTO user_preferences (user_id, style_preference, color_preference, room_preference, price_range) 
            VALUES ($user_id, '$style', '$color', '$room', '$price_range')";
}

if ($conn->query($sql)) {
    $_SESSION['success'] = 'AI 취향 분석이 완료되었습니다!';
} else {
    $_SESSION['error'] = '저장 중 오류가 발생했습니다: ' . $conn->error;
    error_log("DB Error: " . $conn->error);
}

redirect('../ai-recommend.php');
?>