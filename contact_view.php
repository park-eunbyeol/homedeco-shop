<?php
require_once 'includes/db.php';

if (!isset($_GET['id'])) {
    echo "<script>alert('잘못된 접근입니다.'); history.back();</script>";
    exit;
}

$id = (int) $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM inquiries WHERE inquiry_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>alert('존재하지 않는 게시글입니다.'); history.back();</script>";
    exit;
}

$post = $result->fetch_assoc();

// 접근 권한 체크 (비밀글)
$has_access = false;
if (!$post['is_private']) {
    $has_access = true;
} else {
    // 비밀글인 경우
    if (is_logged_in()) {
        if (is_admin() || $_SESSION['user_id'] == $post['user_id']) {
            $has_access = true;
        }
    }

    // 비밀번호 입력 확인 (세션 or POST)
    if (!$has_access && isset($_POST['view_password'])) {
        if ($post['password'] && password_verify($_POST['view_password'], $post['password'])) {
            $has_access = true;
        } else {
            $password_error = "비밀번호가 일치하지 않습니다.";
        }
    }
}

$page_title = htmlspecialchars($post['subject']);
require_once 'includes/header.php';
?>

<div class="container board-container" style="max-width: 800px;">
    <?php if (!$has_access): ?>
        <!-- 비밀번호 입력 폼 -->
        <div
            style="max-width: 400px; margin: 100px auto; text-align: center; padding: 40px; background: #fafafa; border-radius: 12px; border: 1px solid #eee;">
            <i class="fas fa-lock" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
            <h3 style="margin-bottom: 20px;">비밀글입니다</h3>
            <p style="margin-bottom: 20px; color: #666;">작성자와 관리자만 열람할 수 있습니다.<br>비회원인 경우 비밀번호를 입력해주세요.</p>

            <?php if (isset($password_error)): ?>
                <p style="color: red; margin-bottom: 15px;"><?php echo $password_error; ?></p>
            <?php endif; ?>

            <form method="post">
                <input type="password" name="view_password" placeholder="비밀번호 입력" style="margin-bottom: 10px;" required>
                <button type="submit" class="btn btn-primary btn-block">확인</button>
            </form>
            <div style="margin-top: 20px;">
                <a href="contact.php" class="btn btn-outline">목록으로</a>
            </div>
        </div>
    <?php else: ?>
        <!-- 게시글 보기 -->
        <div class="board-header"
            style="border-bottom: 2px solid var(--primary-color); padding-bottom: 20px; flex-direction: column; align-items: flex-start; gap: 10px;">
            <h2 class="board-title" style="font-size: 24px;">
                <?php if ($post['is_private']): ?><i class="fas fa-lock lock-icon"></i><?php endif; ?>
                <?php echo htmlspecialchars($post['subject']); ?>
            </h2>
            <div style="display: flex; gap: 15px; color: #666; font-size: 14px;">
                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($post['name']); ?></span>
                <span><i class="far fa-clock"></i> <?php echo date('Y-m-d H:i', strtotime($post['created_at'])); ?></span>
            </div>
        </div>

        <div class="board-content"
            style="padding: 40px 10px; min-height: 200px; border-bottom: 1px solid #eee; line-height: 1.8;">
            <?php echo nl2br(htmlspecialchars($post['message'])); ?>
        </div>

        <?php
        // 관리자 답변 확인
        $reply_sql = "SELECT r.*, u.name as admin_name 
                      FROM inquiry_replies r 
                      LEFT JOIN users u ON r.admin_id = u.user_id 
                      WHERE r.inquiry_id = ?";
        $reply_stmt = $conn->prepare($reply_sql);
        $reply_stmt->bind_param("i", $id);
        $reply_stmt->execute();
        $reply_result = $reply_stmt->get_result();

        if ($reply_result->num_rows > 0):
            $reply = $reply_result->fetch_assoc();
            ?>
            <div class="admin-reply-box"
                style="margin-top: 30px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 25px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <span
                        style="background: var(--primary-color); color: white; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">관리자
                        답변</span>
                    <span
                        style="color: #666; font-size: 13px;"><?php echo date('Y-m-d H:i', strtotime($reply['created_at'])); ?></span>
                </div>
                <div style="line-height: 1.8; color: #374151;">
                    <?php echo nl2br(htmlspecialchars($reply['reply_message'])); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="board-footer" style="padding-top: 30px; justify-content: space-between;">
            <a href="contact.php" class="btn btn-outline">목록으로</a>

            <?php if ((is_logged_in() && $_SESSION['user_id'] == $post['user_id']) || is_admin()): ?>
                <!-- 수정/삭제 버튼 (기능은 추후 구현) -->
                <!-- <div style="display: flex; gap: 10px;">
                <button class="btn btn-outline">수정</button>
                <button class="btn btn-outline" style="color: var(--danger-color); border-color: var(--danger-color);">삭제</button>
            </div> -->
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>