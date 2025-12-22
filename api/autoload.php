mf
<?php
require 'vendor/autoload.php';
use OpenAI\Client;

$ai_products_list = []; // 반드시 초기화

$apiKey = 'YOUR_API_KEY_HERE';
$client = new Client(['api_key' => $apiKey]);

try {
    $response = $client->chat()->create([
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'user', 'content' => '홈데코용 가구 추천 4가지 목록을 **순수 JSON 배열**로 만들어주세요. 각 가구마다 반드시 "name", "material", "color", "description", "image_url" 포함. JSON 외 텍스트는 절대 포함하지 말 것.']
        ],
        'max_tokens' => 500
    ]);

    $ai_response = $response['choices'][0]['message']['content'];

    $decoded = json_decode($ai_response, true);
    if (is_array($decoded)) {
        $ai_products_list = $decoded;
    } else {
        throw new Exception("AI JSON 파싱 실패, 기본 샘플 사용");
    }

    // 이미지 다운로드 폴더
    $imgDir = __DIR__ . '/images/ai/';
    if (!is_dir($imgDir))
        mkdir($imgDir, 0777, true);

    foreach ($ai_products_list as &$item) {
        if (!empty($item['image_url'])) {
            $imgData = @file_get_contents($item['image_url']);
            if ($imgData !== false) {
                $fileName = preg_replace('/[^a-zA-Z0-9_-]/', '', $item['name']) . '.jpg';
                $filePath = $imgDir . $fileName;
                file_put_contents($filePath, $imgData);
                $item['local_image'] = 'images/ai/' . $fileName;
            } else {
                $item['local_image'] = 'images/default.png';
            }
        } else {
            $item['local_image'] = 'images/default.png';
        }
    }

} catch (\Exception $e) {
    // API 실패 시 기본 샘플 데이터 제거 (유저 요청)
    $ai_products_list = [];
}
