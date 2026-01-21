@extends('templates.base.layout')

@section('content')
<!-- Hero Section -->
<section class="py-20" style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);">
    <div class="container mx-auto px-4">
        <nav class="text-white/70 text-sm mb-8">
            <a href="/" class="hover:text-white">Главная</a>
            <span class="mx-2">/</span>
            <span class="text-white">Контакты</span>
        </nav>
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">Контакты</h1>
        <p class="text-xl text-white/90 max-w-2xl">{{ $content['contacts_hero']['subtitle'] ?? 'Свяжитесь с нами удобным для вас способом' }}</p>
    </div>
</section>

<!-- Contact Info + Form -->
<section class="py-20 bg-white">
    <div class="container mx-auto px-4">
        <div class="grid lg:grid-cols-2 gap-16">
            <!-- Contact Information -->
            <div>
                <h2 class="text-2xl font-bold mb-8" style="color: var(--color-text);">Наши контакты</h2>
                
                <div class="space-y-6">
                    <!-- Address -->
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center" style="background-color: var(--color-surface);">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-primary);">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-1" style="color: var(--color-text);">Адрес офиса</h3>
                            <p style="color: var(--color-text-muted);">{{ $content['contacts']['address'] ?? 'г. Москва, ул. Примерная, д. 123, офис 456' }}</p>
                        </div>
                    </div>
                    
                    <!-- Phone -->
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center" style="background-color: var(--color-surface);">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-primary);">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-1" style="color: var(--color-text);">Телефон</h3>
                            <p style="color: var(--color-text-muted);">
                                <a href="tel:{{ $content['contacts']['phone'] ?? '+7 (495) 123-45-67' }}" class="hover:underline">
                                    {{ $content['contacts']['phone'] ?? '+7 (495) 123-45-67' }}
                                </a>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Email -->
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center" style="background-color: var(--color-surface);">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-primary);">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-1" style="color: var(--color-text);">Email</h3>
                            <p style="color: var(--color-text-muted);">
                                <a href="mailto:{{ $content['contacts']['email'] ?? 'info@company.ru' }}" class="hover:underline">
                                    {{ $content['contacts']['email'] ?? 'info@company.ru' }}
                                </a>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Working Hours -->
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center" style="background-color: var(--color-surface);">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-primary);">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-1" style="color: var(--color-text);">Время работы</h3>
                            <p style="color: var(--color-text-muted);">{{ $content['contacts']['working_hours'] ?? 'Пн-Пт: 9:00 - 18:00' }}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Social Links -->
                <div class="mt-8 pt-8" style="border-top: 1px solid var(--color-surface);">
                    <h3 class="font-semibold mb-4" style="color: var(--color-text);">Мы в социальных сетях</h3>
                    <div class="flex gap-4">
                        <a href="#" class="w-10 h-10 rounded-lg flex items-center justify-center transition-colors" style="background-color: var(--color-surface); color: var(--color-text-muted);" onmouseover="this.style.backgroundColor='var(--color-primary)'; this.style.color='white';" onmouseout="this.style.backgroundColor='var(--color-surface)'; this.style.color='var(--color-text-muted)';">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/></svg>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-lg flex items-center justify-center transition-colors" style="background-color: var(--color-surface); color: var(--color-text-muted);" onmouseover="this.style.backgroundColor='var(--color-primary)'; this.style.color='white';" onmouseout="this.style.backgroundColor='var(--color-surface)'; this.style.color='var(--color-text-muted)';">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22.46 6c-.85.38-1.78.64-2.75.76 1-.6 1.76-1.55 2.12-2.68-.93.55-1.96.95-3.06 1.17-.88-.94-2.13-1.53-3.51-1.53-2.66 0-4.81 2.16-4.81 4.81 0 .38.04.75.13 1.1-4-.2-7.58-2.11-9.96-5.02-.42.72-.66 1.56-.66 2.46 0 1.68.86 3.16 2.14 4.02-.79-.02-1.53-.24-2.18-.6v.06c0 2.35 1.67 4.31 3.88 4.76-.4.1-.83.16-1.27.16-.31 0-.62-.03-.92-.08.63 1.96 2.45 3.39 4.61 3.43-1.69 1.32-3.83 2.1-6.15 2.1-.4 0-.8-.02-1.19-.07 2.19 1.4 4.78 2.22 7.57 2.22 9.07 0 14.02-7.52 14.02-14.02 0-.21 0-.42-.01-.63.96-.69 1.79-1.56 2.45-2.55-.88.39-1.83.65-2.82.77z"/></svg>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-lg flex items-center justify-center transition-colors" style="background-color: var(--color-surface); color: var(--color-text-muted);" onmouseover="this.style.backgroundColor='var(--color-primary)'; this.style.color='white';" onmouseout="this.style.backgroundColor='var(--color-surface)'; this.style.color='var(--color-text-muted)';">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-lg flex items-center justify-center transition-colors" style="background-color: var(--color-surface); color: var(--color-text-muted);" onmouseover="this.style.backgroundColor='var(--color-primary)'; this.style.color='white';" onmouseout="this.style.backgroundColor='var(--color-surface)'; this.style.color='var(--color-text-muted)';">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.401.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.354-.629-2.758-1.379l-.749 2.848c-.269 1.045-1.004 2.352-1.498 3.146 1.123.345 2.306.535 3.55.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026L12.017 0z"/></svg>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Contact Form -->
            <div class="p-8 rounded-2xl" style="background-color: var(--color-surface);">
                <h2 class="text-2xl font-bold mb-6" style="color: var(--color-text);">Напишите нам</h2>
                <form class="space-y-6">
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium mb-2" style="color: var(--color-text);">Ваше имя</label>
                            <input type="text" class="w-full px-4 py-3 rounded-lg border focus:outline-none focus:ring-2" style="border-color: var(--color-secondary); background: white;" placeholder="Иван Иванов" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2" style="color: var(--color-text);">Телефон</label>
                            <input type="tel" class="w-full px-4 py-3 rounded-lg border focus:outline-none focus:ring-2" style="border-color: var(--color-secondary); background: white;" placeholder="+7 (999) 123-45-67" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2" style="color: var(--color-text);">Email</label>
                        <input type="email" class="w-full px-4 py-3 rounded-lg border focus:outline-none focus:ring-2" style="border-color: var(--color-secondary); background: white;" placeholder="email@example.com" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2" style="color: var(--color-text);">Тема обращения</label>
                        <select class="w-full px-4 py-3 rounded-lg border focus:outline-none focus:ring-2" style="border-color: var(--color-secondary); background: white;">
                            <option>Выберите тему</option>
                            <option>Консультация</option>
                            <option>Коммерческое предложение</option>
                            <option>Партнёрство</option>
                            <option>Другое</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2" style="color: var(--color-text);">Сообщение</label>
                        <textarea rows="4" class="w-full px-4 py-3 rounded-lg border focus:outline-none focus:ring-2" style="border-color: var(--color-secondary); background: white;" placeholder="Опишите ваш вопрос или предложение..."></textarea>
                    </div>
                    <button type="submit" class="w-full py-4 px-6 rounded-lg font-semibold text-white transition-all hover:opacity-90" style="background-color: var(--color-primary);">
                        Отправить сообщение
                    </button>
                    <p class="text-xs text-center" style="color: var(--color-text-muted);">
                        Нажимая кнопку, вы соглашаетесь с политикой конфиденциальности
                    </p>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="py-20" style="background-color: var(--color-surface);">
    <div class="container mx-auto px-4">
        <h2 class="text-2xl font-bold mb-8 text-center" style="color: var(--color-text);">Как нас найти</h2>
        <div class="rounded-2xl overflow-hidden shadow-lg h-96 bg-gray-200">
            <!-- Placeholder for map -->
            <div class="w-full h-full flex items-center justify-center" style="background: linear-gradient(135deg, var(--color-surface) 0%, var(--color-secondary) 100%); opacity: 0.5;">
                <div class="text-center">
                    <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-primary);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <p style="color: var(--color-text-muted);">Здесь будет карта</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Requisites Section -->
<section class="py-20 bg-white">
    <div class="container mx-auto px-4">
        <h2 class="text-2xl font-bold mb-8 text-center" style="color: var(--color-text);">Реквизиты компании</h2>
        <div class="max-w-3xl mx-auto grid md:grid-cols-2 gap-8">
            <div class="p-6 rounded-xl" style="background-color: var(--color-surface);">
                <h3 class="font-semibold mb-4" style="color: var(--color-text);">Юридическая информация</h3>
                <ul class="space-y-2 text-sm" style="color: var(--color-text-muted);">
                    <li><strong>Полное наименование:</strong> {{ $content['requisites']['full_name'] ?? 'ООО "Название Компании"' }}</li>
                    <li><strong>ИНН:</strong> {{ $content['requisites']['inn'] ?? '7700000000' }}</li>
                    <li><strong>КПП:</strong> {{ $content['requisites']['kpp'] ?? '770001001' }}</li>
                    <li><strong>ОГРН:</strong> {{ $content['requisites']['ogrn'] ?? '1234567890123' }}</li>
                </ul>
            </div>
            <div class="p-6 rounded-xl" style="background-color: var(--color-surface);">
                <h3 class="font-semibold mb-4" style="color: var(--color-text);">Банковские реквизиты</h3>
                <ul class="space-y-2 text-sm" style="color: var(--color-text-muted);">
                    <li><strong>Банк:</strong> {{ $content['requisites']['bank'] ?? 'ПАО Сбербанк' }}</li>
                    <li><strong>Р/с:</strong> {{ $content['requisites']['account'] ?? '40702810000000000000' }}</li>
                    <li><strong>К/с:</strong> {{ $content['requisites']['corr_account'] ?? '30101810400000000000' }}</li>
                    <li><strong>БИК:</strong> {{ $content['requisites']['bik'] ?? '044525225' }}</li>
                </ul>
            </div>
        </div>
    </div>
</section>
@endsection
