<?php
$currentPage = 'about';
$pageTitle = '회사소개';
require_once 'includes/sub_layout.php';
include 'head.php';

// 서브페이지 레이아웃 시작
startSubPage('회사소개', 'about');

// 사이드바
companySidebar('about');
?>

<main class="sub-content">
    <div class="content-header">
        <h2>회사소개</h2>
        <p>충남스틸은 믿을 수 있는 철강 파트너입니다.</p>
    </div>

    <div class="content-body">

<style>
/* ===== About Page Premium Styles ===== */

/* CSS Variables */
:root {
    --about-primary: #1565C0;
    --about-primary-dark: #0D47A1;
    --about-primary-light: #42A5F5;
    --about-accent: #FF6B35;
    --about-gradient: linear-gradient(135deg, #1565C0 0%, #0D47A1 100%);
    --about-gradient-light: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%);
    --about-text-dark: #1a1a2e;
    --about-text-gray: #5a5a6e;
    --about-bg-light: #f8fafc;
    --about-shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --about-shadow-md: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    --about-shadow-lg: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
    --about-shadow-blue: 0 20px 40px -10px rgba(21, 101, 192, 0.3);
}

/* Animation Keyframes */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}

/* Section Container */
.about-section-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
}

/* ===== Hero Section ===== */
.about-hero {
    background: url('/img/company.png') center center / cover no-repeat;
    padding: 100px 0;
    position: relative;
    overflow: hidden;
    min-height: 450px;
    display: flex;
    align-items: center;
}

.about-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(21, 101, 192, 0.85) 0%, rgba(13, 71, 161, 0.75) 50%, rgba(0, 0, 0, 0.6) 100%);
    pointer-events: none;
}

.about-hero-content {
    position: relative;
    z-index: 1;
    text-align: center;
}

.about-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    padding: 10px 24px;
    border-radius: 50px;
    margin-bottom: 32px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    animation: fadeInUp 0.6s ease-out;
}

.about-hero-badge span {
    color: #fff;
    font-size: 14px;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.about-hero h3 {
    font-size: 48px;
    font-weight: 800;
    color: #fff;
    margin-bottom: 24px;
    line-height: 1.2;
    animation: fadeInUp 0.6s ease-out 0.1s both;
}

.about-hero-subtitle {
    font-size: 20px;
    color: rgba(255, 255, 255, 0.9);
    max-width: 700px;
    margin: 0 auto 40px;
    line-height: 1.8;
    animation: fadeInUp 0.6s ease-out 0.2s both;
}

.about-hero-stats {
    display: flex;
    justify-content: center;
    gap: 60px;
    animation: fadeInUp 0.6s ease-out 0.3s both;
}

.about-stat-item {
    text-align: center;
}

.about-stat-number {
    font-size: 42px;
    font-weight: 800;
    color: #fff;
    display: block;
    line-height: 1;
}

.about-stat-label {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.8);
    margin-top: 8px;
    display: block;
    font-weight: 500;
}

/* ===== Introduction Section ===== */
.about-intro-section {
    padding: 80px 0;
    background: #fff;
}

.about-intro-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
}

.about-intro-visual {
    position: relative;
}

.about-intro-card {
    background: var(--about-gradient);
    border-radius: 24px;
    padding: 48px;
    color: #fff;
    position: relative;
    overflow: hidden;
}

.about-intro-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    pointer-events: none;
}

.about-intro-icon {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 24px;
}

.about-intro-icon svg {
    width: 40px;
    height: 40px;
    fill: #fff;
}

.about-intro-card h4 {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 16px;
}

.about-intro-card p {
    font-size: 16px;
    line-height: 1.7;
    opacity: 0.9;
}

.about-floating-badge {
    position: absolute;
    bottom: -20px;
    right: -20px;
    background: #fff;
    border-radius: 16px;
    padding: 20px 28px;
    box-shadow: var(--about-shadow-lg);
    animation: float 3s ease-in-out infinite;
}

.about-floating-badge span {
    font-size: 24px;
    font-weight: 800;
    color: var(--about-primary);
}

.about-floating-badge small {
    display: block;
    font-size: 12px;
    color: var(--about-text-gray);
    margin-top: 4px;
}

.about-intro-text h4 {
    font-size: 32px;
    font-weight: 700;
    color: var(--about-text-dark);
    margin-bottom: 24px;
    line-height: 1.4;
}

.about-intro-text h4 span {
    color: var(--about-primary);
}

.about-intro-paragraphs p {
    font-size: 16px;
    line-height: 1.9;
    color: var(--about-text-gray);
    margin-bottom: 20px;
}

.about-intro-paragraphs p:last-child {
    margin-bottom: 0;
}

.about-highlight-text {
    background: var(--about-gradient-light);
    padding: 24px;
    border-radius: 16px;
    border-left: 4px solid var(--about-primary);
    margin-top: 24px;
}

.about-highlight-text p {
    margin: 0;
    font-weight: 500;
    color: var(--about-text-dark);
}

/* ===== Values Section ===== */
.about-values-section {
    padding: 80px 0;
    background: var(--about-bg-light);
}

.about-section-header {
    text-align: center;
    margin-bottom: 60px;
}

.about-section-tag {
    display: inline-block;
    background: var(--about-gradient);
    color: #fff;
    padding: 8px 20px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 20px;
}

.about-section-header h3 {
    font-size: 40px;
    font-weight: 800;
    color: var(--about-text-dark);
    margin-bottom: 16px;
}

.about-section-header p {
    font-size: 18px;
    color: var(--about-text-gray);
    max-width: 600px;
    margin: 0 auto;
}

.about-values-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 32px;
}

.about-value-card {
    background: #fff;
    border-radius: 24px;
    padding: 48px 36px;
    text-align: center;
    position: relative;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.about-value-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--about-gradient);
    transform: scaleX(0);
    transition: transform 0.4s ease;
}

.about-value-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--about-shadow-lg);
}

.about-value-card:hover::before {
    transform: scaleX(1);
}

.about-value-icon {
    width: 90px;
    height: 90px;
    background: var(--about-gradient-light);
    border-radius: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 28px;
    transition: all 0.4s ease;
}

.about-value-card:hover .about-value-icon {
    background: var(--about-gradient);
    transform: scale(1.1) rotate(5deg);
}

.about-value-icon svg {
    width: 44px;
    height: 44px;
    fill: var(--about-primary);
    transition: fill 0.4s ease;
}

.about-value-card:hover .about-value-icon svg {
    fill: #fff;
}

.about-value-card h4 {
    font-size: 22px;
    font-weight: 700;
    color: var(--about-text-dark);
    margin-bottom: 16px;
}

.about-value-card p {
    font-size: 15px;
    color: var(--about-text-gray);
    line-height: 1.7;
}

/* ===== Business Section ===== */
.about-business-section {
    padding: 80px 0;
    background: #fff;
}

.about-business-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
}

.about-business-card {
    background: #fff;
    border-radius: 20px;
    padding: 40px 28px;
    text-align: center;
    position: relative;
    overflow: hidden;
    border: 2px solid #E8EDF5;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
}

.about-business-card::after {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--about-gradient);
    opacity: 0;
    transition: opacity 0.4s ease;
    z-index: 0;
}

.about-business-card:hover {
    border-color: var(--about-primary);
    transform: translateY(-6px);
    box-shadow: var(--about-shadow-blue);
}

.about-business-card:hover::after {
    opacity: 1;
}

.about-business-card > * {
    position: relative;
    z-index: 1;
}

.about-business-icon {
    width: 70px;
    height: 70px;
    background: var(--about-gradient-light);
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
    transition: all 0.4s ease;
}

.about-business-card:hover .about-business-icon {
    background: rgba(255, 255, 255, 0.2);
}

.about-business-icon svg {
    width: 36px;
    height: 36px;
    fill: var(--about-primary);
    transition: fill 0.4s ease;
}

.about-business-card:hover .about-business-icon svg {
    fill: #fff;
}

.about-business-card h4 {
    font-size: 18px;
    font-weight: 700;
    color: var(--about-text-dark);
    margin-bottom: 12px;
    transition: color 0.4s ease;
}

.about-business-card:hover h4 {
    color: #fff;
}

.about-business-card p {
    font-size: 14px;
    color: var(--about-text-gray);
    line-height: 1.6;
    transition: color 0.4s ease;
}

.about-business-card:hover p {
    color: rgba(255, 255, 255, 0.9);
}

/* ===== Certificates Section ===== */
.about-certificates-section {
    padding: 80px 0;
    background: linear-gradient(180deg, var(--about-bg-light) 0%, #fff 100%);
}

.about-certificates-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
    max-width: 900px;
    margin: 0 auto;
}

.about-certificate-card {
    background: #fff;
    border-radius: 16px;
    padding: 28px 32px;
    display: flex;
    align-items: center;
    gap: 20px;
    border: 1px solid #E8EDF5;
    transition: all 0.3s ease;
}

.about-certificate-card:hover {
    transform: translateX(8px);
    box-shadow: var(--about-shadow-md);
    border-color: var(--about-primary-light);
}

.about-certificate-icon {
    width: 56px;
    height: 56px;
    background: var(--about-gradient);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.about-certificate-icon svg {
    width: 28px;
    height: 28px;
    fill: #fff;
}

.about-certificate-content h5 {
    font-size: 16px;
    font-weight: 700;
    color: var(--about-text-dark);
    margin-bottom: 6px;
}

.about-certificate-content p {
    font-size: 14px;
    color: var(--about-text-gray);
    margin: 0;
}

/* ===== CTA Section ===== */
.about-cta-section {
    padding: 80px 0;
    background: var(--about-gradient);
    position: relative;
    overflow: hidden;
}

.about-cta-section::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 60%;
    height: 200%;
    background: radial-gradient(ellipse, rgba(255,255,255,0.1) 0%, transparent 70%);
    pointer-events: none;
}

.about-cta-content {
    position: relative;
    z-index: 1;
    text-align: center;
    color: #fff;
}

.about-cta-content h3 {
    font-size: 36px;
    font-weight: 800;
    margin-bottom: 16px;
}

.about-cta-content p {
    font-size: 18px;
    opacity: 0.9;
    margin-bottom: 32px;
}

.about-cta-buttons {
    display: flex;
    justify-content: center;
    gap: 16px;
}

.about-cta-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 16px 32px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}

.about-cta-btn-primary {
    background: #fff;
    color: var(--about-primary);
}

.about-cta-btn-primary:hover {
    background: #f0f0f0;
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.about-cta-btn-secondary {
    background: rgba(255, 255, 255, 0.15);
    color: #fff;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.about-cta-btn-secondary:hover {
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.5);
}

/* ===== Responsive Design ===== */
@media (max-width: 1024px) {
    .about-intro-grid {
        grid-template-columns: 1fr;
        gap: 40px;
    }

    .about-business-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .about-hero {
        padding: 60px 0;
    }

    .about-hero h3 {
        font-size: 32px;
    }

    .about-hero-stats {
        flex-direction: column;
        gap: 30px;
    }

    .about-values-grid,
    .about-business-grid {
        grid-template-columns: 1fr;
    }

    .about-certificates-grid {
        grid-template-columns: 1fr;
    }

    .about-section-header h3 {
        font-size: 28px;
    }

    .about-cta-buttons {
        flex-direction: column;
        align-items: center;
    }

    .about-cta-btn {
        width: 100%;
        max-width: 280px;
        justify-content: center;
    }
}
</style>

<!-- 히어로 섹션 -->
<section class="about-hero">
    <div class="about-section-container">
        <div class="about-hero-content">
            <div class="about-hero-badge">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
                </svg>
                <span>Since 1974 - 50년 전통의 철강 전문기업</span>
            </div>
            <h3>충남스틸을 소개합니다</h3>
            <p class="about-hero-subtitle">
                반세기 철강유통 노하우를 바탕으로 고객과 함께 성장하는<br>
                대한민국 대표 철강종합판매대리점
            </p>
            <div class="about-hero-stats">
                <div class="about-stat-item">
                    <span class="about-stat-number">50+</span>
                    <span class="about-stat-label">Years of Experience</span>
                </div>
                <div class="about-stat-item">
                    <span class="about-stat-number">1,000+</span>
                    <span class="about-stat-label">Satisfied Clients</span>
                </div>
                <div class="about-stat-item">
                    <span class="about-stat-number">KS</span>
                    <span class="about-stat-label">Certified Products</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 회사 소개 섹션 -->
<section class="about-intro-section">
    <div class="about-section-container">
        <div class="about-intro-grid">
            <div class="about-intro-visual">
                <div class="about-intro-card">
                    <div class="about-intro-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2L2 7L12 12L22 7L12 2Z"/>
                            <path d="M2 17L12 22L22 17"/>
                            <path d="M2 12L12 17L22 12"/>
                        </svg>
                    </div>
                    <h4>3대 경영이념</h4>
                    <p>최고의 제품, 최고의 서비스, 거품없는 가격으로 고객 여러분과 함께 성장합니다.</p>
                </div>
                <div class="about-floating-badge">
                    <span>50년</span>
                    <small>철강유통 노하우</small>
                </div>
            </div>
            <div class="about-intro-text">
                <h4>신뢰할 수 있는 <span>철강 파트너</span></h4>
                <div class="about-intro-paragraphs">
                    <p>당사에서는 철강재 판매, 관급대체, 보관창고 및 중고제품, 철스크랩 수거까지 철강 업무 전체를 병행합니다. 납품에서 수거까지 고객 여러분의 편의를 도모하며, 철강재 경매 및 이벤트 판매 등 다양한 방법으로 최저가 납품을 원칙으로 업무에 임하고 있습니다.</p>
                    <p>국내 1군 KS 제강사 대리점으로 안전하고 검증 완료된 제품만을 납품하며, 철강 산업 1차 유통으로 고객 여러분에게 거품없는 가격으로 원가 절감을 실현해 드립니다.</p>
                </div>
                <div class="about-highlight-text">
                    <p>충남스틸 주식회사는 각종 매스컴(일간신문 및 철강신문 등)에 보도되었으며, 금융기관 우수기업으로 선정된 공식 철강 업체입니다.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 경영이념 섹션 -->
<section class="about-values-section">
    <div class="about-section-container">
        <div class="about-section-header">
            <span class="about-section-tag">Core Values</span>
            <h3>경영이념</h3>
            <p>충남스틸이 추구하는 핵심 가치입니다</p>
        </div>
        <div class="about-values-grid">
            <div class="about-value-card">
                <div class="about-value-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
                    </svg>
                </div>
                <h4>품질제일주의</h4>
                <p>KS 인증 제품만을 엄선하여 최고 품질의 철강재를 고객에게 제공합니다. 품질에 대한 타협은 없습니다.</p>
            </div>
            <div class="about-value-card">
                <div class="about-value-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M17 21V19C17 16.79 14.76 15 12 15C9.24 15 7 16.79 7 19V21"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <h4>고객만족경영</h4>
                <p>고객의 요구를 최우선으로 생각하며, 맞춤형 솔루션과 신속한 서비스로 최고의 만족을 제공합니다.</p>
            </div>
            <div class="about-value-card">
                <div class="about-value-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22Z"/>
                        <path d="M9 12L11 14L15 10" stroke="#1565C0" stroke-width="2" fill="none"/>
                    </svg>
                </div>
                <h4>신뢰와 성실</h4>
                <p>투명하고 정직한 경영으로 50년간 쌓아온 신뢰를 바탕으로 고객과 함께 성장합니다.</p>
            </div>
        </div>
    </div>
</section>

<!-- 사업분야 섹션 -->
<section class="about-business-section">
    <div class="about-section-container">
        <div class="about-section-header">
            <span class="about-section-tag">Business Areas</span>
            <h3>사업분야</h3>
            <p>철강 산업의 모든 영역을 아우르는 종합 서비스</p>
        </div>
        <div class="about-business-grid">
            <div class="about-business-card">
                <div class="about-business-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M3 21H21M3 7V21M21 7V21M6 7H18M6 7V4C6 3.45 6.45 3 7 3H17C17.55 3 18 3.45 18 4V7M9 14H15M9 17H15M9 11H15"/>
                    </svg>
                </div>
                <h4>철강재 유통</h4>
                <p>H형강, 철근, 강관, 형강 등 다양한 철강 제품을 최저가로 공급합니다</p>
            </div>
            <div class="about-business-card">
                <div class="about-business-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M14.7 6.3C14.3 5.9 13.7 5.9 13.3 6.3L3 16.6V21H7.4L17.7 10.7C18.1 10.3 18.1 9.7 17.7 9.3L14.7 6.3Z"/>
                        <path d="M21 2L22 3L20 5L19 4L21 2Z"/>
                    </svg>
                </div>
                <h4>철강 가공</h4>
                <p>절단, 절곡, 용접 등 고객 맞춤형 정밀 가공 서비스를 제공합니다</p>
            </div>
            <div class="about-business-card">
                <div class="about-business-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM12 20C7.59 20 4 16.41 4 12C4 7.59 7.59 4 12 4C16.41 4 20 7.59 20 12C20 16.41 16.41 20 12 20Z"/>
                        <path d="M12 6V12L16 14"/>
                    </svg>
                </div>
                <h4>기술 컨설팅</h4>
                <p>철강 제품 선정 및 시공 관련 전문 기술 지원을 제공합니다</p>
            </div>
            <div class="about-business-card">
                <div class="about-business-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M20 8H17V4H3C1.9 4 1 4.9 1 6V17H3C3 18.66 4.34 20 6 20C7.66 20 9 18.66 9 17H15C15 18.66 16.34 20 18 20C19.66 20 21 18.66 21 17H23V12L20 8ZM6 18.5C5.17 18.5 4.5 17.83 4.5 17C4.5 16.17 5.17 15.5 6 15.5C6.83 15.5 7.5 16.17 7.5 17C7.5 17.83 6.83 18.5 6 18.5ZM19.5 9.5L21.46 12H17V9.5H19.5ZM18 18.5C17.17 18.5 16.5 17.83 16.5 17C16.5 16.17 17.17 15.5 18 15.5C18.83 15.5 19.5 16.17 19.5 17C19.5 17.83 18.83 18.5 18 18.5Z"/>
                    </svg>
                </div>
                <h4>물류 서비스</h4>
                <p>신속하고 안전한 제품 운송 및 보관 창고 서비스를 제공합니다</p>
            </div>
        </div>
    </div>
</section>

<!-- 인증 및 수상 섹션 -->
<section class="about-certificates-section">
    <div class="about-section-container">
        <div class="about-section-header">
            <span class="about-section-tag">Certifications</span>
            <h3>인증 및 수상</h3>
            <p>공인된 품질과 신뢰의 증거</p>
        </div>
        <div class="about-certificates-grid">
            <div class="about-certificate-card">
                <div class="about-certificate-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
                    </svg>
                </div>
                <div class="about-certificate-content">
                    <h5>ISO 9001:2015</h5>
                    <p>품질경영시스템 국제 인증</p>
                </div>
            </div>
            <div class="about-certificate-card">
                <div class="about-certificate-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M19 5H5C3.9 5 3 5.9 3 7V17C3 18.1 3.9 19 5 19H19C20.1 19 21 18.1 21 17V7C21 5.9 20.1 5 19 5ZM12 17L6 11L7.41 9.59L12 14.17L18.59 7.58L20 9L12 17Z"/>
                    </svg>
                </div>
                <div class="about-certificate-content">
                    <h5>2019 충청남도 우수기업</h5>
                    <p>지역 산업 발전 공로</p>
                </div>
            </div>
            <div class="about-certificate-card">
                <div class="about-certificate-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z"/>
                    </svg>
                </div>
                <div class="about-certificate-content">
                    <h5>2018 일자리 창출 우수기업</h5>
                    <p>고용노동부 선정</p>
                </div>
            </div>
            <div class="about-certificate-card">
                <div class="about-certificate-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2L2 7L12 12L22 7L12 2Z"/>
                        <path d="M2 17L12 22L22 17M2 12L12 17L22 12"/>
                    </svg>
                </div>
                <div class="about-certificate-content">
                    <h5>한국철강협회 정회원사</h5>
                    <p>철강 업계 공식 인증</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA 섹션 -->
<section class="about-cta-section">
    <div class="about-section-container">
        <div class="about-cta-content">
            <h3>충남스틸과 함께하세요</h3>
            <p>50년 노하우의 철강 전문가가 최적의 솔루션을 제공합니다</p>
            <div class="about-cta-buttons">
                <a href="/quote.php" class="about-cta-btn about-cta-btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3ZM17 13H13V17H11V13H7V11H11V7H13V11H17V13Z"/>
                    </svg>
                    견적 문의하기
                </a>
                <a href="/contact.php" class="about-cta-btn about-cta-btn-secondary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M6.62 10.79C8.06 13.62 10.38 15.94 13.21 17.38L15.41 15.18C15.69 14.9 16.08 14.82 16.43 14.93C17.55 15.3 18.75 15.5 20 15.5C20.55 15.5 21 15.95 21 16.5V20C21 20.55 20.55 21 20 21C10.61 21 3 13.39 3 4C3 3.45 3.45 3 4 3H7.5C8.05 3 8.5 3.45 8.5 4C8.5 5.25 8.7 6.45 9.07 7.57C9.18 7.92 9.1 8.31 8.82 8.59L6.62 10.79Z"/>
                    </svg>
                    연락처 보기
                </a>
            </div>
        </div>
    </div>
</section>

    </div>
</main>

<?php 
endSubPage();
include 'tail.php'; 
?>