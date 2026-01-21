@php
    $features = $content['features'] ?? [];
@endphp

<section class="section section-alt" id="features">
    <div class="container">
        <h2 class="section-title">{{ $features['title'] ?? 'Наши преимущества' }}</h2>
        <p class="section-subtitle">{{ $features['subtitle'] ?? 'Почему выбирают нас' }}</p>
        
        <div class="features-grid">
            @foreach(($features['items'] ?? []) as $feature)
            <div class="feature-card">
                <div class="feature-icon">
                    @if(!empty($feature['icon']))
                    {!! $feature['icon'] !!}
                    @else
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    @endif
                </div>
                <h3 class="feature-title">{{ $feature['title'] }}</h3>
                <p class="feature-description">{{ $feature['description'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<style>
.features-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 32px;
}

.feature-card {
    background: var(--color-background);
    padding: 40px 32px;
    border-radius: 16px;
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.feature-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.feature-icon {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
    color: white;
}

.feature-title {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 12px;
    color: var(--color-text);
}

.feature-description {
    color: var(--color-text-muted);
    font-size: 15px;
    line-height: 1.7;
}

@media (max-width: 992px) {
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 576px) {
    .features-grid {
        grid-template-columns: 1fr;
    }
}
</style>
