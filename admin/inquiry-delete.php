<?php
require_once '../includes/db.php';

// 관리자 권한 확인
if (!is_admin()) {
    // redirect('../index.php');
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $sql = "DELETE FROM inquiries WHERE inquiry_id = $id";
    if ($conn->query($sql)) {
        echo "<script>alert('문의글이 삭제되었습니다.'); location.href='inquiries-manage.php';</script>";
    } else {
        echo "<script>alert('삭제 중 오류가 발생했습니다.'); history.back();</script>";
    }
} else {
    redirect('inquiries-manage.php');
}
