<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    redirect('notice.php');
}

// 조회수 증가
$conn->query("UPDATE notices SET view_count = view_count + 1 WHERE notice_id = $id");

// 공지사항 상세 조회
$stmt = $conn->prepare("SELECT * FROM notices WHERE notice_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$notice = $result->fetch_assoc();

if (!$notice) {
    redirect('notice.php');
}

$page_title = $notice['title'] . ' | 공지사항';
include 'includes/header.php';
?>

<main class="notice-detail-container">
    <div class="notice-detail-header">
        <div class="notice-category">공지사항</div>
        <h1 class="notice-detail-title">
            <?php if ($notice['is_important']): ?><span class="badge-notice">중요</span><?php endif; ?>
            <?php echo htmlspecialchars($notice['title']); ?>
        </h1>
        <div class="notice-meta">
            <span><i class="far fa-calendar-alt"></i>
                <?php echo date('Y.m.d H:i', strtotime($notice['created_at'])); ?></span>
            <span><i class="far fa-eye"></i> 조회수 <?php echo $notice['view_count']; ?></span>
        </div>
    </div>

    <div class="notice-detail-body">
        <?php echo nl2br(htmlspecialchars($notice['content'])); ?>
    </div>

    <div class="notice-detail-footer">
        <a href="notice.php" class="btn-list">목록으로</a>
    </div>
</main>

<style>
    .notice-detail-container {
        max-width: 900px;
        margin: 60px auto;
        padding: 0 20px;
    }

    .notice-detail-header {
        border-bottom: 1px solid #eee;
        padding-bottom: 25px;
        margin-bottom: 30px;
    }

    .notice-category {
        color: var(--primary-color);
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 10px;
    }

    .notice-detail-title {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 15px;
        line-height: 1.4;
    }

    .notice-meta {
        display: flex;
        gap: 20px;
        color: #888;
        font-size: 14px;
    }

    .notice-meta i {
        margin-right: 5px;
    }

    .notice-detail-body {
        min-height: 300px;
        line-height: 1.8;
        color: #444;
        font-size: 16px;
        padding-bottom: 50px;
        border-bottom: 1px solid #eee;
    }

    .notice-detail-footer {
        padding-top: 30px;
        text-align: center;
    }

    .btn-list {
        display: inline-block;
        padding: 12px 40px;
        background: #333;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: background 0.2s;
    }

    .btn-list:hover {
        background: #000;
    }

    .badge-notice {
        background: #ff4757;
        color: white;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 14px;
        vertical-align: middle;
        margin-right: 8px;
    }
</style>

<?php include 'includes/footer.php'; ?>