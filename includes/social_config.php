<?php
// 도메인 감지 (로컬 vs 닷홈)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];

// ghjrodf.dothome.co.kr 도메인이거나 localhost인 경우 /homedeco-shop 경로 추가
$is_homedeco_dir = (strpos($host, 'localhost') !== false || strpos($host, 'ghjrodf.dothome.co.kr') !== false);
$base_dir = $is_homedeco_dir ? '/homedeco-shop' : '';

$base_url = $protocol . $host . $base_dir;

// 카카오 설정
define('KAKAO_REST_API_KEY', '9d374a92aa6a9df86cbfcf5d19a756a2');
define('KAKAO_REDIRECT_URI', $base_url . '/social/kakao_callback.php');

// 네이버 설정
define('NAVER_CLIENT_ID', '9QPicDmAceT5m9YsfvkA');
define('NAVER_CLIENT_SECRET', 'iuJpzpqNLk');
define('NAVER_REDIRECT_URI', $base_url . '/social/naver_callback.php');
?>