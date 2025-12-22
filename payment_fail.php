<?php
// 결제 실패 페이지 mockup
$page_title = '결제 실패';
require_once 'includes/db.php';
require_once 'includes/header.php';

$code = $_GET['code'] ?? '';
$message = $_GET['message'] ?? '';

?>
<div class="container" style="text-align: center; padding: 100px 0;">
    <div style="font-size: 60px; color: #e74c3c; margin-bottom: 20px;">
        <i class="fas fa-times-circle"></i>
    </div>
    <h1>결제에 실패했습니다.</h1>
    <p style="color: #666; margin-bottom: 30px;">
        사유: <?php echo htmlspecialchars($message); ?> (<?php echo htmlspecialchars($code); ?>)
    </p>
    <a href="checkout.php" class="btn btn-primary">다시 시도하기</a>
</div>
<?php require_once 'includes/footer.php'; ?>