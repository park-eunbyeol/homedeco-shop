<?php
require_once 'includes/db.php';

// 폼 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = clean_input($_POST['title']);
    $content = clean_input($_POST['content']);
    $email = isset($_POST['email']) ? clean_input($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? clean_input($_POST['phone']) : '';
    $is_private = isset($_POST['is_private']) ? 1 : 0;

    // 유효성 검사
    if (empty($title) || empty($content)) {
        $error = "제목과 내용을 입력해주세요.";
    } else {
        if (is_logged_in()) {
            $user_id = $_SESSION['user_id'];
            $name = $_SESSION['name'];
            // 로그인한 사용자의 이메일 가져오기
            if (empty($email)) {
                $user_sql = "SELECT email FROM users WHERE user_id = $user_id";
                $user_result = $conn->query($user_sql);
                if ($user_result && $user_result->num_rows > 0) {
                    $user_data = $user_result->fetch_assoc();
                    $email = $user_data['email'];
                }
            }
        } else {
            $user_id = null;
            $name = clean_input($_POST['name']);

            if (empty($name)) {
                $error = "이름을 입력해주세요.";
            }
            if (empty($email)) {
                $error = "이메일을 입력해주세요.";
            }
        }
    }

    if (!isset($error)) {
        $stmt = $conn->prepare("INSERT INTO inquiries (user_id, name, email, phone, subject, message, is_private) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssi", $user_id, $name, $email, $phone, $title, $content, $is_private);

        if ($stmt->execute()) {
            echo "<script>alert('문의가 등록되었습니다.'); location.href='contact.php';</script>";
            exit;
        } else {
            $error = "등록 중 오류가 발생했습니다: " . $conn->error;
        }
    }
}

$page_title = '문의 작성';
require_once 'includes/header.php';
?>

<div class="container board-container" style="max-width: 800px;">
    <div class="board-header">
        <h2 class="board-title">문의 작성</h2>
    </div>

    <form action="" method="post" class="contact-form">
        <?php if (isset($error)): ?>
            <div style="padding: 15px; background: #f8d7da; color: #721c24; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">작성자</label>
            <?php if (is_logged_in()): ?>
                <input type="text" value="<?php echo $_SESSION['name']; ?> (회원)" disabled style="background: #f1f1f1;">
            <?php else: ?>
                <input type="text" name="name" placeholder="이름" required>
            <?php endif; ?>
        </div>

        <?php if (!is_logged_in()): ?>
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">이메일 *</label>
                <input type="email" name="email" placeholder="example@email.com" required>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">전화번호</label>
                <input type="tel" name="phone" placeholder="010-1234-5678">
            </div>
        <?php endif; ?>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">제목</label>
            <input type="text" name="title" placeholder="제목을 입력하세요" required>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">내용</label>
            <textarea name="content" rows="10" placeholder="문의 내용을 입력하세요" required></textarea>
        </div>

        <div style="margin-bottom: 30px; display: flex; align-items: center; gap: 8px;">
            <input type="checkbox" name="is_private" id="is_private" value="1">
            <label for="is_private" style="cursor: pointer; user-select: none;">🔒 비밀글로 작성 (관리자만 확인 가능)</label>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <a href="contact.php" class="btn btn-outline">취소</a>
            <button type="submit" class="btn btn-primary">등록하기</button>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>