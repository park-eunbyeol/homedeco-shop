<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/payment_config.php'; // 토스 키
require_once '../includes/NotificationService.php'; // 알림 서비스

// POST 데이터 수신
$data = json_decode(file_get_contents('php://input'), true);
$order_id = $data['order_id'] ?? 0;

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => '잘못된 요청']);
    exit;
}

// 주문 정보 및 결제키 조회
$stmt = $conn->prepare("SELECT payment_key, total_amount, status FROM orders WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo json_encode(['success' => false, 'message' => '주문을 찾을 수 없습니다.']);
    exit;
}

if ($order['status'] !== 'paid') {
    echo json_encode(['success' => false, 'message' => '이미 취소되었거나 결제 완료 상태가 아닙니다.']);
    exit;
}

if (empty($order['payment_key'])) {
    echo json_encode(['success' => false, 'message' => '결제 키(paymentKey)가 없어 취소할 수 없습니다.']);
    exit;
}

$paymentKey = $order['payment_key'];
$cancelReason = "관리자 취소";

// 1. 토스페이먼츠 취소 API 호출
$url = "https://api.tosspayments.com/v1/payments/" . $paymentKey . "/cancel";
$credential = base64_encode($toss_secret_key . ":"); // Secret Key 인코딩

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode(['cancelReason' => $cancelReason]),
    CURLOPT_HTTPHEADER => [
        "Authorization: Basic " . $credential,
        "Content-Type: application/json"
    ],
]);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    echo json_encode(['success' => false, 'message' => 'cURL Error: ' . $err]);
    exit;
}

// 2. 응답 처리
if ($http_code == 200) {
    // 취소 성공
    // DB 업데이트
    $update_stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ?");
    $update_stmt->bind_param("i", $order_id);
    $update_stmt->execute();

    // 3. 취소 알림 문자 발송
    $notifier = new NotificationService();
    // 테스트용 번호 가져오기
    $test_phone = '010-9450-1509';
    if (file_exists('../includes/solapi_config.php')) {
        include '../includes/solapi_config.php';
        if (!empty($solapi_sender_phone))
            $test_phone = $solapi_sender_phone;
    }

    // 취소 알림 문자 발송
    $notifier->sendCancelMessage($test_phone, $order_id);

    // 성공 응답
    echo json_encode(['success' => true]);
} else {
    // 취소 실패
    $resObj = json_decode($response);
    $msg = $resObj->message ?? '알 수 없는 오류';
    echo json_encode(['success' => false, 'message' => '토스 응답 오류: ' . $msg]);
}
?>