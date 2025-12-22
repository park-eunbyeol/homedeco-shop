<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';

// 관리자 권한 확인
if (!is_admin()) {
    redirect('login.php');
}

$page_title = '문의 관리';
$current_page = 'inquiries';

// 검색 및 필터
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$status = isset($_GET['status']) ? clean_input($_GET['status']) : '';

// 문의 조회
$where = [];
if (!empty($search)) {
    $where[] = "(subject LIKE '%$search%' OR message LIKE '%$search%' OR name LIKE '%$search%')";
}
if (!empty($status)) {
    $where[] = "status = '$status'";
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT i.*, r.reply_id 
        FROM inquiries i 
        LEFT JOIN inquiry_replies r ON i.inquiry_id = r.inquiry_id 
        $where_clause 
        ORDER BY i.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 - <?= $page_title ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
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
                <a href="notices-manage.php" class="nav-item <?= $current_page == 'notices' ? 'active' : '' ?>">
                    <i class="fas fa-bullhorn"></i> 공지사항 관리
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
            <div class="page-title">
                <i class="fas fa-comments"></i> 문의 관리
            </div>

            <!-- 검색 및 필터 -->
            <div class="admin-card">
                <form method="GET" class="filter-bar">
                    <div class="search-wrapper">
                        <input type="text" name="search" placeholder="제목, 내용, 작성자 검색"
                            value="<?= htmlspecialchars($search) ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </div>
                    <select name="status" onchange="this.form.submit()" class="status-select">
                        <option value="">전체 상태</option>
                        <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>답변대기</option>
                        <option value="answered" <?= $status == 'answered' ? 'selected' : '' ?>>답변완료</option>
                        <option value="closed" <?= $status == 'closed' ? 'selected' : '' ?>>처리완료</option>
                    </select>
                </form>
            </div>

            <!-- 목록 테이블 -->
            <div class="admin-card">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>제목</th>
                            <th style="width: 150px;">작성자</th>
                            <th style="width: 180px;">작성일</th>
                            <th style="width: 120px;">상태</th>
                            <th style="width: 140px; text-align: center;">관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['inquiry_id'] ?></td>
                                    <td>
                                        <a href="inquiry-view.php?id=<?= $row['inquiry_id'] ?>"
                                            style="text-decoration: none; color: inherit; font-weight: 500;">
                                            <?php if (isset($row['is_private']) && $row['is_private']): ?>
                                                <i class="fas fa-lock"
                                                    style="color: #cbd5e1; font-size: 13px; margin-right: 8px;"></i>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($row['subject']) ?>
                                        </a>
                                    </td>
                                    <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                                    <td style="color: #94a3b8; font-size: 14px;">
                                        <?= date('Y.m.d H:i', strtotime($row['created_at'])) ?>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] == 'pending'): ?>
                                            <span class="badge badge-pending">답변대기</span>
                                        <?php elseif ($row['status'] == 'answered'): ?>
                                            <span class="badge badge-success">답변완료</span>
                                        <?php else: ?>
                                            <span class="badge badge-muted">처리완료</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="display: flex; gap: 8px; justify-content: center;">
                                            <a href="inquiry-view.php?id=<?= $row['inquiry_id'] ?>" class="action-btn btn-reply"
                                                title="답변">
                                                <i class="fas fa-reply"></i>
                                            </a>
                                            <button onclick="deleteInquiry(<?= $row['inquiry_id'] ?>)"
                                                class="action-btn btn-delete" title="삭제">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="padding: 100px 0; text-align: center; color: #94a3b8;">
                                    <i class="fas fa-inbox"
                                        style="font-size: 48px; display: block; margin-bottom: 20px; opacity: 0.2;"></i>
                                    문의 내역이 없습니다.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        function deleteInquiry(id) {
            if (confirm('정말 이 문의글을 삭제하시겠습니까?')) {
                location.href = 'inquiry-delete.php?id=' + id;
            }
        }
    </script>
</body>

</html>