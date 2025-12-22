<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$page_title = 'Q&A | COZY-DECO';
include 'includes/header.php';

// 페이지네이션 설정
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1)
    $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 검색 및 카테고리 필터
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$category = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : 'all';

$where = ["1=1"];
if (!empty($search)) {
    $where[] = "(subject LIKE '%$search%' OR name LIKE '%$search%')";
}
// inquiries 테이블에 category 컬럼이 있다고 가정 (없으면 무시)
// 만약 없으면 아래 쿼리에서 에러가 날 수 있으므로 체크 필요
$columns_check = $conn->query("SHOW COLUMNS FROM inquiries LIKE 'category'");
if ($columns_check->num_rows > 0 && $category !== 'all') {
    $where[] = "category = '$category'";
}

$where_clause = implode(' AND ', $where);

// 전체 게시글 수
$count_sql = "SELECT COUNT(*) FROM inquiries WHERE $where_clause";
$count_result = $conn->query($count_sql);
$total_rows = $count_result ? $count_result->fetch_row()[0] : 0;
$total_pages = ceil($total_rows / $limit);

// 게시글 목록 조회
$sql = "SELECT i.*, r.reply_id 
        FROM inquiries i 
        LEFT JOIN inquiry_replies r ON i.inquiry_id = r.inquiry_id 
        WHERE $where_clause 
        ORDER BY i.created_at DESC 
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$inquiries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<main class="qna-container">
    <div class="qna-header">
        <h2 class="qna-title">Q&A</h2>
        <p class="qna-subtitle">궁금하신 점을 남겨주시면 정성껏 답변해 드립니다.</p>
    </div>

    <!-- 검색 및 필터 -->
    <div class="qna-filter-bar">
        <form method="GET" class="qna-filter-form">
            <div class="search-group">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="제목 또는 작성자 검색">
                <button type="submit"><i class="fas fa-search"></i></button>
            </div>
            <div class="category-group">
                <select name="category" onchange="this.form.submit()">
                    <option value="all" <?php echo $category == 'all' ? 'selected' : ''; ?>>전체 카테고리</option>
                    <option value="상품" <?php echo $category == '상품' ? 'selected' : ''; ?>>상품 문의</option>
                    <option value="배송" <?php echo $category == '배송' ? 'selected' : ''; ?>>배송 문의</option>
                    <option value="결제" <?php echo $category == '결제' ? 'selected' : ''; ?>>결제 문의</option>
                    <option value="기타" <?php echo $category == '기타' ? 'selected' : ''; ?>>기타 문의</option>
                </select>
            </div>
        </form>
        <a href="contact_write.php" class="btn-write">문의하기</a>
    </div>

    <div class="qna-list-wrapper">
        <table class="qna-table">
            <thead>
                <tr>
                    <th class="col-status">상태</th>
                    <th class="col-subject">제목</th>
                    <th class="col-author">작성자</th>
                    <th class="col-date">작성일</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($inquiries) > 0): ?>
                    <?php foreach ($inquiries as $row): ?>
                        <tr>
                            <td class="col-status">
                                <?php if ($row['reply_id']): ?>
                                    <span class="badge-answered">답변완료</span>
                                <?php else: ?>
                                    <span class="badge-pending">답변대기</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-subject">
                                <a href="contact_view.php?id=<?php echo $row['inquiry_id']; ?>">
                                    <?php if (isset($row['is_private']) && $row['is_private']): ?>
                                        <i class="fas fa-lock private-icon"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($row['subject']); ?>
                                </a>
                            </td>
                            <td class="col-author">
                                <?php echo htmlspecialchars(mb_substr($row['name'], 0, 1) . '*' . mb_substr($row['name'], 2)); ?>
                            </td>
                            <td class="col-date"><?php echo date('Y.m.d', strtotime($row['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="empty-msg">등록된 문의가 없습니다.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>"
                    class="<?php echo ($i === $page) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</main>

<style>
    .qna-container {
        max-width: 1000px;
        margin: 60px auto;
        padding: 0 20px;
    }

    .qna-header {
        text-align: center;
        margin-bottom: 50px;
    }

    .qna-title {
        font-size: 2.2rem;
        font-weight: 800;
        margin-bottom: 10px;
        letter-spacing: -0.5px;
    }

    .qna-subtitle {
        color: #718096;
        font-size: 1.1rem;
    }

    .qna-filter-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        gap: 20px;
    }

    .qna-filter-form {
        display: flex;
        gap: 12px;
        flex: 1;
    }

    .search-group {
        position: relative;
        flex: 1;
        max-width: 400px;
    }

    .search-group input {
        width: 100%;
        padding: 10px 40px 10px 15px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
    }

    .search-group button {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #a0aec0;
        cursor: pointer;
    }

    .category-group select {
        padding: 10px 15px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        background: white;
    }

    .btn-write {
        padding: 11px 24px;
        background: #1a202c;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.2s;
        white-space: nowrap;
    }

    .btn-write:hover {
        background: #2d3748;
        transform: translateY(-1px);
    }

    .qna-table {
        width: 100%;
        border-collapse: collapse;
        border-top: 2px solid #1a202c;
    }

    .qna-table th {
        background: #f8fafc;
        padding: 16px;
        font-size: 14px;
        font-weight: 700;
        color: #4a5568;
        border-bottom: 1px solid #edf2f7;
    }

    .qna-table td {
        padding: 20px 16px;
        border-bottom: 1px solid #edf2f7;
        text-align: center;
        font-size: 15px;
        color: #2d3748;
    }

    .col-status {
        width: 100px;
    }

    .col-subject {
        text-align: left !important;
    }

    .col-author {
        width: 120px;
        color: #718096;
    }

    .col-date {
        width: 120px;
        color: #a0aec0;
        font-size: 14px;
    }

    .col-subject a {
        color: inherit;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .col-subject a:hover {
        color: #1a202c;
        font-weight: 600;
    }

    .private-icon {
        font-size: 12px;
        color: #cbd5e0;
    }

    .badge-answered {
        background: #e6fffa;
        color: #319795;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 700;
    }

    .badge-pending {
        background: #fff5f5;
        color: #e53e3e;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 700;
    }

    .empty-msg {
        padding: 80px 0 !important;
        color: #a0aec0;
    }

    .pagination {
        margin-top: 40px;
        display: flex;
        justify-content: center;
        gap: 8px;
    }

    .pagination a {
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e2e8f0;
        text-decoration: none;
        color: #4a5568;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.2s;
    }

    .pagination a.active {
        background: #1a202c;
        color: white;
        border-color: #1a202c;
    }

    @media (max-width: 768px) {
        .qna-filter-bar {
            flex-direction: column;
            align-items: stretch;
        }

        .btn-write {
            text-align: center;
        }

        .col-date,
        .col-status {
            font-size: 12px;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>