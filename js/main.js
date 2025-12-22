document.addEventListener('DOMContentLoaded', function () {
    // 찜하기 버튼
    const wishlistBtns = document.querySelectorAll('.wishlist-btn, .wishlist-btn-detail');
    wishlistBtns.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const productId = this.dataset.productId || this.getAttribute('data-product-id');
            toggleWishlist(productId, this);
        });
    });

    // 장바구니 추가 버튼
    const addToCartBtns = document.querySelectorAll('.add-to-cart-btn');
    addToCartBtns.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const productId = this.dataset.productId || this.getAttribute('data-product-id');
            const quantity = this.dataset.quantity || 1;
            addToCart(productId, quantity);
        });
    });

    // 이미지 에러 처리
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        img.addEventListener('error', function () {
            this.src = 'https://placehold.co/300x300?text=No+Image';
        });
    });
});

// 찜하기 토글
function toggleWishlist(productId, button) {
    fetch('/api/wishlist-toggle.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const icon = button.querySelector('i');
                if (data.action === 'added') {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    button.classList.add('active');
                    showNotification('찜 목록에 추가되었습니다', 'success');
                } else {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    button.classList.remove('active');
                    showNotification('찜 목록에서 제거되었습니다', 'info');
                }
                updateWishlistCount();
            } else if (data.message === 'not_logged_in') {
                showNotification('로그인이 필요합니다', 'error');
                setTimeout(() => {
                    window.location.href = '/login.php?redirect=' + encodeURIComponent(window.location.href);
                }, 1000);
            } else {
                showNotification('오류가 발생했습니다', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showNotification('오류가 발생했습니다', 'error');
        });
}

// 장바구니 추가
function addToCart(productId, quantity) {
    fetch('/api/cart-add.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId, quantity: parseInt(quantity) })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showNotification('장바구니에 추가되었습니다', 'success');
                updateCartCount();
                if (confirm('장바구니로 이동하시겠습니까?')) {
                    window.location.href = '/cart.php';
                }
            } else if (data.message === 'not_logged_in') {
                showNotification('로그인이 필요합니다', 'error');
                setTimeout(() => {
                    window.location.href = '/login.php?redirect=' + encodeURIComponent(window.location.href);
                }, 1000);
            } else if (data.message === 'out_of_stock') {
                showNotification('재고가 부족합니다', 'error');
            } else {
                showNotification('오류가 발생했습니다', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showNotification('오류가 발생했습니다', 'error');
        });
}

// 장바구니 수량 업데이트
function updateCartCount() {
    fetch('/api/cart-count.php')
        .then(res => res.json())
        .then(data => {
            const badge = document.querySelector('.icon-link .badge');
            if (badge) {
                badge.textContent = data.count;
                badge.style.display = data.count > 0 ? 'flex' : 'none';
            }
        });
}

// 찜하기 수량 업데이트
function updateWishlistCount() {
    fetch('/api/wishlist-count.php')
        .then(res => res.json())
        .then(data => {
            const wishlistBadge = document.querySelector('.icon-link.wishlist .badge');
            if (wishlistBadge) {
                wishlistBadge.textContent = data.count;
                wishlistBadge.style.display = data.count > 0 ? 'flex' : 'none';
            }
        });
}

// 알림 메시지 표시
function showNotification(message, type = 'info') {
    const existing = document.querySelector('.toast-notification');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// 수량 조절
function updateQuantity(input, change) {
    const current = parseInt(input.value);
    const newValue = current + change;
    if (newValue >= 1 && newValue <= 99) input.value = newValue;
}

// 전체 선택
function toggleSelectAll(checkbox) {
    document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = checkbox.checked);
}

// Toast 스타일
const style = document.createElement('style');
style.textContent = `
.toast-notification {
    position: fixed;
    top: 100px;
    right: 20px;
    background: white;
    padding: 16px 24px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 9999;
    transform: translateX(400px);
    transition: transform 0.3s ease;
    font-weight: 500;
}
.toast-notification.show { transform: translateX(0); }
.toast-success { border-left: 4px solid #27ae60; color: #27ae60; }
.toast-error { border-left: 4px solid #e74c3c; color: #e74c3c; }
.toast-info { border-left: 4px solid #3498db; color: #3498db; }
`;
document.head.appendChild(style);
