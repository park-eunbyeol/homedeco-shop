<?php
// 카카오 로그인 설정
require_once '../includes/social_config.php';

// 카카오 인증 URL 생성
$kakao_oauth_url = "https://kauth.kakao.com/oauth/authorize?client_id=" . KAKAO_REST_API_KEY . "&redirect_uri=" . urlencode(KAKAO_REDIRECT_URI) . "&response_type=code";

header("Location: " . $kakao_oauth_url);
exit;
?>