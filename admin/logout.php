session_start();
// 관리자 세션만 삭제
unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_logged_in']);

header("Location: login.php");
exit;