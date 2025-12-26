<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';

// 관리자 권한 확인
if (!is_admin()) {
    redirect('login.php');
}

$page_title = '대시보드';
$current_page = 'dashboard';

// 통계 데이터
$stats = [];
$stats['total_products'] = $conn->query("SELECT COUNT(*) as cnt FROM products WHERE is_active = 1")->fetch_assoc()['cnt'] ?? 0;
$stats['total_users'] = $conn->query("SELECT COUNT(*) as cnt FROM users")->fetch_assoc()['cnt'] ?? 0;

$table_check = $conn->query("SHOW TABLES LIKE 'orders'");
if ($table_check && $table_check->num_rows > 0) {
    $stats['total_orders'] = $conn->query("SELECT COUNT(*) as cnt FROM orders")->fetch_assoc()['cnt'] ?? 0;
    // 총 매출 (결제 완료 기준)
    $stats['total_revenue'] = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'paid'")->fetch_assoc()['total'] ?? 0;
    // 오늘 주문건수
    $stats['today_orders'] = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['cnt'] ?? 0;
    // 회원 vs 비회원 비율
    $stats['member_orders'] = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE user_id IS NOT NULL")->fetch_assoc()['cnt'] ?? 0;
    $stats['guest_orders'] = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE user_id IS NULL")->fetch_assoc()['cnt'] ?? 0;
} else {
    $stats['total_orders'] = 0;
    $stats['total_revenue'] = 0;
    $stats['today_orders'] = 0;
    $stats['member_orders'] = 0;
    $stats['guest_orders'] = 0;
}

$stats['pending_inquiries'] = $conn->query("SELECT COUNT(*) as cnt FROM inquiries WHERE status = 'pending'")->fetch_assoc()['cnt'] ?? 0;

// 최근 7일간 매출 차트 데이터용
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('m/d', strtotime("-$i days"));
    $revenue = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE DATE(created_at) = '$date' AND status = 'paid'")->fetch_assoc()['total'] ?? 0;
    $chart_data[] = ['label' => $label, 'value' => (int) $revenue];
}
$labels_json = json_encode(array_column($chart_data, 'label'));
$values_json = json_encode(array_column($chart_data, 'value'));
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .stat-card .label {
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 600;
        }

        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-color);
        }

        .stat-card .icon {
            font-size: 24px;
            color: var(--accent-color);
            margin-bottom: 5px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }

        .admin-menu-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            cursor: pointer;
        }

        .admin-menu-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
        }

        .admin-menu-card i {
            font-size: 48px;
            color: var(--accent-color);
            margin-bottom: 20px;
        }

        .admin-menu-card h3 {
            font-size: 20px;
            margin-bottom: 10px;
        }

        .admin-menu-card p {
            color: var(--text-muted);
            font-size: 14px;
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
                <?php if (is_super_admin()): ?>
                <a href="statistics.php" class="nav-item <?= $current_page == 'statistics' ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar"></i> 통계 분석
                </a>
                <?php endif; ?>
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
                <i class="fas fa-chart-line"></i> 대시보드
            </div>

            <!-- 통계 -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-won-sign"></i></div>
                    <div class="label">총 누적 매출</div>
                    <div class="value">
                        <?php if (is_super_admin()): ?>
                            <?= number_format($stats['total_revenue']) ?>
                        <?php else: ?>
                            권한 없음
                        <?php endif; ?>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-users"></i></div>
                    <div class="label">전체 회원</div>
                    <div class="value"><?= number_format($stats['total_users']) ?></div>
                </div>
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-shopping-bag"></i></div>
                    <div class="label">전체 주문</div>
                    <div class="value"><?= number_format($stats['total_orders']) ?></div>
                </div>
                <div class="stat-card">
                    <div class="icon" style="color: var(--warning-color);"><i class="fas fa-comment-dots"></i></div>
                    <div class="label">답변 대기 문의</div>
                    <div class="value" style="color: var(--warning-color);">
                        <?= number_format($stats['pending_inquiries']) ?>
                    </div>
                </div>
            </div>

            <!-- 메뉴 -->
            <div class="menu-grid">
                <a href="products-manage.php" class="admin-menu-card">
                    <i class="fas fa-boxes"></i>
                    <h3>상품 관리</h3>
                    <p>상품 등록, 수정 및 재고 관리</p>
                </a>
                <a href="orders-manage.php" class="admin-menu-card">
                    <i class="fas fa-receipt"></i>
                    <h3>주문 관리</h3>
                    <p>결제 내역 및 주문 상태 관리</p>
                </a>
                <a href="inquiries-manage.php" class="admin-menu-card">
                    <i class="fas fa-headset"></i>
                    <h3>문의 관리</h3>
                    <p>고객 문의 답변 및 상담 관리</p>
                </a>
                <a href="import-products.php" class="admin-menu-card">
                    <i class="fas fa-cloud-download-alt"></i>
                    <h3>상품 가져오기</h3>
                    <p>외부 API 연동 상품 일괄 등록</p>
                </a>
                <a href="notices-manage.php" class="admin-menu-card">
                    <i class="fas fa-bullhorn" style="color: #ff9f43;"></i>
                    <h3>공지사항 관리</h3>
                    <p>공지사항 및 이벤트 등록/삭제</p>
                </a>
            </div>
        </main>
    </div>
</body>

</html>