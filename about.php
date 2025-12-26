<?php
$page_title = '브랜드 소개';
require_once 'includes/db.php';
require_once 'includes/header.php';
?>

<div class="about-page">
    <!-- Hero Section -->
    <div class="about-hero">
        <div class="container">
            <h1>COZY-DECO Story</h1>
            <p>당신의 공간을 더욱 특별하게, 일상을 더욱 따뜻하게</p>
        </div>
    </div>

    <!-- Vision Section -->
    <section class="vision-section">
        <div class="container">
            <div class="vision-content">
                <div class="vision-text">
                    <span class="subtitle">Our Philosophy</span>
                    <h2>공간은 그 사람의 거울입니다</h2>
                    <p>
                        COZY-DECO는 단순히 가구를 파는 것이 아니라, <br>
                        당신의 취향과 라이프스타일이 묻어나는 공간을 제안합니다.
                    </p>
                    <p>
                        우리는 좋은 디자인이 삶의 질을 높인다고 믿습니다.<br>
                        바쁜 일상 속에서 편안한 휴식이 되는 공간,<br>
                        사랑하는 사람들과의 추억이 깃드는 공간을 만들어갑니다.
                    </p>
                </div>
                <div class="vision-image">
                    <img src="images/hero_living.jpg" alt="Cozy Interior"
                        onerror="this.src='https://placehold.co/600x400?text=Brand+Image'">
                </div>
            </div>
        </div>
    </section>

    <!-- Core Values -->
    <section class="values-section">
        <div class="container">
            <h2 class="section-title" style="text-align: center; margin-bottom: 50px;">Core Values</h2>
            <div class="values-grid">
                <div class="value-card">
                    <div class="icon">✨</div>
                    <h3>Premium Quality</h3>
                    <p>엄선된 자재와 장인 정신으로<br>오래 사용할 수 있는 가치를 만듭니다.</p>
                </div>
                <div class="value-card">
                    <div class="icon">🎨</div>
                    <h3>Unique Design</h3>
                    <p>트렌드를 선도하면서도<br>시간이 지나도 변치 않는 아름다움을 추구합니다.</p>
                </div>
                <div class="value-card">
                    <div class="icon">🌿</div>
                    <h3>Eco-Friendly</h3>
                    <p>지속 가능한 미래를 위해<br>환경 친화적인 소재와 공정을 지향합니다.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact CTA -->
    <section class="cta-section">
        <div class="container">
            <h2>당신의 공간을 변화시킬 준비가 되셨나요?</h2>
            <p>COZY-DECO의 전문가들이 도와드립니다.</p>
            <a href="contact.php" class="btn btn-primary btn-lg">문의하기</a>
        </div>
    </section>
</div>

<style>
    /* About Page Specific Styles */
    .about-hero {
        background-image: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), url('images/hero_dining.jpg');
        background-size: cover;
        background-position: center;
        height: 400px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: white;
        margin-bottom: 60px;
    }

    .about-hero h1 {
        font-size: 48px;
        font-weight: 700;
        margin-bottom: 20px;
    }

    .about-hero p {
        font-size: 20px;
        font-weight: 300;
        opacity: 0.9;
    }

    .vision-section {
        padding: 60px 0;
        background-color: #fff;
        margin-bottom: 60px;
    }

    .vision-content {
        display: flex;
        align-items: center;
        gap: 60px;
    }

    .vision-text {
        flex: 1;
    }

    .vision-image {
        flex: 1;
    }

    .vision-image img {
        width: 100%;
        border-radius: 12px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }

    .vision-text .subtitle {
        color: var(--primary-color);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 10px;
        display: inline-block;
    }

    .vision-text h2 {
        font-size: 36px;
        margin-bottom: 24px;
        line-height: 1.3;
    }

    .vision-text p {
        color: #666;
        margin-bottom: 20px;
        font-size: 16px;
        line-height: 1.8;
    }

    .values-section {
        background-color: #f8f9fa;
        padding: 80px 0;
    }

    .values-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
    }

    .value-card {
        background: white;
        padding: 40px;
        border-radius: 16px;
        text-align: center;
        transition: transform 0.3s ease;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
    }

    .value-card:hover {
        transform: translateY(-10px);
    }

    .value-card .icon {
        font-size: 48px;
        margin-bottom: 24px;
    }

    .value-card h3 {
        font-size: 20px;
        margin-bottom: 16px;
        color: var(--primary-color);
    }

    .value-card p {
        color: #777;
        line-height: 1.6;
    }

    .cta-section {
        padding: 100px 0;
        text-align: center;
    }

    .cta-section h2 {
        font-size: 32px;
        margin-bottom: 16px;
    }

    .cta-section p {
        color: #666;
        margin-bottom: 30px;
        font-size: 18px;
    }

    .btn-lg {
        padding: 15px 40px;
        font-size: 18px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .vision-content {
            flex-direction: column;
            gap: 40px;
        }

        .values-grid {
            grid-template-columns: 1fr;
        }

        .about-hero h1 {
            font-size: 36px;
        }
    }
</style>

<?php require_once 'includes/footer.php'; ?>