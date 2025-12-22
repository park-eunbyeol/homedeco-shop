<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';

// 관리자 권한 확인
if (!is_admin()) {
    redirect('login.php');
}

$page_title = '공지사항 관리';
$current_page = 'notices';

// 공지사항 조회
$notices = $conn->query("SELECT * FROM notices ORDER BY is_important DESC, created_at DESC");
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
        .notice-table-container {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .badge-important {
            background: #ff4757;
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
        }

        .notice-title-link {
            color: #333;
            text-decoration: none;
            font-weight: 500;
        }

        .notice-title-link:hover {
            color: var(--primary-color);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            padding: 30px;
            box-sizing: border-box;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-family: inherit;
        }

        textarea.form-control {
            height: 200px;
            resize: none;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-group {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="admin-wrapper">
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

        <main class="admin-main">
            <div class="header-action">
                <div class="page-title">
                    <i class="fas fa-bullhorn"></i> 공지사항 관리
                </div>
                <button class="btn btn-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i> 공지 등록
                </button>
            </div>

            <div class="notice-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>번호</th>
                            <th>상태</th>
                            <th>제목</th>
                            <th>조회수</th>
                            <th>등록일</th>
                            <th>관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($notice = $notices->fetch_assoc()): ?>
                            <tr>
                                <td><?= $notice['notice_id'] ?></td>
                                <td>
                                    <?php if ($notice['is_important']): ?>
                                        <span class="badge-important">중요</span>
                                    <?php else: ?>
                                        <span class="badge badge-muted">일반</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: left;">
                                    <a href="#" class="notice-title-link"
                                        onclick="editNotice(<?= htmlspecialchars(json_encode($notice)) ?>)">
                                        <?= htmlspecialchars($notice['title']) ?>
                                    </a>
                                </td>
                                <td><?= number_format($notice['view_count']) ?></td>
                                <td><?= date('Y-m-d', strtotime($notice['created_at'])) ?></td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn-icon" onclick="deleteNotice(<?= $notice['notice_id'] ?>)"
                                            title="삭제">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- 등록/수정 모달 -->
    <div id="noticeModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle" style="margin-bottom: 25px;">공지사항 등록</h3>
            <form id="noticeForm">
                <input type="hidden" name="notice_id" id="notice_id">
                <div class="form-group">
                    <label>제목</label>
                    <input type="text" name="title" id="title" class="form-control" placeholder="공지사항 제목을 입력하세요"
                        required>
                </div>
                <div class="form-group">
                    <label>내용</label>
                    <textarea name="content" id="content" class="form-control" placeholder="공지사항 상세 내용을 입력하세요"
                        required></textarea>
                </div>
                <div class="form-group checkbox-group">
                    <input type="checkbox" name="is_important" id="is_important" value="1">
                    <label for="is_important" style="margin-bottom:0;">중요 공지로 설정 (상단 노출)</label>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn" onclick="closeModal()" style="background: #eee;">취소</button>
                    <button type="submit" class="btn btn-primary">저장하기</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('noticeModal');

        function openModal() {
            document.getElementById('noticeForm').reset();
            document.getElementById('notice_id').value = '';
            document.getElementById('modalTitle').innerText = '공지사항 등록';
            modal.classList.add('show');
        }

        function closeModal() {
            modal.classList.remove('show');
        }

        function editNotice(notice) {
            document.getElementById('notice_id').value = notice.notice_id;
            document.getElementById('title').value = notice.title;
            document.getElementById('content').value = notice.content;
            document.getElementById('is_important').checked = notice.is_important == 1;
            document.getElementById('modalTitle').innerText = '공지사항 수정';
            modal.classList.add('show');
        }

        document.getElementById('noticeForm').onsubmit = async function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            data.is_important = document.getElementById('is_important').checked ? 1 : 0;

            try {
                const response = await fetch('api-notice-save.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert(result.message || '오류가 발생했습니다.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('네트워크 오류가 발생했습니다.');
            }
        };

        async function deleteNotice(id) {
            if (!confirm('정말 삭제하시겠습니까?')) return;

            try {
                const response = await fetch('api-notice-delete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notice_id: id })
                });
                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert(result.message || '오류가 발생했습니다.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('네트워크 오류가 발생했습니다.');
            }
        }
    </script>
</body>

</html>