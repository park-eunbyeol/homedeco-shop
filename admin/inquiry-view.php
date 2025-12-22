<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';

// 관리자 권한 확인
if (!is_admin()) {
    redirect('../index.php');
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    redirect('inquiries-manage.php');
}

// 문의 내용 조회
$sql = "SELECT * FROM inquiries WHERE inquiry_id = $id";
$result = $conn->query($sql);
$inquiry = $result->fetch_assoc();

if (!$inquiry) {
    echo "<script>alert('존재하지 않는 문의입니다.'); location.href='inquiries-manage.php';</script>";
    exit;
}

// 답변 내용 조회
$sql2 = "SELECT * FROM inquiry_replies WHERE inquiry_id = $id";
$reply_result = $conn->query($sql2);
$reply = $reply_result->fetch_assoc();

$page_title = '문의 상세';
$current_page = 'inquiries';
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 - <?= $page_title ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .inquiry-detail-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 20px;
            transition: color 0.2s;
        }

        .back-btn:hover {
            color: var(--primary-color);
        }

        .reply-status-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
    </style>
</head>

<body>
    <div class="admin-wrapper">
        <!-- 사이드바 -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <i class="fas fa-crown"></i>
                <h3>관리자 메뉴</h3>
            </div>
            <nav class="admin-nav">
                <a href="index.php" class="nav-item <?= $current_page == 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i> 대시보드
                </a>
                <a href="products-manage.php" class="nav-item <?= $current_page == 'products' ? 'active' : '' ?>">
                    <i class="fas fa-box"></i> 상품 관리
                </a>
                <a href="orders-manage.php" class="nav-item <?= $current_page == 'orders' ? 'active' : '' ?>">
                    <i class="fas fa-shopping-cart"></i> 주문 관리
                </a>
                <a href="inquiries-manage.php" class="nav-item <?= $current_page == 'inquiries' ? 'active' : '' ?>">
                    <i class="fas fa-comments"></i> 문의 관리
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="../index.php" class="btn-site-home">
                    <i class="fas fa-home"></i> 사이트로 이동
                </a>
            </div>
        </aside>

        <!-- 메인 콘텐츠 -->
        <main class="admin-main">
            <a href="inquiries-manage.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> 목록으로 돌아가기
            </a>

            <div class="page-title">
                <i class="fas fa-comment-alt"></i> 문의 상세 보기
            </div>

            <div class="inquiry-detail-grid">
                <!-- 문의 상세 카드 -->
                <div class="admin-card detail-card">
                    <div class="meta-info">
                        <div class="meta-item">
                            <label>작성자</label>
                            <strong><?= htmlspecialchars($inquiry['name']) ?></strong>
                        </div>
                        <div class="meta-item">
                            <label>연락처</label>
                            <span><?= htmlspecialchars($inquiry['email']) ?> /
                                <?= htmlspecialchars($inquiry['phone'] ?? '-') ?></span>
                        </div>
                        <div class="meta-item">
                            <label>작성일</label>
                            <span><?= date('Y.m.d H:i', strtotime($inquiry['created_at'])) ?></span>
                        </div>
                        <div class="meta-item" style="margin-left: auto;">
                            <label>상태</label>
                            <?php if ($inquiry['status'] == 'pending'): ?>
                                <span class="badge badge-pending">답변대기</span>
                            <?php elseif ($inquiry['status'] == 'answered'): ?>
                                <span class="badge badge-success">답변완료</span>
                            <?php else: ?>
                                <span class="badge badge-muted">처리완료</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="inquiry-body">
                        <h2 style="font-size: 22px; margin-bottom: 20px; color: var(--text-main);">
                            <?php if (isset($inquiry['is_private']) && $inquiry['is_private']): ?>
                                <i class="fas fa-lock" style="color: #cbd5e1; font-size: 18px; margin-right: 10px;"></i>
                            <?php endif; ?>
                            <?= htmlspecialchars($inquiry['subject']) ?>
                        </h2>
                        <div class="content-box"><?= htmlspecialchars($inquiry['message']) ?></div>
                    </div>
                </div>

                <!-- 답변 작성 카드 -->
                <div class="admin-card detail-card">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 25px;">
                        <i class="fas fa-reply" style="color: var(--accent-color); font-size: 20px;"></i>
                        <h3 style="margin: 0; font-size: 18px;">관리자 답변</h3>
                        <?php if ($reply): ?>
                            <span style="font-size: 12px; color: var(--text-muted);">(최종 수정:
                                <?= date('Y.m.d H:i', strtotime($reply['created_at'])) ?>)</span>
                        <?php endif; ?>
                    </div>

                    <form action="inquiry-reply-save.php" method="POST" class="reply-form">
                        <input type="hidden" name="inquiry_id" value="<?= $id ?>">
                        <div class="form-group">
                            <textarea name="reply_message" placeholder="고객님의 문의에 대한 답변을 입력해 주세요."
                                required><?= $reply['reply_message'] ?? '' ?></textarea>
                        </div>

                        <div class="reply-status-section">
                            <label
                                style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: #64748b; font-size: 14px;">
                                <input type="checkbox" name="close_inquiry" value="1" <?= $inquiry['status'] == 'closed' ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                                <span>문의 처리 완료 (상담 종료)</span>
                            </label>
                            <div style="display: flex; gap: 12px;">
                                <a href="inquiries-manage.php" class="btn-cancel"
                                    style="padding: 12px 25px; background: #f1f5f9; color: #64748b; text-decoration: none; border-radius: 10px; font-weight: 600;">취소</a>
                                <button type="submit" class="btn-primary"
                                    style="padding: 12px 35px; background: var(--accent-color); color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer;">답변
                                    저장하기</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>

</html>