@php
    $cta = $content['cta'] ?? [];
@endphp

<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2 class="cta-title">{{ $cta['title'] ?? 'Готовы начать?' }}</h2>
            <p class="cta-text">{{ $cta['subtitle'] ?? 'Свяжитесь с нами сегодня и получите бесплатную консультацию' }}</p>
            <div class="cta-actions">
                <a href="#contacts" class="btn btn-cta">{{ $cta['button_text'] ?? 'Получить консультацию' }}</a>
            </div>
        </div>
    </div>
    <div class="cta-bg"></div>
</section>

<style>
.cta-section {
    position: relative;
    padding: 100px 0;
    overflow: hidden;
}

.cta-bg {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
    z-index: -1;
}

.cta-bg::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 60%;
    height: 200%;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.cta-content {
    text-align: center;
    position: relative;
    z-index: 1;
}

.cta-title {
    font-size: 44px;
    font-weight: 800;
    color: white;
    margin-bottom: 20px;
}

.cta-text {
    font-size: 20px;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 40px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.btn-cta {
    background: white;
    color: var(--color-primary);
    padding: 16px 40px;
    font-size: 18px;
}

.btn-cta:hover {
    background: rgba(255, 255, 255, 0.9);
    transform: translateY(-3px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
}

@media (max-width: 768px) {
    .cta-title {
        font-size: 32px;
    }
    
    .cta-text {
        font-size: 17px;
    }
}
</style>
