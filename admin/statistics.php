<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';

// 관리자 권한 확인
if (!is_admin()) {
    redirect('login.php');
}

$page_title = '통계 분석';
$current_page = 'statistics';

// 통계 데이터 계산
$stats = [];
$stats['total_revenue'] = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'paid'")->fetch_assoc()['total'] ?? 0;
$stats['total_orders'] = $conn->query("SELECT COUNT(*) as cnt FROM orders")->fetch_assoc()['cnt'] ?? 0;
$stats['member_orders'] = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE user_id IS NOT NULL")->fetch_assoc()['cnt'] ?? 0;
$stats['guest_orders'] = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE user_id IS NULL")->fetch_assoc()['cnt'] ?? 0;

// 최근 7일간 매출 차트 데이터
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('m/d', strtotime("-$i days"));
    $revenue = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE DATE(created_at) = '$date' AND status = 'paid'")->fetch_assoc()['total'] ?? 0;
    $chart_data[] = ['label' => $label, 'value' => (int) $revenue];
}
$labels_json = json_encode(array_column($chart_data, 'label'));
$values_json = json_encode(array_column($chart_data, 'value'));

// 인기 상품 TOP 5
$top_products = $conn->query("
    SELECT p.name, SUM(oi.quantity) as total_qty, SUM(oi.quantity * oi.price) as total_sales
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    GROUP BY p.product_id
    ORDER BY total_qty DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 - <?= $page_title ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .admin-main {
            padding: 20px !important;
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            background-color: #f8fafc;
        }

        .page-title {
            margin-bottom: 20px !important;
            font-size: 20px !important;
            font-weight: 800;
            color: #1e293b;
        }

        .stat-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .summary-item {
            padding: 20px;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            transition: transform 0.2s;
        }
        
        .summary-item:hover {
            transform: translateY(-2px);
        }

        .summary-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .summary-info {
            display: flex;
            flex-direction: column;
        }

        .summary-item .label {
            font-size: 13px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .summary-item .value {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
        }

        .stats-container {
            display: grid;
            grid-template-columns: 2fr 1.2fr 1fr;
            gap: 20px;
            flex: 1;
            min-height: 0;
        }

        .stats-card {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f1f5f9;
        }

        .card-header h3 {
            font-size: 15px;
            font-weight: 700;
            margin: 0;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Table Styling */
        .top-products-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
        }

        .top-products-table th {
            text-align: left;
            font-size: 11px;
            color: #64748b;
            padding: 0 10px;
            font-weight: 600;
        }

        .top-products-table td {
            padding: 12px 10px;
            font-size: 13px;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
        }
        
        .top-products-table tr td:first-child {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
            border-right: none;
        }
        
        .top-products-table tr td:last-child {
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
            border-left: none;
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
                <a href="index.php" class="nav-item">
                    <i class="fas fa-chart-line"></i> 대시보드
                </a>
                <a href="statistics.php" class="nav-item active">
                    <i class="fas fa-chart-bar"></i> 통계 분석
                </a>
                <a href="products-manage.php" class="nav-item">
                    <i class="fas fa-box"></i> 상품 관리
                </a>
                <a href="orders-manage.php" class="nav-item">
                    <i class="fas fa-shopping-cart"></i> 주문 관리
                </a>
                <a href="inquiries-manage.php" class="nav-item">
                    <i class="fas fa-comments"></i> 문의 관리
                </a>
            </nav>
        </aside>

        <main class="admin-main">
            <div class="page-title">
                <i class="fas fa-chart-bar"></i> 통계 분석
            </div>

            <div class="stat-summary-grid">
                <!-- 누적 매출액 -->
                <div class="summary-item">
                    <div class="summary-icon" style="background: #eff6ff; color: #3b82f6;">
                        <i class="fas fa-won-sign"></i>
                    </div>
                    <div class="summary-info">
                        <span class="label">누적 매출액</span>
                        <div class="value"><?= number_format($stats['total_revenue']) ?>원</div>
                    </div>
                </div>

                <!-- 누적 주문수 -->
                <div class="summary-item">
                    <div class="summary-icon" style="background: #f5f3ff; color: #8b5cf6;">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="summary-info">
                        <span class="label">누적 주문수</span>
                        <div class="value"><?= number_format($stats['total_orders']) ?>건</div>
                    </div>
                </div>

                <!-- 평균 객단가 -->
                <div class="summary-item">
                    <div class="summary-icon" style="background: #ecfdf5; color: #10b981;">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="summary-info">
                        <span class="label">평균 객단가</span>
                        <div class="value">
                            <?= $stats['total_orders'] > 0 ? number_format($stats['total_revenue'] / $stats['total_orders']) : 0 ?>원
                        </div>
                    </div>
                </div>

                <!-- 회원 주문율 -->
                <div class="summary-item">
                    <div class="summary-icon" style="background: #fff7ed; color: #f97316;">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="summary-info">
                        <span class="label">회원 주문율</span>
                        <div class="value">
                            <?= $stats['total_orders'] > 0 ? round(($stats['member_orders'] / $stats['total_orders']) * 100) : 0 ?>%
                        </div>
                    </div>
                </div>
            </div>

            <!-- 메인 통계 그리드 (한 줄 배치) -->
            <div class="stats-container" style="grid-template-columns: 2fr 1.2fr 1fr; gap: 15px; grid-auto-rows: minmax(min-content, max-content);">
                
                <!-- 1. 매출 차트 -->
                <div class="stats-card" style="padding: 15px; height: 100%;">
                    <div class="card-header" style="margin-bottom: 10px; padding-bottom: 5px;">
                        <h3><i class="fas fa-line-chart" style="margin-right: 8px; color: #3498db;"></i>주간 매출</h3>
                    </div>
                    <div style="height: 200px; width: 100%;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- 2. 판매 TOP 5 (테이블) -->
                <div class="stats-card" style="padding: 15px; height: 100%; overflow: hidden;">
                    <div class="card-header" style="margin-bottom: 10px; padding-bottom: 5px;">
                        <h3><i class="fas fa-trophy" style="margin-right: 8px; color: #f1c40f;"></i>인기 상품</h3>
                    </div>
                    <div style="overflow-y: auto;">
                        <table class="top-products-table">
                            <thead>
                                <tr>
                                    <th>상품명</th>
                                    <th style="text-align: right; white-space: nowrap;">판매</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank=1; while ($p = $top_products->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <span style="display:inline-block; width:16px; height:16px; background:<?= $rank==1?'#f1c40f':($rank==2?'#bdc3c7':'#e67e22') ?>; color:#fff; font-size:10px; text-align:center; line-height:16px; border-radius:50%; margin-right:5px;"><?= $rank++ ?></span>
                                            <?= htmlspecialchars(mb_strimwidth($p['name'], 0, 16, '..')) ?>
                                        </td>
                                        <td style="text-align: right; font-weight: 600;"><?= number_format($p['total_qty']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 3. 고객 비중 -->
                <div class="stats-card" style="padding: 15px; height: 100%;">
                    <div class="card-header" style="margin-bottom: 10px; padding-bottom: 5px;">
                        <h3><i class="fas fa-users" style="margin-right: 8px; color: #2ecc71;"></i>고객</h3>
                    </div>
                    <div style="height: 120px; display: flex; align-items: center; justify-content: center;">
                        <canvas id="customerChart"></canvas>
                    </div>
                    <div style="margin-top: 10px; font-size: 11px; text-align: center; color: #64748b;">
                        회원 <?= round($stats['total_orders'] > 0 ? ($stats['member_orders'] / $stats['total_orders']) * 100 : 0) ?>% 
                        / 비회원 <?= round($stats['total_orders'] > 0 ? ($stats['guest_orders'] / $stats['total_orders']) * 100 : 0) ?>%
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        // 매출 차트
        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: <?= $labels_json ?>,
                datasets: [{
                    label: '일일 매출',
                    data: <?= $values_json ?>,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderWidth: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#3498db',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f1f5f9'
                        },
                        ticks: {
                            callback: function (value) {
                                if (value >= 10000) return (value / 10000) + '만';
                                return value.toLocaleString();
                            },
                            font: { size: 11 }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 } }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        padding: 12,
                        backgroundColor: 'rgba(30, 41, 59, 0.9)',
                        titleFont: { size: 13, weight: 'bold' },
                        bodyFont: { size: 13 },
                        callbacks: {
                            label: function (context) {
                                return '매출액: ' + context.parsed.y.toLocaleString() + '원';
                            }
                        }
                    }
                }
            }
        });

        // 고객 분포 차트
        new Chart(document.getElementById('customerChart'), {
            type: 'doughnut',
            data: {
                labels: ['회원', '비회원'],
                datasets: [{
                    data: [<?= $stats['member_orders'] ?>, <?= $stats['guest_orders'] ?>],
                    backgroundColor: ['#3498db', '#94a3b8']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
    </script>
</body>

</html>