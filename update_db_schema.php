<?php
require_once 'includes/db.php';

// orders 테이블에 payment_key 컬럼 추가
try {
    $sql = "ALTER TABLE orders ADD COLUMN payment_key VARCHAR(255) NULL AFTER total_amount";
    if ($conn->query($sql) === TRUE) {
        echo "Successfully added 'payment_key' column to 'orders' table.<br>";
    } else {
        // 이미 존재할 수도 있으므로 오류 메시지 확인
        if (strpos($conn->error, "Duplicate column name") !== false) {
            echo "'payment_key' column already exists.<br>";
        } else {
            echo "Error adding column: " . $conn->error . "<br>";
        }
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
?>