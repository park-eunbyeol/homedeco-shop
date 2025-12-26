<?php
// 네이버 로그인 콜백
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/naver_api.php';

// 설정 값 가져오기
$client_id = defined('NAVER_CLIENT_ID') ? NAVER_CLIENT_ID : '9QPicDmAceT5m9YsfvkA';
$client_secret = defined('NAVER_CLIENT_SECRET') ? NAVER_CLIENT_SECRET : 'iuJpzpqNLk';

$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';
$error = $_GET['error'] ?? '';
$error_description = $_GET['error_description'] ?? '';

if ($error) {
    echo "<script>alert('네이버 로그인 실패: " . htmlspecialchars($error_description) . "'); location.href='../login.php';</script>";
    exit;
}

if (!$code) {
    echo "<script>alert('잘못된 접근입니다.'); location.href='../login.php';</script>";
    exit;
}

// 1. 액세스 토큰 발급 요청
$redirect_uri = urlencode('http://localhost/homedeco-shop/social/naver_callback.php');
$token_url = "https://nid.naver.com/oauth2.0/token?grant_type=authorization_code&client_id={$client_id}&client_secret={$client_secret}&redirect_uri={$redirect_uri}&code={$code}&state={$state}";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($status_code != 200) {
    echo "<script>alert('네이버 토큰 발급 실패'); location.href='../login.php';</script>";
    exit;
}

$token_data = json_decode($response, true);
if (!isset($token_data['access_token'])) {
    echo "<script>alert('네이버 토큰 정보 오류'); location.href='../login.php';</script>";
    exit;
}

$access_token = $token_data['access_token'];

// 2. 사용자 프로필 정보 요청
$profile_url = "https://openapi.naver.com/v1/nid/me";
$header = "Bearer " . $access_token;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $profile_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: ' . $header));
$profile_response = curl_exec($ch);
$profile_status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($profile_status_code != 200) {
    echo "<script>alert('사용자 정보 조회 실패'); location.href='../login.php';</script>";
    exit;
}

$profile_data = json_decode($profile_response, true);
$naver_user = $profile_data['response'];

$email = $naver_user['email'] ?? '';
$name = $naver_user['name'] ?? '네이버사용자';
$mobile = $naver_user['mobile'] ?? '';

if (empty($email)) {
    echo "<script>alert('이메일 정보가 필요합니다. 동의항목을 확인해주세요.'); location.href='../login.php';</script>";
    exit;
}

// 3. DB 조회 및 로그인 처리
// 이메일로 기존 회원 확인
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // 이미 가입된 회원 -> 로그인 처리
    $user = $result->fetch_assoc();

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['is_social'] = true;
    $_SESSION['social_provider'] = 'naver';

    // 30일간 유지되는 쿠키 설정 (로그인 유지)
    setcookie('remember_token', $user['user_id'], time() + (86400 * 30), '/', '', false, true);

    echo "<script>
        window.opener.location.href = '../index.php';
        window.close();
    </script>";
    exit;
} else {
    // 신규 회원 -> 자동 가입 처리
    // 비밀번호는 랜덤 생성 (로그인할 수 없도록 복잡하게)
    $password = bin2hex(random_bytes(16));
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (email, name, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $name, $hashed_password);

    if ($stmt->execute()) {
        // 가입 성공 후 즉시 로그인
        $new_user_id = $conn->insert_id;

        $_SESSION['user_id'] = $new_user_id;
        $_SESSION['user_email'] = $email;
        $_SESSION['name'] = $name;
        $_SESSION['is_social'] = true;
        $_SESSION['social_provider'] = 'naver';

        // 30일간 유지되는 쿠키 설정 (로그인 유지)
        setcookie('remember_token', $new_user_id, time() + (86400 * 30), '/', '', false, true);

        echo "<script>
            window.opener.location.href = '../index.php';
            window.close();
        </script>";
        exit;
    } else {
        echo "<script>alert('회원가입 처리 중 오류가 발생했습니다.'); location.href='../login.php';</script>";
        exit;
    }
}
?>