<?php
// 네이버 로그인 설정
require_once '../includes/naver_api.php';

// includes/naver_api.php에 정의된 상수가 있으면 사용, 없으면 플레이스홀더 사용
$client_id = defined('NAVER_CLIENT_ID') ? NAVER_CLIENT_ID : 'YOUR_NAVER_CLIENT_ID';
$redirect_uri = urlencode('http://localhost/homedeco-shop/social/naver_callback.php');
$state = bin2hex(random_bytes(10)); // 보안을 위한 상태 토큰 생성

// 네이버 인증 URL 생성
$api_url = "https://nid.naver.com/oauth2.0/authorize?response_type=code&client_id={$client_id}&redirect_uri={$redirect_uri}&state={$state}";

header("Location: " . $api_url);
exit;
?>