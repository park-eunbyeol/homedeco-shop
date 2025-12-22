<?php
require_once 'ai_config.php';

function get_ai_recommendations($user_prefs, $products)
{
    $url = 'https://api.openai.com/v1/chat/completions';

    // Prepare context for AI
    $system_message = "You are a professional home decor stylist AI. Your goal is to recommend the best products for a user based on their preferences. You must return ONLY a JSON array of the top 4 matching product IDs.";

    $user_context = "User Preferences: " . json_encode($user_prefs, JSON_UNESCAPED_UNICODE);
    $product_list = "Available Products: " . json_encode($products, JSON_UNESCAPED_UNICODE);

    $prompt = $user_context . "\n\n" . $product_list . "\n\n" . "Return a JSON array of the 4 best Product IDs. Example: [1, 2, 3, 4]. Do not include any other text.";

    $data = [
        'model' => 'gpt-3.5-turbo', // or gpt-4o-mini if available/preferred, sticking to robust 3.5-turbo or 4o for now. 4o-mini is efficient. let's try gpt-4o-mini if the user key allows, otherwise 3.5-turbo.
        'messages' => [
            ['role' => 'system', 'content' => $system_message],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.7,
        'max_tokens' => 100
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10s timeout

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        // Log error or just return empty
        curl_close($ch);
        return [];
    }

    curl_close($ch);

    $result = json_decode($response, true);

    if (isset($result['choices'][0]['message']['content'])) {
        $content = $result['choices'][0]['message']['content'];
        // Clean markdown code blocks if present
        $content = str_replace('```json', '', $content);
        $content = str_replace('```', '', $content);
        $ids = json_decode(trim($content), true);
        if (is_array($ids)) {
            return $ids;
        }
    }

    return [];
}
?>