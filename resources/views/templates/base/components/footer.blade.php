<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="/" class="footer-logo">
                    <span class="logo-icon">{{ mb_substr($site->title, 0, 1) }}</span>
                    <span class="logo-text">{{ $site->title }}</span>
                </a>
                <p class="footer-description">{{ $site->seo_description ?? '' }}</p>
            </div>
            
            <div class="footer-links">
                <h4 class="footer-title">Навигация</h4>
                <ul>
                    <li><a href="#about">О нас</a></li>
                    <li><a href="#services">Услуги</a></li>
                    <li><a href="#features">Преимущества</a></li>
                    <li><a href="#faq">FAQ</a></li>
                    <li><a href="#contacts">Контакты</a></li>
                </ul>
            </div>
            
            <div class="footer-links">
                <h4 class="footer-title">Контакты</h4>
                <ul>
                    @if(!empty($content['contacts']['phone']))
                    <li><a href="tel:{{ $content['contacts']['phone'] }}">{{ $content['contacts']['phone'] }}</a></li>
                    @endif
                    @if(!empty($content['contacts']['email']))
                    <li><a href="mailto:{{ $content['contacts']['email'] }}">{{ $content['contacts']['email'] }}</a></li>
                    @endif
                    @if(!empty($content['contacts']['address']))
                    <li>{{ $content['contacts']['address'] }}</li>
                    @endif
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; {{ date('Y') }} {{ $site->title }}. Все права защищены.</p>
            
            <!-- Backlinks -->
            @if(!empty($backlinks['footer']))
            <div class="footer-backlinks">
                {!! $backlinks['footer'] !!}
            </div>
            @endif
        </div>
    </div>
</footer>

<style>
.footer {
    background: var(--color-text);
    color: rgba(255, 255, 255, 0.7);
    padding: 80px 0 40px;
}

.footer-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 60px;
    margin-bottom: 60px;
}

.footer-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    margin-bottom: 20px;
}

.footer-logo .logo-icon {
    width: 44px;
    height: 44px;
    background: var(--color-primary);
    color: white;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 18px;
}

.footer-logo .logo-text {
    font-weight: 700;
    font-size: 20px;
    color: white;
}

.footer-description {
    font-size: 15px;
    line-height: 1.7;
    max-width: 300px;
}

.footer-title {
    color: white;
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 24px;
}

.footer-links ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 12px;
}

.footer-links a {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    font-size: 15px;
    transition: color 0.2s;
}

.footer-links a:hover {
    color: white;
}

.footer-bottom {
    padding-top: 40px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.footer-bottom p {
    font-size: 14px;
    margin: 0;
}

.footer-backlinks {
    font-size: 12px;
}

.footer-backlinks a {
    color: rgba(255, 255, 255, 0.5);
    text-decoration: none;
    margin: 0 8px;
}

.footer-backlinks a:hover {
    color: rgba(255, 255, 255, 0.7);
}

@media (max-width: 992px) {
    .footer-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .footer-brand {
        grid-column: span 2;
    }
}

@media (max-width: 576px) {
    .footer-grid {
        grid-template-columns: 1fr;
    }
    
    .footer-brand {
        grid-column: span 1;
    }
    
    .footer-bottom {
        flex-direction: column;
        text-align: center;
    }
}
</style>
