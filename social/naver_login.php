<?php
// 네이버 로그인 설정
require_once '../includes/social_config.php';

$client_id = NAVER_CLIENT_ID;
$redirect_uri = urlencode(NAVER_REDIRECT_URI);
$state = bin2hex(random_bytes(10)); // 보안을 위한 상태 토큰 생성

// 네이버 인증 URL 생성
$api_url = "https://nid.naver.com/oauth2.0/authorize?response_type=code&client_id={$client_id}&redirect_uri={$redirect_uri}&state={$state}&auth_type=reprompt";

header("Location: " . $api_url);
exit;
?>