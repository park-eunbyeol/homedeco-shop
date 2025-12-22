<?php
$page_title = '회원가입';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// 로그인 상태라면 메인으로 이동
if (is_logged_in()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = clean_input($_POST['email']);
    $name = clean_input($_POST['name']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    if (empty($email) || empty($name) || empty($password) || empty($confirm)) {
        $error = '모든 항목을 입력해주세요.';
    } elseif ($password !== $confirm) {
        $error = '비밀번호가 일치하지 않습니다.';
    } else {

        // 이메일 중복 체크
        $check_sql = "SELECT * FROM users WHERE email = '$email'";
        $result = $conn->query($check_sql);

        if ($result->num_rows > 0) {
            $error = '이미 사용 중인 이메일입니다.';
        } else {
            // 비밀번호 암호화 후 저장
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $insert_sql = "
                INSERT INTO users (email, name, password)
                VALUES ('$email', '$name', '$hashed')
            ";

            if ($conn->query($insert_sql)) {
                redirect('login.php?registered=success');
            } else {
                $error = '회원가입 중 오류가 발생했습니다.';
            }
        }
    }
}

require_once 'includes/header.php';
?>

<link rel="stylesheet" href="auth.css">
<div class="auth-container single">

    <div class="auth-box">
        <h1>회원가입</h1>
        <p class="auth-subtitle">HomeStyle 신규 회원 등록</p>

        <?php if ($error): ?>
            <div class="alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>이메일</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>이름</label>
                <input type="text" name="name" required>
            </div>

            <div class="form-group">
                <label>비밀번호</label>
                <input type="password" name="password" required>
            </div>

            <div class="form-group">
                <label>비밀번호 확인</label>
                <input type="password" name="confirm" required>
            </div>

            <button type="submit" class="btn-primary">회원가입</button>
        </form>



        <div class="form-footer">
            <p style="margin-top: 30px;">이미 계정이 있으신가요? <a href="login.php">로그인</a></p>
        </div>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>