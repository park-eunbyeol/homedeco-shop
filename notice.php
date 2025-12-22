<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// 페이지네이션 설정
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1)
    $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 공지사항 테이블 생성 (없을 경우)
$conn->query("CREATE TABLE IF NOT EXISTS notices (
    notice_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_important TINYINT(1) DEFAULT 0,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 샘플 데이터 삽입 (테이블이 비어있을 경우)
$check_empty = $conn->query("SELECT COUNT(*) FROM notices");
if ($check_empty->fetch_row()[0] == 0) {
    $conn->query("INSERT INTO notices (title, content, is_important) VALUES 
        ('[공지] COZY-DECO 그랜드 오픈 및 신규 가입 이벤트 안내', '안녕하세요. COZY-DECO입니다. 브랜드 런칭을 기념하여 신규 가입 시 10% 쇼핑 지원금을 드립니다.', 1),
        ('[공지] 연말 배송 일정 및 고객센터 운영 안내', '연말 물량 증가로 인해 배송이 1~2일 지연될 수 있습니다. 고객님의 양해 부탁드립니다.', 0),
        ('[안내] 개인정보 처리방침 개정 안내 (2025.12.01)', '개인정보 보호법 개정에 따라 처리방침이 변경되었습니다.', 0)");
}

// 전체 데이터 수
$count_result = $conn->query("SELECT COUNT(*) FROM notices");
$total_rows = $count_result->fetch_row()[0];
$total_pages = ceil($total_rows / $limit);

// 게시글 목록 조회 (중요 공지 우선)
$sql = "SELECT * FROM notices ORDER BY is_important DESC, created_at DESC LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$notices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = '공지사항 | COZY-DECO';
include 'includes/header.php';
?>

<main class="notice-container">
    <div class="notice-header">
        <h2 class="notice-title">공지사항</h2>
        <p class="notice-subtitle">COZY-DECO의 새로운 소식과 안내를 전해드립니다.</p>
    </div>

    <div class="notice-list-wrapper">
        <table class="notice-table">
            <thead>
                <tr>
                    <th class="col-num">번호</th>
                    <th class="col-subject">제목</th>
                    <th class="col-date">작성일</th>
                    <th class="col-view">조회수</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($notices) > 0): ?>
                    <?php foreach ($notices as $row): ?>
                        <tr class="<?php echo $row['is_important'] ? 'important-row' : ''; ?>">
                            <td class="col-num">
                                <?php if ($row['is_important']): ?>
                                    <span class="badge-notice">중요</span>
                                <?php else: ?>
                                    <?php echo $row['notice_id']; ?>
                                <?php endif; ?>
                            </td>
                            <td class="col-subject">
                                <a href="notice_view.php?id=<?php echo $row['notice_id']; ?>">
                                    <?php echo htmlspecialchars($row['title']); ?>
                                </a>
                            </td>
                            <td class="col-date"><?php echo date('Y.m.d', strtotime($row['created_at'])); ?></td>
                            <td class="col-view"><?php echo $row['view_count']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="empty-msg">등록된 공지사항이 없습니다.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="<?php echo ($i === $page) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</main>

<style>
    .notice-container {
        max-width: 1000px;
        margin: 60px auto;
        padding: 0 20px;
    }

    .notice-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .notice-title {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .notice-subtitle {
        color: #666;
    }

    .notice-table {
        width: 100%;
        border-collapse: collapse;
        border-top: 2px solid #333;
    }

    .notice-table th {
        background: #f8f9fa;
        padding: 15px;
        font-size: 14px;
        font-weight: 600;
        border-bottom: 1px solid #eee;
    }

    .notice-table td {
        padding: 18px 15px;
        border-bottom: 1px solid #eee;
        text-align: center;
        font-size: 15px;
    }

    .col-num {
        width: 80px;
    }

    .col-subject {
        text-align: left !important;
    }

    .col-date {
        width: 120px;
        color: #888;
    }

    .col-view {
        width: 80px;
        color: #888;
    }

    .col-subject a {
        color: #333;
        text-decoration: none;
        transition: color 0.2s;
    }

    .col-subject a:hover {
        color: var(--primary-color);
    }

    .important-row {
        background-color: #fff9f9;
    }

    .badge-notice {
        background: #ff4757;
        color: white;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 700;
    }

    .empty-msg {
        padding: 60px 0 !important;
        color: #999;
    }

    .pagination {
        margin-top: 40px;
        display: flex;
        justify-content: center;
        gap: 8px;
    }

    .pagination a {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #eee;
        text-decoration: none;
        color: #666;
        border-radius: 4px;
    }

    .pagination a.active {
        background: #333;
        color: white;
        border-color: #333;
    }
</style>

<?php include 'includes/footer.php'; ?>