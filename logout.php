<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// 로그아웃 함수 호출 (세션 및 쿠키 삭제)
logout();

// 메인 페이지로 리다이렉트
redirect('index.php');
?>