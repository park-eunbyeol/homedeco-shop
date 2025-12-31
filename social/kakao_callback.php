<?php
// 카카오 로그인 콜백
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/social_config.php';

// 설정 값 (social_config.php에서 가져옴)
$client_id = '9d374a92aa6a9df86cbfcf5d19a756a2';
$redirect_uri = 'http://localhost/homedeco-shop/social/kakao_callback.php';

$code = $_GET['code'] ?? '';
$error = $_GET['error'] ?? '';

if ($error) {
    echo "<script>alert('카카오 로그인 실패: " . htmlspecialchars($error) . "'); location.href='../login.php';</script>";
    exit;
}

if (!$code) {
    echo "<script>alert('잘못된 접근입니다.'); location.href='../login.php';</script>";
    exit;
}

// 1. 토큰 발급 요청
$token_url = "https://kauth.kakao.com/oauth/token";
$params = [
    'grant_type' => 'authorization_code',
    'client_id' => $client_id,
    'redirect_uri' => $redirect_uri,
    'code' => $code
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($status_code != 200) {
    echo "<script>alert('카카오 토큰 발급 실패'); location.href='../login.php';</script>";
    exit;
}

$token_data = json_decode($response, true);
if (!isset($token_data['access_token'])) {
    echo "<script>alert('카카오 토큰 정보 오류'); location.href='../login.php';</script>";
    exit;
}

$access_token = $token_data['access_token'];

// 2. 사용자 정보 요청
$user_url = "https://kapi.kakao.com/v2/user/me";
$header = "Authorization: Bearer " . $access_token;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $user_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));
$user_response = curl_exec($ch);
$user_status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($user_status_code != 200) {
    echo "<script>alert('사용자 정보 조회 실패'); location.href='../login.php';</script>";
    exit;
}

$user_data = json_decode($user_response, true);
$kakao_id = $user_data['id'] ?? ''; // 카카오 고유 번호
$kakao_account = $user_data['kakao_account'] ?? [];
$properties = $user_data['properties'] ?? [];

$email = $kakao_account['email'] ?? '';
$nickname = $properties['nickname'] ?? '카카오사용자';

// 이메일 정보가 없는 경우 카카오 고유 ID를 활용해 가상 이메일 생성
if (empty($email) && !empty($kakao_id)) {
    $email = $kakao_id . "@kakao.user";
}

if (empty($email)) {
    echo "<script>alert('사용자 정보를 가져올 수 없습니다. 다시 시도해주세요.'); location.href='../login.php';</script>";
    exit;
}

// 3. DB 조회 및 로그인 처리
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // 로그인 처리
    $user = $result->fetch_assoc();

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['is_social'] = true;
    $_SESSION['social_provider'] = 'kakao';

    redirect('../index.php');
} else {
    // 자동 회원가입
    $password = bin2hex(random_bytes(16));
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (email, name, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $nickname, $hashed_password);

    if ($stmt->execute()) {
        $new_user_id = $conn->insert_id;

        $_SESSION['user_id'] = $new_user_id;
        $_SESSION['user_email'] = $email;
        $_SESSION['name'] = $nickname;
        $_SESSION['is_social'] = true;
        $_SESSION['social_provider'] = 'kakao';

        redirect('../index.php');
    } else {
        echo "<script>alert('회원가입 처리 중 오류가 발생했습니다.'); location.href='../login.php';</script>";
        exit;
    }
}
?>