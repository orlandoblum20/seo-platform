@php
    $contacts = $content['contacts'] ?? [];
@endphp

<section class="section" id="contacts">
    <div class="container">
        <h2 class="section-title">{{ $contacts['title'] ?? 'Свяжитесь с нами' }}</h2>
        <p class="section-subtitle">{{ $contacts['subtitle'] ?? 'Оставьте заявку и мы перезвоним вам' }}</p>
        
        <div class="contacts-grid">
            <div class="contacts-info">
                <div class="contact-item">
                    <div class="contact-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                    </div>
                    <div class="contact-text">
                        <div class="contact-label">Телефон</div>
                        <a href="tel:{{ $contacts['phone'] ?? '+7 (999) 999-99-99' }}" class="contact-value">{{ $contacts['phone'] ?? '+7 (999) 999-99-99' }}</a>
                    </div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                    </div>
                    <div class="contact-text">
                        <div class="contact-label">Email</div>
                        <a href="mailto:{{ $contacts['email'] ?? 'info@example.com' }}" class="contact-value">{{ $contacts['email'] ?? 'info@example.com' }}</a>
                    </div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                    </div>
                    <div class="contact-text">
                        <div class="contact-label">Адрес</div>
                        <div class="contact-value">{{ $contacts['address'] ?? 'г. Москва' }}</div>
                    </div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <div class="contact-text">
                        <div class="contact-label">Режим работы</div>
                        <div class="contact-value">{{ $contacts['hours'] ?? 'Пн-Пт: 9:00 - 18:00' }}</div>
                    </div>
                </div>
            </div>
            
            <div class="contacts-form-wrapper">
                <form class="contacts-form" id="contactForm">
                    <div class="form-group">
                        <input type="text" name="name" placeholder="Ваше имя" required class="form-input">
                    </div>
                    <div class="form-group">
                        <input type="tel" name="phone" placeholder="Телефон" required class="form-input">
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Email" class="form-input">
                    </div>
                    <div class="form-group">
                        <textarea name="message" placeholder="Сообщение" rows="4" class="form-input"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Отправить заявку</button>
                    <p class="form-privacy">Нажимая кнопку, вы соглашаетесь с политикой конфиденциальности</p>
                </form>
            </div>
        </div>
    </div>
</section>

<style>
.contacts-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: start;
}

.contacts-info {
    display: flex;
    flex-direction: column;
    gap: 32px;
}

.contact-item {
    display: flex;
    align-items: flex-start;
    gap: 20px;
}

.contact-icon {
    width: 56px;
    height: 56px;
    background: var(--color-surface);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-primary);
    flex-shrink: 0;
}

.contact-label {
    font-size: 14px;
    color: var(--color-text-muted);
    margin-bottom: 4px;
}

.contact-value {
    font-size: 18px;
    font-weight: 600;
    color: var(--color-text);
    text-decoration: none;
}

.contact-value:hover {
    color: var(--color-primary);
}

.contacts-form-wrapper {
    background: var(--color-surface);
    padding: 40px;
    border-radius: 24px;
}

.form-group {
    margin-bottom: 20px;
}

.form-input {
    width: 100%;
    padding: 16px 20px;
    font-size: 16px;
    border: 2px solid transparent;
    border-radius: 12px;
    background: var(--color-background);
    color: var(--color-text);
    transition: border-color 0.2s, box-shadow 0.2s;
    font-family: inherit;
}

.form-input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
}

.form-input::placeholder {
    color: var(--color-text-muted);
}

textarea.form-input {
    resize: vertical;
    min-height: 120px;
}

.btn-block {
    width: 100%;
    padding: 16px;
    font-size: 17px;
}

.form-privacy {
    font-size: 13px;
    color: var(--color-text-muted);
    text-align: center;
    margin-top: 16px;
}

@media (max-width: 992px) {
    .contacts-grid {
        grid-template-columns: 1fr;
    }
}
</style>
