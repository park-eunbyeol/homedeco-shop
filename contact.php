<?php
require_once 'includes/db.php';
$page_title = '문의 게시판';
require_once 'includes/header.php';

// 페이지네이션 설정
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1)
    $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 게시글 가져오기
// 테이블이 존재하는지 확인
$table_check = $conn->query("SHOW TABLES LIKE 'inquiries'");
if ($table_check->num_rows == 0) {
    // 테이블이 없으면 생성 안내 또는 빈 배열
    $error_msg = "아직 문의 게시판이 설정되지 않았습니다. 관리자에게 문의하세요.";
    $inquiries = [];
    $total_rows = 0;
} else {
    // 전체 게시글 수
    $count_sql = "SELECT COUNT(*) FROM inquiries";
    $count_result = $conn->query($count_sql);
    $total_rows = $count_result->fetch_row()[0];

    // 게시글 목록 조회
    $sql = "SELECT * FROM inquiries ORDER BY created_at DESC LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $offset, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $inquiries = $result->fetch_all(MYSQLI_ASSOC);
}

$total_pages = ceil($total_rows / $limit);
?>

<div class="container board-container">
    <div class="board-header">
        <h2 class="board-title">문의 게시판</h2>
    </div>

    <?php if (isset($error_msg)): ?>
        <div style="padding: 20px; background: #f8d7da; color: #721c24; border-radius: 8px; margin-bottom: 20px;">
            <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>

    <table class="board-table">
        <colgroup>
            <col style="width: 80px;">
            <col style="width: auto;">
            <col style="width: 120px;">
            <col style="width: 120px;">
        </colgroup>
        <thead>
            <tr>
                <th>번호</th>
                <th>제목</th>
                <th>작성자</th>
                <th>작성일</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($inquiries) > 0): ?>
                <?php foreach ($inquiries as $row): ?>
                    <tr>
                        <td><?php echo $row['inquiry_id']; ?></td>
                        <td class="text-left">
                            <a href="contact_view.php?id=<?php echo $row['inquiry_id']; ?>">
                                <?php if (isset($row['is_private']) && $row['is_private']): ?>
                                    <i class="fas fa-lock" style="color: #999; margin-right: 5px;"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($row['subject']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo date('Y.m.d', strtotime($row['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="padding: 40px 0; color: #999;">등록된 문의가 없습니다.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="board-footer">
        <!-- 페이지네이션 -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1">&laquo;</a>
                    <a href="?page=<?php echo $page - 1; ?>">&lt;</a>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                    <a href="?page=<?php echo $i; ?>" class="<?php echo ($i === $page) ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>">&gt;</a>
                    <a href="?page=<?php echo $total_pages; ?>">&raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- 글쓰기 버튼 -->
        <div class="write-btn-wrapper">
            <a href="contact_write.php" class="btn btn-primary">
                <i class="fas fa-pen" style="margin-right: 6px;"></i> 글쓰기
            </a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>