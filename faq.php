<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$page_title = '자주 묻는 질문 | COZY-DECO';
include 'includes/header.php';
?>

<main class="faq-container">
    <section class="faq-hero">
        <h1 class="faq-title">무엇을 도와드릴까요?</h1>
        <p class="faq-subtitle">COZY-DECO 이용에 관한 궁금한 점들을 모았습니다.</p>
    </section>

    <section class="faq-content">
        <div class="faq-categories">
            <button class="faq-cat-btn active" data-category="all">전체</button>
            <button class="faq-cat-btn" data-category="shipping">배송/결제</button>
            <button class="faq-cat-btn" data-category="return">교환/반품</button>
            <button class="faq-cat-btn" data-category="product">상품문의</button>
        </div>

        <div class="faq-list">
            <!-- 배송/결제 -->
            <div class="faq-item" data-category="shipping">
                <div class="faq-question">
                    <span>배송은 얼마나 걸리나요?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>평균적으로 영업일 기준 3~5일 정도 소요됩니다. 가구의 경우 지역 및 제작 상황에 따라 최대 14일까지 소요될 수 있으며, 배송 전 해피콜을 통해 일정을 안내해 드립니다.
                    </p>
                </div>
            </div>

            <div class="faq-item" data-category="shipping">
                <div class="faq-question">
                    <span>배송비는 얼마인가요?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>기본 배송비는 3,000원이며, 50,000원 이상 구매 시 무료 배송됩니다. 다만, 제주 및 도서산간 지역은 추가 배송비가 발생할 수 있습니다.</p>
                </div>
            </div>

            <!-- 교환/반품 -->
            <div class="faq-item" data-category="return">
                <div class="faq-question">
                    <span>반품하고 싶은데 어떻게 하나요?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>상품 수령 후 7일 이내에 마이페이지 > 주문내역에서 반품 신청이 가능합니다. 단순 변심에 의한 반품 시 왕복 배송비가 청구될 수 있습니다.</p>
                </div>
            </div>

            <div class="faq-item" data-category="return">
                <div class="faq-question">
                    <span>교환하고 싶을 때는요?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>동일 상품의 옵션 변경 또는 불량으로 인한 교환은 고객센터 접수 후 처리 가능합니다. 상품 가치가 훼손된 경우에는 교환이 어려울 수 있으니 주의 부탁드립니다.</p>
                </div>
            </div>

            <!-- 상품문의 -->
            <div class="faq-item" data-category="product">
                <div class="faq-question">
                    <span>품절된 상품은 언제 재입고 되나요?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>상품마다 재입고 일정이 다릅니다. '재입고 알림 신청'을 해주시면 입고 즉시 문자 또는 앱 알림으로 안내해 드립니다.</p>
                </div>
            </div>

            <div class="faq-item" data-category="product">
                <div class="faq-question">
                    <span>대량 구매 할인이 가능한가요?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>네, 인테리어 법인이나 대량으로 구매하시는 고객님들을 위한 별도의 견적 상담이 가능합니다. 1:1 문의 게시판을 통해 문의 주시면 담당자가 연락드리겠습니다.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="faq-contact">
        <h3>답변을 찾지 못하셨나요?</h3>
        <p>1:1 문의를 남겨주시면 정성껏 답변해 드리겠습니다.</p>
        <a href="contact.php" class="btn-contact">1:1 문의하기</a>
    </section>
</main>

<style>
    .faq-container {
        max-width: 900px;
        margin: 60px auto;
        padding: 0 20px;
        font-family: 'Pretendard', sans-serif;
    }

    .faq-hero {
        text-align: center;
        margin-bottom: 60px;
    }

    .faq-title {
        font-size: 2.5rem;
        font-weight: 800;
        color: #1a202c;
        margin-bottom: 16px;
        letter-spacing: -0.5px;
    }

    .faq-subtitle {
        font-size: 1.1rem;
        color: #718096;
    }

    .faq-categories {
        display: flex;
        justify-content: center;
        gap: 12px;
        margin-bottom: 40px;
    }

    .faq-cat-btn {
        padding: 10px 24px;
        border-radius: 30px;
        border: 1px solid #e2e8f0;
        background: white;
        font-size: 15px;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .faq-cat-btn:hover {
        border-color: #cbd5e0;
        background: #f8fafc;
    }

    .faq-cat-btn.active {
        background: #1a202c;
        color: white;
        border-color: #1a202c;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .faq-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .faq-item {
        background: #fff;
        border: 1px solid #edf2f7;
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .faq-item:hover {
        border-color: #e2e8f0;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    }

    .faq-question {
        padding: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        font-size: 17px;
        font-weight: 600;
        color: #2d3748;
    }

    .faq-question i {
        font-size: 14px;
        color: #a0aec0;
        transition: transform 0.3s ease;
    }

    .faq-answer {
        max-height: 0;
        overflow: hidden;
        background: #f8fafc;
        transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .faq-answer p {
        padding: 24px;
        margin: 0;
        color: #4a5568;
        line-height: 1.7;
        font-size: 15px;
    }

    .faq-item.open {
        border-color: #1a202c;
    }

    .faq-item.open .faq-question {
        color: #1a202c;
    }

    .faq-item.open .faq-question i {
        transform: rotate(180deg);
        color: #1a202c;
    }

    .faq-contact {
        margin-top: 80px;
        text-align: center;
        padding: 60px;
        background: linear-gradient(135deg, #f6f9fc 0%, #edf2f7 100%);
        border-radius: 24px;
    }

    .faq-contact h3 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 12px;
    }

    .faq-contact p {
        color: #718096;
        margin-bottom: 24px;
    }

    .btn-contact {
        display: inline-block;
        padding: 14px 32px;
        background: #1a202c;
        color: white;
        text-decoration: none;
        border-radius: 12px;
        font-weight: 600;
        transition: transform 0.2s;
    }

    .btn-contact:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    @media (max-width: 768px) {
        .faq-title {
            font-size: 2rem;
        }

        .faq-cat-btn {
            padding: 8px 16px;
            font-size: 14px;
        }

        .faq-question {
            padding: 20px;
            font-size: 15px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const faqItems = document.querySelectorAll('.faq-item');
        const catBtns = document.querySelectorAll('.faq-cat-btn');

        // Accordion Logic
        faqItems.forEach(item => {
            const question = item.querySelector('.faq-question');
            const answer = item.querySelector('.faq-answer');

            question.addEventListener('click', () => {
                const isOpen = item.classList.contains('open');

                // Close other items
                faqItems.forEach(i => {
                    i.classList.remove('open');
                    i.querySelector('.faq-answer').style.maxHeight = null;
                });

                if (!isOpen) {
                    item.classList.add('open');
                    answer.style.maxHeight = answer.scrollHeight + "px";
                }
            });
        });

        // Category Filter Logic
        catBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const category = btn.dataset.category;

                catBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                faqItems.forEach(item => {
                    if (category === 'all' || item.dataset.category === category) {
                        item.style.display = 'block';
                        // Optional: reset height if filter changes
                        item.classList.remove('open');
                        item.querySelector('.faq-answer').style.maxHeight = null;
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>