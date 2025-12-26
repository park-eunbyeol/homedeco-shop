<?php
require_once '../includes/db.php';
require_once '../includes/header.php';
?>

<div class="auth-page">
    <div class="auth-container">
        <div class="auth-header">
            <h2>관리자 로그인</h2>
            <?php if (isset($_GET['error'])): ?>
                <div
                    style="background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 8px; font-size: 14px; margin-top: 15px; text-align: center; font-weight: 600;">
                    <?php if ($_GET['error'] == '1'): ?>
                        이메일 또는 비밀번호가 올바르지 않거나 관리자 계정이 아닙니다.
                    <?php else: ?>
                        접근 권한이 없습니다. 관리자 로그인이 필요합니다.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <form action="login_process.php" method="post" class="auth-form">
            <div class="form-group">
                <label>이메일</label>
                <input type="email" name="email" placeholder="admin@homedeco.com" required>
            </div>

            <div class="form-group">
                <label>비밀번호</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-submit">로그인</button>
        </form>

        <div class="auth-footer">
            <p>계정이 없으신가요? <a href="register.php">회원가입</a></p>
        </div>
    </div>
</div>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    .auth-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f5f5f5;
        padding: 20px;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Malgun Gothic', sans-serif;
    }

    .auth-container {
        background: white;
        border-radius: 12px;
        padding: 50px 40px;
        width: 100%;
        max-width: 400px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    }

    .auth-header {
        margin-bottom: 35px;
    }

    .auth-header h2 {
        font-size: 28px;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 10px;
    }



    .auth-form {
        margin-bottom: 25px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #1a1a1a;
        margin-bottom: 8px;
    }

    .form-group input {
        width: 100%;
        padding: 14px 16px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        font-size: 15px;
        background: #f8f9fa;
        transition: all 0.2s;
    }

    .form-group input:focus {
        outline: none;
        border-color: #4285f4;
        background: white;
    }

    .form-group input::placeholder {
        color: #aaa;
    }

    .btn-submit {
        width: 100%;
        padding: 15px;
        background: #4285f4;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
        margin-top: 10px;
    }

    .btn-submit:hover {
        background: #3367d6;
    }

    .btn-submit:active {
        transform: scale(0.99);
    }

    .auth-footer {
        text-align: center;
        padding-top: 25px;
        border-top: 1px solid #f0f0f0;
    }

    .auth-footer p {
        color: #666;
        font-size: 14px;
    }

    .auth-footer a {
        color: #4285f4;
        text-decoration: none;
        font-weight: 600;
    }

    .auth-footer a:hover {
        text-decoration: underline;
    }

    @media (max-width: 768px) {
        .auth-container {
            padding: 40px 30px;
        }

        .auth-header h2 {
            font-size: 24px;
        }
    }
</style>

<?php require_once '../includes/footer.php'; ?>