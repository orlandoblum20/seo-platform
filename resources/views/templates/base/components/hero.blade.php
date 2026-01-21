@php
    $hero = $content['hero'] ?? [];
@endphp

<section class="hero">
    <div class="container">
        <div class="hero-content">
            @if(!empty($hero['badge']))
            <div class="hero-badge">{{ $hero['badge'] }}</div>
            @endif
            
            <h1 class="hero-title">{{ $hero['headline'] ?? $site->title }}</h1>
            
            @if(!empty($hero['subheadline']))
            <p class="hero-subtitle">{{ $hero['subheadline'] }}</p>
            @endif
            
            <div class="hero-actions">
                <a href="#contacts" class="btn btn-primary">
                    {{ $hero['cta_text'] ?? 'Получить консультацию' }}
                </a>
                <a href="#about" class="btn btn-secondary">Узнать больше</a>
            </div>
            
            @if(!empty($hero['stats']))
            <div class="hero-stats">
                @foreach($hero['stats'] as $stat)
                <div class="hero-stat">
                    <div class="hero-stat-value">{{ $stat['value'] }}</div>
                    <div class="hero-stat-label">{{ $stat['label'] }}</div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        
        <div class="hero-visual">
            <div class="hero-shape"></div>
            <div class="hero-shape-2"></div>
        </div>
    </div>
</section>

<style>
.hero {
    min-height: 100vh;
    display: flex;
    align-items: center;
    padding: 120px 0 80px;
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, var(--color-surface) 0%, var(--color-background) 100%);
}

.hero .container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
}

.hero-badge {
    display: inline-block;
    background: rgba(37, 99, 235, 0.1);
    color: var(--color-primary);
    padding: 8px 16px;
    border-radius: 100px;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 24px;
}

.hero-title {
    font-size: 56px;
    font-weight: 800;
    line-height: 1.1;
    margin-bottom: 24px;
    background: linear-gradient(135deg, var(--color-text) 0%, var(--color-primary) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.hero-subtitle {
    font-size: 20px;
    color: var(--color-text-muted);
    line-height: 1.7;
    margin-bottom: 40px;
    max-width: 500px;
}

.hero-actions {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.hero-stats {
    display: flex;
    gap: 48px;
    margin-top: 60px;
    padding-top: 40px;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.hero-stat-value {
    font-size: 36px;
    font-weight: 800;
    color: var(--color-primary);
}

.hero-stat-label {
    font-size: 14px;
    color: var(--color-text-muted);
    margin-top: 4px;
}

.hero-visual {
    position: relative;
    height: 500px;
}

.hero-shape {
    position: absolute;
    width: 400px;
    height: 400px;
    background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-accent) 100%);
    border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
    opacity: 0.8;
    animation: morph 8s ease-in-out infinite;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
}

.hero-shape-2 {
    position: absolute;
    width: 300px;
    height: 300px;
    background: linear-gradient(135deg, var(--color-accent) 0%, var(--color-primary) 100%);
    border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
    opacity: 0.5;
    animation: morph 10s ease-in-out infinite reverse;
    right: 100px;
    top: 60%;
    transform: translateY(-50%);
}

@keyframes morph {
    0%, 100% {
        border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
    }
    50% {
        border-radius: 70% 30% 30% 70% / 70% 70% 30% 30%;
    }
}

@media (max-width: 992px) {
    .hero .container {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .hero-title {
        font-size: 40px;
    }
    
    .hero-subtitle {
        margin: 0 auto 40px;
    }
    
    .hero-actions {
        justify-content: center;
    }
    
    .hero-stats {
        justify-content: center;
    }
    
    .hero-visual {
        display: none;
    }
}
</style>
