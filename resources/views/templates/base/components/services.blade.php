@php
    $services = $content['services'] ?? [];
@endphp

<section class="section section-alt" id="services">
    <div class="container">
        <h2 class="section-title">{{ $services['title'] ?? 'Наши услуги' }}</h2>
        <p class="section-subtitle">{{ $services['subtitle'] ?? 'Полный спектр услуг для вашего бизнеса' }}</p>
        
        <div class="services-grid">
            @foreach(($services['items'] ?? []) as $index => $service)
            <div class="service-card">
                <div class="service-number">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</div>
                <h3 class="service-title">{{ $service['title'] }}</h3>
                <p class="service-description">{{ $service['description'] }}</p>
                
                @if(!empty($service['features']))
                <ul class="service-features">
                    @foreach($service['features'] as $feature)
                    <li>{{ $feature }}</li>
                    @endforeach
                </ul>
                @endif
                
                @if(!empty($service['price']))
                <div class="service-price">от {{ $service['price'] }} ₽</div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</section>

<style>
.services-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 32px;
}

.service-card {
    background: var(--color-background);
    padding: 40px 32px;
    border-radius: 20px;
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.service-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, var(--color-primary), var(--color-accent));
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.3s ease;
}

.service-card:hover::before {
    transform: scaleX(1);
}

.service-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.service-number {
    font-size: 48px;
    font-weight: 800;
    color: var(--color-surface);
    position: absolute;
    top: 20px;
    right: 24px;
    line-height: 1;
}

.service-title {
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 16px;
    color: var(--color-text);
}

.service-description {
    color: var(--color-text-muted);
    font-size: 15px;
    line-height: 1.7;
    margin-bottom: 20px;
}

.service-features {
    list-style: none;
    padding: 0;
    margin: 0 0 20px 0;
}

.service-features li {
    padding: 8px 0;
    padding-left: 24px;
    position: relative;
    font-size: 14px;
    color: var(--color-text-muted);
}

.service-features li::before {
    content: '✓';
    position: absolute;
    left: 0;
    color: var(--color-primary);
    font-weight: 600;
}

.service-price {
    font-size: 20px;
    font-weight: 700;
    color: var(--color-primary);
    margin-top: auto;
}

@media (max-width: 992px) {
    .services-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 576px) {
    .services-grid {
        grid-template-columns: 1fr;
    }
}
</style>
