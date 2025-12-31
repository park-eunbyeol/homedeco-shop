<?php
// 도메인 감지 (로컬 vs 닷홈)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];

// 현재 경로 자동 감지 (예: /homedeco-shop/social/kakao_login.php -> /homedeco-shop)
$current_path = $_SERVER['REQUEST_URI'];
$base_dir = (strpos($host, 'localhost') !== false) ? '/homedeco-shop' : '';

$base_url = $protocol . $host . $base_dir;

// 카카오 설정
define('KAKAO_REST_API_KEY', '9d374a92aa6a9df86cbfcf5d19a756a2');
define('KAKAO_REDIRECT_URI', $base_url . '/social/kakao_callback.php');

// 네이버 설정
define('NAVER_CLIENT_ID', '9QPicDmAceT5m9YsfvkA');
define('NAVER_CLIENT_SECRET', 'iuJpzpqNLk');
define('NAVER_REDIRECT_URI', $base_url . '/social/naver_callback.php');
?>