<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';

// 이미 로그인 상태면 메인으로 이동
if (is_logged_in()) {
    redirect('index.php');
}

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

require_once 'includes/header.php';
?>

<link rel="stylesheet" href="css/auth.css">

<div class="auth-container single">
    <style>
        .auth-tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
        }

        .auth-tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            font-weight: 600;
            color: #888;
            transition: all 0.3s;
        }

        .auth-tab.active {
            color: #3b7ddd;
            border-bottom: 2px solid #3b7ddd;
        }

        .auth-content {
            display: none;
        }

        .auth-content.active {
            display: block;
        }
    </style>

    <div class="auth-box">
        <div class="auth-tabs">
            <div class="auth-tab active" onclick="switchTab('member')">회원 로그인</div>
            <div class="auth-tab" onclick="switchTab('guest')">비회원 주문조회</div>
        </div>

        <!-- 회원 로그인 -->
        <div id="member-login" class="auth-content active">
            <h1>로그인</h1>
            <p class="auth-subtitle">COZY-DECO에 오신 것을 환영합니다</p>

            <?php if ($error): ?>
                <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="login_process.php">
                <div class="form-group">
                    <label>이메일</label>
                    <input type="email" name="email" required>
                </div>

                <div class="form-group">
                    <label>비밀번호</label>
                    <input type="password" name="password" required>
                </div>

                <button class="btn-primary">로그인</button>
            </form>

            <div class="social-login-box">
                <p class="social-title">또는 소셜 계정으로 로그인</p>
                <div class="social-btn-wrap">
                    <a href="social/kakao_login.php" class="social-btn kakao">카카오로 로그인</a>
                    <a href="social/naver_login.php" class="social-btn naver">네이버로 로그인</a>
                </div>
            </div>

            <div class="form-footer">
                <p style="margin-top: 30px;">계정이 없으신가요? <a href="register.php">회원가입</a></p>
            </div>
        </div>

        <!-- 비회원 주문조회 -->
        <div id="guest-lookup" class="auth-content">
            <h1>비회원 주문조회</h1>
            <p class="auth-subtitle">주문번호와 연락처를 입력해주세요</p>

            <form method="POST" action="guest-order-lookup.php">
                <div class="form-group">
                    <label>주문번호</label>
                    <input type="text" name="order_id" placeholder="숫자만 입력 (예: 123)" required>
                </div>

                <div class="form-group">
                    <label>연락처</label>
                    <input type="tel" name="phone" placeholder="010-0000-0000" required>
                </div>

                <button type="submit" class="btn-primary" style="background: #2c3e50;">조회하기</button>
            </form>

            <div class="form-footer" style="margin-top: 40px;">
                <p>회원이 되시면 더 많은 혜택을 받으실 수 있습니다.</p>
                <a href="register.php" class="btn-primary"
                    style="display: block; background: #fff; color: #333; border: 1px solid #ddd; text-decoration: none; text-align: center; margin-top: 10px;">회원가입하기</a>
            </div>
        </div>
    </div>

    <script>
        function switchTab(type) {
            document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.auth-content').forEach(c => c.classList.remove('active'));

            if (type === 'member') {
                document.querySelector('.auth-tab:first-child').classList.add('active');
                document.getElementById('member-login').classList.add('active');
            } else {
                document.querySelector('.auth-tab:last-child').classList.add('active');
                document.getElementById('guest-lookup').classList.add('active');
            }
        }
    </script>
</div>

<?php require_once 'includes/footer.php'; ?>