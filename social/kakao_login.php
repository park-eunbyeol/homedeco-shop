<?php
// 카카오 로그인 설정
// 카카오 개발자 센터(https://developers.kakao.com)에서 REST API 키를 발급받아 입력해주세요.
$client_id = 'YOUR_KAKAO_REST_API_KEY';
$redirect_uri = 'http://localhost/homedeco-shop/social/kakao_callback.php';

// 카카오 인증 URL 생성
$kakao_oauth_url = "https://kauth.kakao.com/oauth/authorize?client_id={$client_id}&redirect_uri=" . urlencode($redirect_uri) . "&response_type=code";

header("Location: " . $kakao_oauth_url);
exit;
?>