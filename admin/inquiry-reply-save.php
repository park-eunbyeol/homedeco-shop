<?php
require_once '../includes/db.php';

// 관리자 권한 확인
if (!is_admin()) {
    // redirect('../index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inquiry_id = (int) $_POST['inquiry_id'];
    $reply_message = clean_input($_POST['reply_message']);
    $close_inquiry = isset($_POST['close_inquiry']) ? 1 : 0;
    $admin_id = $_SESSION['user_id']; // 관리자의 user_id 사용

    // 트랜잭션 시작 (선택 사항이지만 안전함)
    $conn->begin_transaction();

    try {
        // 이미 답변이 있는지 확인
        $check_sql = "SELECT reply_id FROM inquiry_replies WHERE inquiry_id = $inquiry_id";
        $check_result = $conn->query($check_sql);

        if ($check_result && $check_result->num_rows > 0) {
            // 답변 수정
            $save_sql = "UPDATE inquiry_replies 
                        SET reply_message = '$reply_message', created_at = NOW(), admin_id = $admin_id 
                        WHERE inquiry_id = $inquiry_id";
        } else {
            // 답변 신규 등록
            $save_sql = "INSERT INTO inquiry_replies (inquiry_id, admin_id, reply_message, created_at) 
                        VALUES ($inquiry_id, $admin_id, '$reply_message', NOW())";
        }

        if (!$conn->query($save_sql)) {
            throw new Exception("답변 저장 실패");
        }

        // 문의 상태 업데이트
        $new_status = $close_inquiry ? 'closed' : 'answered';
        $update_status_sql = "UPDATE inquiries SET status = '$new_status' WHERE inquiry_id = $inquiry_id";

        if (!$conn->query($update_status_sql)) {
            throw new Exception("문의 상태 업데이트 실패");
        }

        $conn->commit();
        echo "<script>alert('답변이 저장되었습니다.'); location.href='inquiry-view.php?id=$inquiry_id';</script>";

    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('오류가 발생했습니다: " . $e->getMessage() . "'); history.back();</script>";
    }
} else {
    redirect('inquiries-manage.php');
}
