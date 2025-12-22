<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';

// 관리자 권한 확인
if (!is_admin()) {
    redirect('login.php');
}

$page_title = '주문 관리';
$current_page = 'orders';

// 주문 목록 조회 필터링
$type_filter = "";
if (isset($_GET['type'])) {
    if ($_GET['type'] == 'member') {
        $type_filter = " WHERE o.user_id IS NOT NULL";
    } elseif ($_GET['type'] == 'guest') {
        $type_filter = " WHERE o.user_id IS NULL";
    }
}

$sql = "SELECT o.*, u.name as user_name, u.email,
        GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, '개)') SEPARATOR '<br>') as item_details
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.user_id 
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.product_id
        $type_filter
        GROUP BY o.order_id
        ORDER BY o.created_at DESC";
$result = $conn->query($sql);

// 초기화 로직 (필요시)
if (isset($_GET['action']) && $_GET['action'] == 'reset_orders') {
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    $conn->query("TRUNCATE TABLE order_items");
    $conn->query("TRUNCATE TABLE orders");
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    header("Location: orders-manage.php");
    exit;
}
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
        .status-paid {
            color: #1a73e8;
            font-weight: 700;
            background: #e8f0fe;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
        }

        .status-cancelled {
            color: #d93025;
            font-weight: 700;
            background: #fde8e8;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
        }

        .payment-key {
            font-family: monospace;
            font-size: 11px;
            color: #94a3b8;
            word-break: break-all;
            max-width: 150px;
            display: block;
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
                <a href="statistics.php" class="nav-item <?= $current_page == 'statistics' ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar"></i> 통계 분석
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
            <div class="page-title" style="justify-content: space-between;">
                <div><i class="fas fa-shopping-cart"></i> 주문 관리</div>
                <button onclick="if(confirm('모든 주문을 초기화하시겠습니까?')) location.href='?action=reset_orders'"
                    class="btn-delete"
                    style="padding: 10px 20px; width: auto; height: auto; font-size: 13px; font-weight: 600; border-radius: 8px;">
                    <i class="fas fa-trash-alt"></i> 전체 초기화
                </button>
            </div>

            <!-- 필터 및 검색 -->
            <div class="admin-card" style="margin-bottom: 20px; padding: 15px;">
                <form method="GET" style="display: flex; gap: 15px; align-items: center;">
                    <select name="type"
                        style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                        <option value="">전체 주문</option>
                        <option value="member" <?= ($_GET['type'] ?? '') == 'member' ? 'selected' : '' ?>>회원 주문</option>
                        <option value="guest" <?= ($_GET['type'] ?? '') == 'guest' ? 'selected' : '' ?>>비회원 주문</option>
                    </select>
                    <button type="submit" class="btn-primary"
                        style="padding: 8px 20px; width: auto; height: auto; font-size: 14px; background: #2c3e50;">필터링</button>
                    <?php if (isset($_GET['type'])): ?>
                        <a href="orders-manage.php" style="font-size: 14px; color: #64748b; text-decoration: none;"><i
                                class="fas fa-undo"></i> 초기화</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- 목록 테이블 -->
            <div class="admin-card">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">주문번호</th>
                            <th>주문자 정보</th>
                            <th>주문 상품</th>
                            <th>결제 금액</th>
                            <th>결제 키(Key)</th>
                            <th style="width: 120px;">상태</th>
                            <th style="width: 180px;">주문일시</th>
                            <th style="width: 120px; text-align: center;">관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?= $row['order_id'] ?></strong></td>
                                    <td>
                                        <?php if ($row['user_id']): ?>
                                            <div style="font-weight: 600;">
                                                <?= htmlspecialchars($row['user_name'] ?? '회원') ?>
                                                <span
                                                    style="font-size: 11px; color: #1a73e8; background: #e8f0fe; padding: 2px 6px; border-radius: 4px; margin-left: 5px;">회원</span>
                                            </div>
                                            <div style="font-size: 12px; color: #64748b;">
                                                <?= htmlspecialchars($row['email'] ?? '-') ?>
                                            </div>
                                        <?php else: ?>
                                            <div style="font-weight: 600;">
                                                <?= htmlspecialchars($row['shipping_name'] ?: '비회원') ?>
                                                <span
                                                    style="font-size: 11px; color: #64748b; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; margin-left: 5px;">비회원</span>
                                            </div>
                                            <div style="font-size: 12px; color: #64748b;">
                                                <?= htmlspecialchars($row['shipping_phone'] ?? '-') ?>
                                            </div>
                                            <div
                                                style="font-size: 11px; color: #94a3b8; margin-top: 4px; border-top: 1px dashed #eee; padding-top: 4px;">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?= htmlspecialchars($row['shipping_address'] ?? '-') ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-size: 13px; line-height: 1.5; color: #475569;">
                                            <?= $row['item_details'] ?: '<span style="color: #cbd5e1;">상품 정보 없음</span>' ?>
                                        </div>
                                    </td>
                                    <td><strong
                                            style="color: var(--text-main);"><?= number_format($row['total_amount']) ?>원</strong>
                                    </td>
                                    <td><span class="payment-key"><?= $row['payment_key'] ?: '-' ?></span></td>
                                    <td>
                                        <?php if ($row['status'] == 'paid'): ?>
                                            <span class="status-paid">결제완료</span>
                                        <?php elseif ($row['status'] == 'cancelled'): ?>
                                            <span class="status-cancelled">취소됨</span>
                                        <?php else: ?>
                                            <span class="badge badge-muted"><?= $row['status'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color: #64748b; font-size: 14px;"><?= $row['created_at'] ?></td>
                                    <td style="text-align: center;">
                                        <?php if ($row['status'] == 'paid' && $row['payment_key']): ?>
                                            <button onclick="cancelOrder(<?= $row['order_id'] ?>)" class="action-btn btn-delete"
                                                title="결제 취소">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php else: ?>
                                            <span style="color: #cbd5e1; font-size: 12px;">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="padding: 100px 0; text-align: center; color: #94a3b8;">
                                    <i class="fas fa-receipt"
                                        style="font-size: 48px; display: block; margin-bottom: 20px; opacity: 0.2;"></i>
                                    주문 내역이 없습니다.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        function cancelOrder(orderId) {
            if (confirm('정말 이 주문의 결제를 취소하시겠습니까? (복구 불가)')) {
                fetch('order-cancel.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert('결제가 취소되었습니다.');
                            location.reload();
                        } else {
                            alert('취소 실패: ' + data.message);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('오류 발생');
                    });
            }
        }
    </script>
</body>

</html>