<?php
// 알림톡 발송 모듈 (Mockup)
// 실제 사용 시 Solapi/Aligo 등의 SDK를 설치하여 구현합니다.

class NotificationService
{
    private $apiKey;
    private $apiSecret;
    private $senderPhone;
    private $apiUrl = 'https://api.solapi.com/messages/v4/send';

    public function __construct()
    {
        // 설정 파일 로드
        if (file_exists(__DIR__ . '/solapi_config.php')) {
            require __DIR__ . '/solapi_config.php';
            $this->apiKey = $solapi_api_key ?? '';
            $this->apiSecret = $solapi_api_secret ?? '';
            $this->senderPhone = $solapi_sender_phone ?? '';
        } else {
            // 설정 파일이 없을 경우 로그 남기기 or 처리
            $this->apiKey = '';
            $this->apiSecret = '';
            $this->senderPhone = '';
        }
    }

    /**
     * 주문 완료 알림 문자(LMS) 발송
     */
    public function sendOrderComplete($to, $orderName, $orderId, $amount)
    {
        // 문자 내용 구성
        $message = "안녕하세요 고객님!\n\n";
        $message .= "주문이 정상적으로 완료되었습니다.\n";
        $message .= "꼼꼼히 챙겨서 빠르게 보내드릴게요! 😊\n\n";
        $message .= "[주문 정보]\n";
        $message .= "● 주문번호 : {$orderId}\n";
        $message .= "● 상품명 : {$orderName}\n";
        $message .= "● 결제금액 : " . number_format($amount) . "원\n\n";
        $message .= "배송이 시작되면 다시 안내드리겠습니다.\n";
        $message .= "감사합니다.";

        // 1. 로그 기록 (항상 남김)
        $this->logMessage($to, $message);

        // 2. 실제 문자 발송 (키가 설정된 경우에만)
        if (!empty($this->apiKey) && !empty($this->senderPhone)) {
            return $this->sendRealMessage($to, $message);
        }

        return true;
    }

    /**
     * 주문 취소 알림 문자 발송
     */
    public function sendCancelMessage($to, $orderId)
    {
        $message = "안녕하세요 고객님,\n";
        $message .= "주문(주문번호: {$orderId})의 결제 취소가 정상적으로 완료되었습니다.\n";
        $message .= "환불은 카드사 정책에 따라 영업일 기준 3~5일 소요될 수 있습니다.\n";
        $message .= "감사합니다.";

        $this->logMessage($to, $message);

        if (!empty($this->apiKey) && !empty($this->senderPhone)) {
            return $this->sendRealMessage($to, $message);
        }
        return true;
    }

    /**
     * 실제 솔라피 API를 호출하여 문자 발송
     */
    private function sendRealMessage($to, $text)
    {
        // 특수문자 제거 후 숫자만 남김
        $to = preg_replace('/[^0-9]/', '', $to);
        $from = preg_replace('/[^0-9]/', '', $this->senderPhone);

        $date = date('Y-m-d\TH:i:s.u\Z');
        $salt = uniqid();
        $signature = hash_hmac('sha256', $date . $salt, $this->apiSecret);
        $auth = "HMAC-SHA256 apiKey={$this->apiKey}, date={$date}, salt={$salt}, signature={$signature}";

        $fields = new stdClass();
        $message = new stdClass();
        $message->to = $to;
        $message->from = $from;
        $message->text = $text;

        // 제목이 있으면 좋음 (LMS일 경우)
        $message->subject = '[홈데코샵] 주문완료 안내';

        $fields->message = $message;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: " . $auth,
            "Content-Type: application/json"
        ));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            $this->logMessage('SYSTEM', "CURL Error: " . $err);
            return false;
        }

        // 응답 로그
        $this->logMessage('SYSTEM', "API Response: " . $response);
        return true;
    }

    private function logMessage($to, $msg)
    {
        $logFile = __DIR__ . '/../logs/notification.log';
        if (!is_dir(dirname($logFile)))
            mkdir(dirname($logFile), 0777, true);
        $logEntry = "[" . date('Y-m-d H:i:s') . "] To: {$to} | Msg: {$msg}\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}
?>