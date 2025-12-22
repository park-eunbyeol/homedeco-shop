<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';

// 관리자 권한 확인
if (!is_admin()) {
    redirect('../index.php');
}

$page_title = '상품 가져오기';
$current_page = 'import';

// 카테고리 목록 조회
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
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
                <i class="fas fa-bolt"></i> 네이버 상품 가져오기
            </div>

            <div style="max-width: 800px;">
                <div class="admin-card detail-card">
                    <p style="color: var(--text-muted); margin-bottom: 30px; line-height: 1.6;">
                        네이버 쇼핑 API를 통해 실시간 인기 상품 정보를 우리 사이트로 가져옵니다.<br>
                        검색 키워드에 맞는 상품들을 자동으로 분석하여 이미지를 포함한 모든 정보를 등록합니다.
                    </p>

                    <form id="importForm" class="reply-form">
                        <div class="form-group">
                            <label style="font-weight: 700; display: block; margin-bottom: 10px;">카테고리 선택 *</label>
                            <select name="category_id" class="form-control"
                                style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 10px;"
                                required>
                                <option value="">카테고리를 선택하세요</option>
                                <?php while ($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label style="font-weight: 700; display: block; margin-bottom: 10px;">검색 키워드 *</label>
                            <input type="text" name="keyword" class="form-control"
                                style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 10px;"
                                placeholder="예: 북유럽 소파, 미니 무드등, 원목 식탁" required>
                            <small style="color: #94a3b8; margin-top: 8px; display: block;">💡 구체적인 키워드를 입력할수록 적절한 상품이
                                검색됩니다.</small>
                        </div>

                        <div class="form-group">
                            <label style="font-weight: 700; display: block; margin-bottom: 10px;">가져올 수량</label>
                            <select name="limit" class="form-control"
                                style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 10px;">
                                <option value="10">10개씩</option>
                                <option value="20" selected>20개씩</option>
                                <option value="50">50개씩</option>
                            </select>
                        </div>

                        <div id="resultMessage"
                            style="display: none; padding: 20px; border-radius: 12px; margin-bottom: 25px; font-weight: 500;">
                        </div>

                        <div style="display: flex; gap: 15px; margin-top: 30px;">
                            <button type="submit" id="submitBtn"
                                style="flex: 1; padding: 15px; background: var(--accent-color); color: white; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px;">
                                <i class="fas fa-magic"></i> 상품 분석 및 일괄 가져오기
                            </button>
                        </div>
                    </form>

                    <div style="margin-top: 40px; padding: 25px; background: #f0f7ff; border-radius: 16px;">
                        <h4 style="color: #007bff; margin: 0 0 15px 0;"><i class="fas fa-info-circle"></i> 주의사항 및 안내
                        </h4>
                        <ul style="margin: 0; padding-left: 20px; color: #4b5563; line-height: 1.8; font-size: 14px;">
                            <li>이미 우리 DB에 존재하는 품명의 상품은 자동으로 스킵됩니다.</li>
                            <li>실제 서비스 연동 시에는 네이버 개발자 센터에서 발급받은 API 키가 필요합니다.</li>
                            <li>가져온 상품은 즉시 '판매중' 상태로 등록됩니다 (상품 관리에서 수정 가능).</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('importForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const resDiv = document.getElementById('resultMessage');

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 상품 정보를 분석하여 가져오는 중...';

            resDiv.style.display = 'none';

            try {
                const formData = new FormData(this);
                const response = await fetch('/homedeco-shop/api/import-naver-products.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                resDiv.style.display = 'block';
                if (data.success) {
                    resDiv.style.background = '#e8f5e9';
                    resDiv.style.color = '#2e7d32';
                    resDiv.innerHTML = `<i class="fas fa-check-circle"></i> 상품 가져오기 완료! <br>성공: ${data.imported}건 / 건너뜀(중복): ${data.skipped}건`;
                    setTimeout(() => location.href = 'products-manage.php', 2500);
                } else {
                    resDiv.style.background = '#ffebee';
                    resDiv.style.color = '#c62828';
                    resDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> 오류: ${data.message}`;
                }
            } catch (e) {
                resDiv.style.display = 'block';
                resDiv.style.background = '#ffebee';
                resDiv.style.color = '#c62828';
                resDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> 서버와 통신 중 문제가 발생했습니다.`;
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-magic"></i> 상품 분석 및 일괄 가져오기';
            }
        });
    </script>
</body>

</html>