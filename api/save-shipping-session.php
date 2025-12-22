<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if ($input) {
    $_SESSION['temp_shipping'] = $input;
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'No data received']);
}
