@php
    $about = $content['about'] ?? [];
@endphp

<section class="section" id="about">
    <div class="container">
        <div class="about-grid">
            <div class="about-content">
                <span class="about-badge">{{ $about['badge'] ?? 'О компании' }}</span>
                <h2 class="about-title">{{ $about['title'] ?? 'Мы — команда профессионалов' }}</h2>
                <p class="about-text">{{ $about['text'] ?? '' }}</p>
                
                @if(!empty($about['stats']))
                <div class="about-stats">
                    @foreach($about['stats'] as $stat)
                    <div class="about-stat">
                        <div class="about-stat-value">{{ $stat['value'] }}</div>
                        <div class="about-stat-label">{{ $stat['label'] }}</div>
                    </div>
                    @endforeach
                </div>
                @endif
                
                @if(!empty($about['slogan']))
                <blockquote class="about-quote">
                    "{{ $about['slogan'] }}"
                </blockquote>
                @endif
            </div>
            
            <div class="about-visual">
                <div class="about-card about-card-1">
                    <div class="about-card-icon">✓</div>
                    <div class="about-card-text">Гарантия качества</div>
                </div>
                <div class="about-card about-card-2">
                    <div class="about-card-icon">⚡</div>
                    <div class="about-card-text">Быстрый результат</div>
                </div>
                <div class="about-shape"></div>
            </div>
        </div>
    </div>
</section>

<style>
.about-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 80px;
    align-items: center;
}

.about-badge {
    display: inline-block;
    background: rgba(37, 99, 235, 0.1);
    color: var(--color-primary);
    padding: 8px 16px;
    border-radius: 100px;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 20px;
}

.about-title {
    font-size: 40px;
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 24px;
}

.about-text {
    font-size: 17px;
    color: var(--color-text-muted);
    line-height: 1.8;
    margin-bottom: 32px;
}

.about-stats {
    display: flex;
    gap: 40px;
    margin-bottom: 32px;
}

.about-stat-value {
    font-size: 32px;
    font-weight: 800;
    color: var(--color-primary);
}

.about-stat-label {
    font-size: 14px;
    color: var(--color-text-muted);
}

.about-quote {
    font-size: 18px;
    font-style: italic;
    color: var(--color-text);
    border-left: 4px solid var(--color-primary);
    padding-left: 24px;
    margin: 0;
}

.about-visual {
    position: relative;
    height: 400px;
}

.about-shape {
    position: absolute;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, var(--color-surface) 0%, rgba(37, 99, 235, 0.1) 100%);
    border-radius: 24px;
}

.about-card {
    position: absolute;
    background: white;
    padding: 20px 28px;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 16px;
    z-index: 1;
}

.about-card-1 {
    top: 40px;
    left: 20px;
}

.about-card-2 {
    bottom: 60px;
    right: 20px;
}

.about-card-icon {
    width: 48px;
    height: 48px;
    background: var(--color-primary);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

.about-card-text {
    font-weight: 600;
    color: var(--color-text);
}

@media (max-width: 992px) {
    .about-grid {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    .about-visual {
        order: -1;
        height: 300px;
    }
    
    .about-title {
        font-size: 32px;
    }
}
</style>
