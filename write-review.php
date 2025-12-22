<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';

if (!is_logged_in()) {
    redirect('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

$product_id = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;
$order_id = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;

// 상품 정보 조회
$sql = "SELECT name, main_image FROM products WHERE product_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    echo "<script>alert('상품 정보를 찾을 수 없습니다.'); history.back();</script>";
    exit;
}

$page_title = '리뷰 작성';
require_once 'includes/header.php';
?>

<style>
    .review-form-container {
        max-width: 600px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .product-summary {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 30px;
        padding: 20px;
        background: #fff;
        border: 1px solid #eee;
        border-radius: 8px;
    }

    .product-summary img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid #f0f0f0;
    }

    .rating-group {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        gap: 10px;
        margin-bottom: 20px;
        position: relative;
    }

    .rating-group input {
        position: absolute;
        left: -9999px;
    }

    .rating-group label {
        cursor: pointer;
        color: #ddd;
        font-size: 32px;
        transition: color 0.2s;
    }

    .rating-group input:checked~label,
    .rating-group label:hover,
    .rating-group label:hover~label {
        color: #fdd835;
    }
</style>

<div class="review-form-container">
    <h1 style="text-align: center; margin-bottom: 30px;">리뷰 작성</h1>

    <div class="product-summary">
        <img src="<?= htmlspecialchars($product['main_image']) ?>" alt="">
        <div>
            <h3 style="margin: 0; font-size: 16px;"><?= htmlspecialchars($product['name']) ?></h3>
            <p style="margin: 5px 0 0; color: #666; font-size: 14px;">이 상품은 어떠셨나요?</p>
        </div>
    </div>

    <form id="reviewForm" onsubmit="submitReview(event)">
        <input type="hidden" name="product_id" value="<?= $product_id ?>">
        <input type="hidden" name="order_id" value="<?= $order_id ?>">

        <div class="form-group" style="margin-bottom: 25px;">
            <label style="display: block; margin-bottom: 15px; font-weight: bold; font-size: 16px;">별점 평가</label>
            <div class="rating-group">
                <input type="radio" id="star5" name="rating" value="5" required><label for="star5" title="5점"><i
                        class="fas fa-star"></i></label>
                <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="4점"><i
                        class="fas fa-star"></i></label>
                <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="3점"><i
                        class="fas fa-star"></i></label>
                <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="2점"><i
                        class="fas fa-star"></i></label>
                <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="1점"><i
                        class="fas fa-star"></i></label>
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 25px;">
            <label style="display: block; margin-bottom: 10px; font-weight: bold;">제목</label>
            <input type="text" name="title" class="form-control"
                style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;"
                placeholder="한 줄 평을 남겨주세요" required>
        </div>

        <div class="form-group" style="margin-bottom: 30px;">
            <label style="display: block; margin-bottom: 10px; font-weight: bold;">상세 후기</label>
            <textarea name="content" class="form-control"
                style="width: 100%; height: 200px; padding: 12px; border: 1px solid #ddd; border-radius: 4px; resize: vertical; box-sizing: border-box; line-height: 1.6;"
                placeholder="상품의 품질, 배송, 만족도 등을 자세히 적어주세요." required></textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-block"
            style="width: 100%; padding: 15px; font-size: 16px; font-weight: bold; cursor: pointer; border: none; border-radius: 4px; background-color: #333; color: white;">리뷰
            등록하기</button>
    </form>
</div>

<script>
    function submitReview(e) {
        e.preventDefault();

        // 호환성을 위해 FormData 사용 방식 수정
        const formData = new FormData(e.target);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });

        fetch('./api/review-save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    alert('소중한 리뷰가 등록되었습니다!');
                    window.location.href = 'mypage.php#reviews';
                } else {
                    alert('등록 실패: ' + (res.message || '오류가 발생했습니다'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('서버 통신 오류가 발생했습니다.');
            });
    }
</script>

<?php require_once 'includes/footer.php'; ?>