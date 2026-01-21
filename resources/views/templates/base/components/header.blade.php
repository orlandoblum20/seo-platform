<header class="header">
    <div class="container">
        <nav class="nav">
            <a href="/" class="logo">
                <span class="logo-icon">{{ mb_substr($site->title, 0, 1) }}</span>
                <span class="logo-text">{{ $site->title }}</span>
            </a>
            
            <div class="nav-links" id="navLinks">
                <a href="#about">О нас</a>
                <a href="#services">Услуги</a>
                <a href="#features">Преимущества</a>
                <a href="#faq">FAQ</a>
                <a href="#contacts" class="btn btn-primary btn-sm">Связаться</a>
            </div>
            
            <button class="nav-toggle" id="navToggle" aria-label="Меню">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </nav>
    </div>
</header>

<style>
.header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    padding: 16px 0;
}

.nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.logo {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: var(--color-text);
}

.logo-icon {
    width: 40px;
    height: 40px;
    background: var(--color-primary);
    color: white;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 18px;
}

.logo-text {
    font-weight: 700;
    font-size: 20px;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 32px;
}

.nav-links a {
    text-decoration: none;
    color: var(--color-text-muted);
    font-weight: 500;
    font-size: 15px;
    transition: color 0.2s;
}

.nav-links a:hover {
    color: var(--color-primary);
}

.btn-sm {
    padding: 10px 20px;
    font-size: 14px;
}

.nav-toggle {
    display: none;
    flex-direction: column;
    gap: 5px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 5px;
}

.nav-toggle span {
    width: 24px;
    height: 2px;
    background: var(--color-text);
    transition: 0.3s;
}

@media (max-width: 768px) {
    .nav-links {
        position: fixed;
        top: 73px;
        left: 0;
        right: 0;
        bottom: 0;
        background: white;
        flex-direction: column;
        padding: 40px 20px;
        gap: 24px;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    }
    
    .nav-links.active {
        transform: translateX(0);
    }
    
    .nav-toggle {
        display: flex;
    }
}
</style>

<script>
document.getElementById('navToggle').addEventListener('click', function() {
    document.getElementById('navLinks').classList.toggle('active');
});
</script>
