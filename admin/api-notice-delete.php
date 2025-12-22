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

if ($notice_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$sql = "DELETE FROM notices WHERE notice_id = $notice_id";

if ($conn->query($sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
