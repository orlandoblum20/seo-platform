@extends('templates.base.layout')

@section('content')
@php
    $hero = $content['hero'] ?? [];
    $services = $content['services'] ?? [];
    $process = $content['process'] ?? [];
    $pricing = $content['pricing'] ?? [];
@endphp

<!-- Header -->
@include('templates.base.components.header')

<!-- Hero - Service Style -->
<section class="service-hero">
    <div class="container">
        <div class="service-hero-content">
            <div class="service-hero-badge">{{ $hero['badge'] ?? 'Профессиональные услуги' }}</div>
            <h1 class="service-hero-title">{{ $hero['headline'] ?? $site->title }}</h1>
            <p class="service-hero-text">{{ $hero['subheadline'] ?? '' }}</p>
            <div class="service-hero-actions">
                <a href="#contacts" class="btn btn-primary btn-lg">Заказать услугу</a>
                <a href="#services" class="btn btn-secondary btn-lg">Все услуги</a>
            </div>
        </div>
    </div>
    <div class="service-hero-bg">
        <div class="service-hero-pattern"></div>
    </div>
</section>

<!-- Services Grid -->
<section class="section" id="services">
    <div class="container">
        <h2 class="section-title">{{ $services['title'] ?? 'Наши услуги' }}</h2>
        <p class="section-subtitle">{{ $services['subtitle'] ?? 'Полный спектр профессиональных услуг' }}</p>
        
        <div class="service-cards">
            @foreach(($services['items'] ?? []) as $service)
            <div class="service-card-v2">
                <div class="service-card-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <h3>{{ $service['title'] }}</h3>
                <p>{{ $service['description'] }}</p>
                @if(!empty($service['price']))
                <div class="service-card-price">от {{ number_format($service['price'], 0, '', ' ') }} ₽</div>
                @endif
                <a href="#contacts" class="service-card-link">Заказать →</a>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Process -->
<section class="section section-alt" id="process">
    <div class="container">
        <h2 class="section-title">{{ $process['title'] ?? 'Как мы работаем' }}</h2>
        <p class="section-subtitle">{{ $process['subtitle'] ?? 'Простой и понятный процесс' }}</p>
        
        <div class="process-steps">
            @foreach(($process['steps'] ?? []) as $index => $step)
            <div class="process-step">
                <div class="process-step-number">{{ $index + 1 }}</div>
                <h3>{{ $step['title'] }}</h3>
                <p>{{ $step['description'] }}</p>
            </div>
            @if(!$loop->last)
            <div class="process-step-arrow">→</div>
            @endif
            @endforeach
        </div>
    </div>
</section>

<!-- Features -->
@include('templates.base.components.features')

<!-- Pricing (if available) -->
@if(!empty($pricing['plans']))
<section class="section" id="pricing">
    <div class="container">
        <h2 class="section-title">{{ $pricing['title'] ?? 'Тарифы' }}</h2>
        <p class="section-subtitle">{{ $pricing['subtitle'] ?? 'Выберите подходящий тариф' }}</p>
        
        <div class="pricing-grid">
            @foreach($pricing['plans'] as $plan)
            <div class="pricing-card {{ ($plan['popular'] ?? false) ? 'pricing-card-popular' : '' }}">
                @if($plan['popular'] ?? false)
                <div class="pricing-badge">Популярный</div>
                @endif
                <h3 class="pricing-name">{{ $plan['name'] }}</h3>
                <div class="pricing-price">
                    <span class="pricing-amount">{{ number_format($plan['price'], 0, '', ' ') }}</span>
                    <span class="pricing-currency">₽</span>
                </div>
                <p class="pricing-description">{{ $plan['description'] ?? '' }}</p>
                <ul class="pricing-features">
                    @foreach(($plan['features'] ?? []) as $feature)
                    <li>✓ {{ $feature }}</li>
                    @endforeach
                </ul>
                <a href="#contacts" class="btn {{ ($plan['popular'] ?? false) ? 'btn-primary' : 'btn-secondary' }} btn-block">
                    Выбрать
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Testimonials -->
@include('templates.base.components.testimonials')

<!-- FAQ -->
@include('templates.base.components.faq')

<!-- CTA -->
@include('templates.base.components.cta')

<!-- Contacts -->
@include('templates.base.components.contacts')

<!-- Footer -->
@include('templates.base.components.footer')

<style>
/* Service Hero */
.service-hero {
    min-height: 90vh;
    display: flex;
    align-items: center;
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
    color: white;
    padding: 120px 0 80px;
}

.service-hero-bg {
    position: absolute;
    inset: 0;
    overflow: hidden;
}

.service-hero-pattern {
    position: absolute;
    width: 200%;
    height: 200%;
    top: -50%;
    left: -50%;
    background-image: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
    background-size: 40px 40px;
    animation: patternMove 20s linear infinite;
}

@keyframes patternMove {
    0% { transform: translate(0, 0); }
    100% { transform: translate(40px, 40px); }
}

.service-hero-content {
    position: relative;
    z-index: 1;
    max-width: 700px;
}

.service-hero-badge {
    display: inline-block;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    padding: 8px 20px;
    border-radius: 100px;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 24px;
}

.service-hero-title {
    font-size: 56px;
    font-weight: 800;
    line-height: 1.1;
    margin-bottom: 24px;
}

.service-hero-text {
    font-size: 20px;
    opacity: 0.9;
    line-height: 1.7;
    margin-bottom: 40px;
}

.service-hero-actions {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.btn-lg {
    padding: 16px 36px;
    font-size: 17px;
}

/* Service Cards V2 */
.service-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 32px;
}

.service-card-v2 {
    background: white;
    padding: 40px 32px;
    border-radius: 20px;
    border: 1px solid rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.service-card-v2:hover {
    transform: translateY(-8px);
    box-shadow: 0 25px 50px rgba(0,0,0,0.1);
}

.service-card-icon {
    width: 64px;
    height: 64px;
    background: var(--color-surface);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-primary);
    margin-bottom: 24px;
}

.service-card-v2 h3 {
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 12px;
}

.service-card-v2 p {
    color: var(--color-text-muted);
    font-size: 15px;
    line-height: 1.7;
    margin-bottom: 20px;
}

.service-card-price {
    font-size: 24px;
    font-weight: 700;
    color: var(--color-primary);
    margin-bottom: 16px;
}

.service-card-link {
    color: var(--color-primary);
    font-weight: 600;
    text-decoration: none;
}

/* Process Steps */
.process-steps {
    display: flex;
    align-items: flex-start;
    justify-content: center;
    gap: 24px;
    flex-wrap: wrap;
}

.process-step {
    flex: 1;
    min-width: 200px;
    max-width: 280px;
    text-align: center;
}

.process-step-number {
    width: 60px;
    height: 60px;
    background: var(--color-primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: 700;
    margin: 0 auto 20px;
}

.process-step h3 {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 12px;
}

.process-step p {
    color: var(--color-text-muted);
    font-size: 14px;
    line-height: 1.6;
}

.process-step-arrow {
    font-size: 32px;
    color: var(--color-primary);
    opacity: 0.3;
    margin-top: 20px;
}

/* Pricing */
.pricing-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 32px;
    max-width: 1000px;
    margin: 0 auto;
}

.pricing-card {
    background: white;
    border-radius: 24px;
    padding: 40px 32px;
    border: 2px solid rgba(0,0,0,0.05);
    position: relative;
    transition: all 0.3s;
}

.pricing-card-popular {
    border-color: var(--color-primary);
    transform: scale(1.05);
}

.pricing-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--color-primary);
    color: white;
    padding: 6px 20px;
    border-radius: 100px;
    font-size: 13px;
    font-weight: 600;
}

.pricing-name {
    font-size: 24px;
    font-weight: 700;
    text-align: center;
    margin-bottom: 16px;
}

.pricing-price {
    text-align: center;
    margin-bottom: 16px;
}

.pricing-amount {
    font-size: 48px;
    font-weight: 800;
    color: var(--color-primary);
}

.pricing-currency {
    font-size: 24px;
    color: var(--color-text-muted);
}

.pricing-description {
    text-align: center;
    color: var(--color-text-muted);
    font-size: 14px;
    margin-bottom: 24px;
}

.pricing-features {
    list-style: none;
    padding: 0;
    margin: 0 0 32px 0;
}

.pricing-features li {
    padding: 12px 0;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    font-size: 15px;
}

@media (max-width: 992px) {
    .service-hero-title { font-size: 40px; }
    .service-cards { grid-template-columns: repeat(2, 1fr); }
    .pricing-grid { grid-template-columns: 1fr; max-width: 400px; }
    .pricing-card-popular { transform: none; }
    .process-step-arrow { display: none; }
}

@media (max-width: 576px) {
    .service-hero-title { font-size: 32px; }
    .service-cards { grid-template-columns: 1fr; }
}
</style>
@endsection
