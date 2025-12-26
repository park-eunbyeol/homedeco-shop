<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';

// 관리자 권한 확인
if (!is_admin()) {
    redirect('login.php');
}

$page_title = '상품 관리';
$current_page = 'products';

// 데이터 정정: 판매중지된 상품의 재고를 0으로 강제 동기화 (관제용)
$conn->query("UPDATE products SET stock = 0 WHERE is_active = 0");

// 검색 및 필터
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$category = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// 상품 조회
$where = [];
if (!empty($search)) {
    $where[] = "(name LIKE '%$search%' OR description LIKE '%$search%')";
}
if ($category > 0) {
    $where[] = "category_id = $category";
}
if ($filter === 'no_image') {
    $where[] = "(main_image IS NULL OR main_image = '' OR main_image LIKE '%placehold.co%')";
} elseif ($filter === 'out_of_stock') {
    $where[] = "stock <= 0";
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        $where_clause 
        ORDER BY p.created_at DESC";
$products = $conn->query($sql);

// 카테고리 목록
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
    <style>
        .product-thumb {
            width: 45px;
            height: 45px;
            border-radius: 8px;
            object-fit: cover;
        }

        .stock-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
        }

        .stock-good {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .stock-low {
            background: #fff3e0;
            color: #ef6c00;
        }

        .badge-danger {
            background: #ffebee;
            color: #c62828;
        }

        .stock-out {
            background: #ffebee;
            color: #c62828;
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
            width: 95%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            box-sizing: border-box;
        }

        .modal-content * {
            box-sizing: border-box;
        }

        .modal-header {
            padding: 25px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 40px 50px;
            background: #fff;
        }

        .form-group {
            margin-bottom: 30px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 700;
            color: #334155;
            letter-spacing: -0.02em;
            margin-bottom: 0;
            /* Handled by flex gap */
        }

        .form-control {
            width: 100%;
            padding: 14px 18px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            color: #1e293b;
            background-color: #f8fafc;
            transition: all 0.2s ease;
            outline: none;
        }

        .form-control:focus {
            border-color: var(--accent-color);
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
            line-height: 1.6;
        }

        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-add-product {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-add-product:hover {
            background: #2980b9;
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
            <div class="page-title" style="justify-content: space-between;">
                <div><i class="fas fa-box"></i> 상품 관리</div>
                <button class="btn-add-product" onclick="showAddProduct()">
                    <i class="fas fa-plus"></i> 상품 추가
                </button>
            </div>

            <!-- 검색 및 필터 -->
            <div class="admin-card">
                <form method="GET" class="filter-bar">
                    <div class="search-wrapper">
                        <input type="text" name="search" placeholder="상품명 또는 설명 검색"
                            value="<?= htmlspecialchars($search) ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </div>
                    <select name="category" onchange="this.form.submit()" class="status-select">
                        <option value="0">전체 카테고리</option>
                        <?php
                        $categories->data_seek(0);
                        while ($cat = $categories->fetch_assoc()):
                            ?>
                            <option value="<?= $cat['category_id'] ?>" <?= $category == $cat['category_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    <select name="filter" onchange="this.form.submit()" class="status-select">
                        <option value="">전체 상태</option>
                        <option value="no_image" <?= $filter === 'no_image' ? 'selected' : '' ?>>이미지 없는 상품</option>
                        <option value="out_of_stock" <?= $filter === 'out_of_stock' ? 'selected' : '' ?>>품절(재고0) 상품</option>
                    </select>
                </form>
            </div>

            <!-- 목록 테이블 -->
            <div class="admin-card">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">이미지</th>
                            <th>상품 정보</th>
                            <th style="width: 150px;">카테고리</th>
                            <th style="width: 120px;">가격</th>
                            <th style="width: 100px;">재고</th>
                            <th style="width: 100px;">상태</th>
                            <th style="width: 160px; text-align: center;">관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($products->num_rows > 0): ?>
                            <?php while ($product = $products->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <img src="<?= htmlspecialchars($product['main_image']) ?>"
                                            alt="<?= htmlspecialchars($product['name']) ?>" class="product-thumb"
                                            onerror="this.src='https://placehold.co/60x60?text=No+Image'">
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; font-size: 15px; margin-bottom: 4px;">
                                            <?= htmlspecialchars($product['name']) ?>
                                        </div>
                                        <div style="color: #94a3b8; font-size: 12px;">
                                            <?= htmlspecialchars(mb_substr($product['description'], 0, 40)) ?>...
                                        </div>
                                    </td>
                                    <td><span
                                            style="color: #64748b; font-size: 14px;"><?= htmlspecialchars($product['category_name']) ?></span>
                                    </td>
                                    <td><strong><?= number_format($product['price']) ?>원</strong></td>
                                    <td>
                                        <span
                                            class="stock-badge <?= $product['stock'] > 10 ? 'stock-good' : ($product['stock'] > 0 ? 'stock-low' : 'stock-out') ?>">
                                            <?= $product['stock'] ?>개
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!$product['is_active']): ?>
                                            <span class="badge badge-muted">판매중지</span>
                                        <?php elseif ($product['stock'] <= 0): ?>
                                            <span class="badge badge-danger">판매중지(품절)</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">판매중</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="display: flex; gap: 8px; justify-content: center;">
                                            <button onclick="editProduct(<?= $product['product_id'] ?>)"
                                                class="action-btn btn-edit" title="수정">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button
                                                onclick="toggleProduct(<?= $product['product_id'] ?>, <?= $product['is_active'] ?>)"
                                                class="action-btn btn-toggle" title="상태변경">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                            <?php if (is_super_admin()): ?>
                                            <button onclick="deleteProduct(<?= $product['product_id'] ?>)"
                                                class="action-btn btn-delete" title="삭제">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="padding: 100px 0; text-align: center; color: #94a3b8;">
                                    <i class="fas fa-box-open"
                                        style="font-size: 48px; display: block; margin-bottom: 20px; opacity: 0.2;"></i>
                                    등록된 상품이 없습니다.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- 상품 추가/수정 모달 -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">상품 추가</h3>
                <button onclick="closeModal()"
                    style="background: none; border: none; font-size: 24px; cursor: pointer; color: #94a3b8;">&times;</button>
            </div>
            <form id="productForm" method="POST" action="products-manage.php">
                <div class="modal-body">
                    <input type="hidden" id="product_id" name="product_id">
                    <div class="form-group">
                        <label>카테고리 *</label>
                        <select name="category_id" id="modal_category_id" class="form-control" required>
                            <?php
                            $categories->data_seek(0);
                            while ($cat = $categories->fetch_assoc()):
                                ?>
                                <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>상품명 *</label>
                        <input type="text" name="name" id="modal_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>상품 설명</label>
                        <textarea name="description" id="modal_description" class="form-control" rows="4"></textarea>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; column-gap: 40px; margin-bottom: 30px;">
                        <div class="form-group" style="margin-bottom: 0; min-width: 0;">
                            <label>가격 (원) *</label>
                            <input type="number" name="price" id="modal_price" class="form-control" min="0" required
                                placeholder="0">
                        </div>
                        <div class="form-group" style="margin-bottom: 0; min-width: 0;">
                            <label>재고 (개) *</label>
                            <input type="number" name="stock" id="modal_stock" class="form-control" min="0" required
                                placeholder="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>메인 이미지 URL *</label>
                        <input type="text" name="main_image" id="modal_main_image" class="form-control" required>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; column-gap: 25px; margin-top: 30px;">
                        <div class="form-group" style="margin-bottom: 0; min-width: 0;">
                            <label>스타일</label>
                            <input type="text" name="style_tag" id="modal_style_tag" class="form-control"
                                placeholder="예: 모던">
                        </div>
                        <div class="form-group" style="margin-bottom: 0; min-width: 0;">
                            <label>색상</label>
                            <input type="text" name="color_tag" id="modal_color_tag" class="form-control"
                                placeholder="예: 화이트">
                        </div>
                        <div class="form-group" style="margin-bottom: 0; min-width: 0;">
                            <label>공간</label>
                            <input type="text" name="room_tag" id="modal_room_tag" class="form-control"
                                placeholder="예: 거실">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal()" class="btn-cancel"
                        style="background: #f1f5f9; color: #64748b; font-weight: 600; padding: 10px 20px; border-radius: 8px;">취소</button>
                    <button type="submit" class="btn-add-product" style="padding: 10px 25px;">저장하기</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddProduct() {
            document.getElementById('modalTitle').textContent = '상품 추가';
            document.getElementById('productForm').reset();
            document.getElementById('product_id').value = '';
            document.getElementById('productModal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('productModal').classList.remove('show');
        }

        function editProduct(productId) {
            document.getElementById('modalTitle').textContent = '상품 수정';
            document.getElementById('productForm').reset();

            fetch('api-admin-product-get.php?id=' + productId)
                .then(res => res.json())
                .then(result => {
                    if (result.success) {
                        const data = result.data;
                        document.getElementById('product_id').value = data.product_id;
                        document.getElementById('modal_category_id').value = data.category_id;
                        document.getElementById('modal_name').value = data.name;
                        document.getElementById('modal_description').value = data.description;
                        document.getElementById('modal_price').value = Math.floor(data.price);
                        document.getElementById('modal_stock').value = data.stock;
                        document.getElementById('modal_main_image').value = data.main_image;
                        document.getElementById('modal_style_tag').value = data.style_tag || '';
                        document.getElementById('modal_color_tag').value = data.color_tag || '';
                        document.getElementById('modal_room_tag').value = data.room_tag || '';

                        document.getElementById('productModal').classList.add('show');
                    } else {
                        alert('상품 정보를 불러오는데 실패했습니다: ' + result.message);
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('오류가 발생했습니다.');
                });
        }

        function toggleProduct(productId, currentStatus) {
            const newStatus = currentStatus ? 0 : 1;
            if (confirm('상품 상태를 변경하시겠습니까?')) {
                fetch('api-admin-product-toggle.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId, is_active: newStatus })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) { location.reload(); }
                        else { alert('오류가 발생했습니다.'); }
                    });
            }
        }

        function deleteProduct(productId) {
            if (confirm('정말 삭제하시겠습니까? 이 작업은 취소할 수 없습니다.')) {
                fetch('api-admin-product-delete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) { location.reload(); }
                        else { alert('오류가 발생했습니다.'); }
                    });
            }
        }
    </script>
</body>

</html>

<?php
// API 및 저장 처리 로직 (파일 아래에 포함하거나 별도 분리)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['name'])) {
    $product_id = isset($_POST['product_id']) && !empty($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
    $category_id = (int) $_POST['category_id'];
    $name = clean_input($_POST['name']);
    $description = clean_input($_POST['description']);
    $price = (float) $_POST['price'];
    $stock = (int) $_POST['stock'];
    $main_image = clean_input($_POST['main_image']);
    $style_tag = clean_input($_POST['style_tag']);
    $color_tag = clean_input($_POST['color_tag']);
    $room_tag = clean_input($_POST['room_tag']);

    if ($product_id > 0) {
        $sql = "UPDATE products SET category_id = $category_id, name = '$name', description = '$description', price = $price, stock = $stock, main_image = '$main_image', style_tag = '$style_tag', color_tag = '$color_tag', room_tag = '$room_tag' WHERE product_id = $product_id";
    } else {
        $sql = "INSERT INTO products (category_id, name, description, price, stock, main_image, style_tag, color_tag, room_tag, is_active) VALUES ($category_id, '$name', '$description', $price, $stock, '$main_image', '$style_tag', '$color_tag', '$room_tag', 1)";
    }

    if ($conn->query($sql)) {
        header("Location: products-manage.php");
        exit;
    }
}
?>