<?php
// 네이버 쇼핑 API 설정 (검색 > 쇼핑)
define('NAVER_CLIENT_ID', '9QPicDmAceT5m9YsfvkA');
define('NAVER_CLIENT_SECRET', 'iuJpzpqNLk');

/**
 * 네이버 쇼핑 API로 상품 검색
 * @param string $query 검색어
 * @param int $display 검색 결과 개수 (기본 10, 최대 100)
 * @return array 상품 정보 배열
 */
function naver_shopping_search($query, $display = 10)
{
    $client_id = NAVER_CLIENT_ID;
    $client_secret = NAVER_CLIENT_SECRET;

    $encText = urlencode($query);
    $url = "https://openapi.naver.com/v1/search/shop.json?query=" . $encText . "&display=" . $display;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 로컬 개발 환경용
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 로컬 개발 환경용
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "X-Naver-Client-Id: " . $client_id,
        "X-Naver-Client-Secret: " . $client_secret
    ));

    $response = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // 디버깅용 로그
    if ($status_code != 200) {
        error_log("네이버 API 오류 - 상태코드: " . $status_code);
        error_log("네이버 API 응답: " . $response);
        if ($curl_error) {
            error_log("cURL 오류: " . $curl_error);
        }
        return [];
    }

    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON 파싱 오류: " . json_last_error_msg());
        return [];
    }

    return $result['items'] ?? [];
}

/**
 * 카테고리별 네이버 쇼핑 상품 가져오기
 * @param int $category_id 카테고리 ID
 * @param int $limit 가져올 상품 개수
 * @return array 상품 정보 배열
 */
function get_naver_products_by_category($category_id, $limit = 3)
{
    // 카테고리별 검색 키워드 매핑
    $category_keywords = [
        1 => '거실 인테리어 소파',
        2 => '침실 인테리어 침대',
        3 => '주방 인테리어 식탁',
        4 => '조명 인테리어 무드등',
        5 => '인테리어 소품 장식'
    ];

    $keyword = $category_keywords[$category_id] ?? '홈데코 인테리어';
    $products = naver_shopping_search($keyword, $limit);

    // 상품 정보 포맷팅
    $formatted_products = [];
    foreach ($products as $product) {
        $formatted_products[] = [
            'name' => strip_tags($product['title']),
            'price' => (int) $product['lprice'],
            'main_image' => $product['image'],
            'link' => $product['link'],
            'brand' => $product['brand'] ?? '',
            'category' => $product['category1'] ?? ''
        ];
    }

    return $formatted_products;
}

/**
 * 검색어로 네이버 쇼핑 상품 가져오기
 * @param string $query 검색어
 * @param int $limit 가져올 상품 개수
 * @return array 상품 정보 배열
 */
function search_naver_products($query, $limit = 10)
{
    $products = naver_shopping_search($query, $limit);

    $formatted_products = [];
    foreach ($products as $product) {
        $formatted_products[] = [
            'name' => strip_tags($product['title']),
            'price' => (int) $product['lprice'],
            'main_image' => $product['image'],
            'link' => $product['link'],
            'brand' => $product['brand'] ?? '',
            'category' => $product['category1'] ?? ''
        ];
    }

    return $formatted_products;
}
?>