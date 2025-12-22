<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';

if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$notice_id = isset($data['notice_id']) ? (int) $data['notice_id'] : 0;
$title = $conn->real_escape_string($data['title'] ?? '');
$content = $conn->real_escape_string($data['content'] ?? '');
$is_important = isset($data['is_important']) ? (int) $data['is_important'] : 0;

if (empty($title) || empty($content)) {
    echo json_encode(['success' => false, 'message' => '제목과 내용을 입력해주세요.']);
    exit;
}

if ($notice_id > 0) {
    // Update
    $sql = "UPDATE notices SET title = '$title', content = '$content', is_important = $is_important WHERE notice_id = $notice_id";
} else {
    // Insert
    $sql = "INSERT INTO notices (title, content, is_important) VALUES ('$title', '$content', $is_important)";
}

if ($conn->query($sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
