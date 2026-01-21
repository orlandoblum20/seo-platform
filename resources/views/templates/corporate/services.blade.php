@extends('templates.base.layout')

@section('content')
<!-- Hero Section -->
<section class="py-20" style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);">
    <div class="container mx-auto px-4">
        <nav class="text-white/70 text-sm mb-8">
            <a href="/" class="hover:text-white">Главная</a>
            <span class="mx-2">/</span>
            <span class="text-white">Услуги</span>
        </nav>
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">Наши услуги</h1>
        <p class="text-xl text-white/90 max-w-2xl">{{ $content['services_hero']['subtitle'] ?? 'Комплексные решения для развития вашего бизнеса' }}</p>
    </div>
</section>

<!-- Services List -->
<section class="py-20 bg-white">
    <div class="container mx-auto px-4">
        <div class="grid md:grid-cols-2 gap-8">
            @foreach(($content['services_list']['items'] ?? [
                ['title' => 'Стратегический консалтинг', 'description' => 'Разработка долгосрочной стратегии развития вашего бизнеса с учётом рыночных тенденций и конкурентного окружения.', 'features' => ['Анализ рынка', 'Конкурентный анализ', 'Разработка стратегии', 'Планирование роста']],
                ['title' => 'Цифровая трансформация', 'description' => 'Внедрение современных цифровых технологий для оптимизации бизнес-процессов и повышения эффективности.', 'features' => ['Автоматизация процессов', 'Внедрение CRM', 'Цифровые инструменты', 'Обучение персонала']],
                ['title' => 'Маркетинговые услуги', 'description' => 'Комплексное продвижение вашего бренда и привлечение целевых клиентов через эффективные каналы.', 'features' => ['Digital-маркетинг', 'Контент-стратегия', 'SMM продвижение', 'Аналитика']],
                ['title' => 'Разработка решений', 'description' => 'Создание индивидуальных программных решений под уникальные потребности вашего бизнеса.', 'features' => ['Веб-разработка', 'Мобильные приложения', 'Интеграции', 'Техподдержка']],
                ['title' => 'Аудит и оптимизация', 'description' => 'Глубокий анализ текущего состояния бизнеса и разработка рекомендаций по улучшению.', 'features' => ['Бизнес-аудит', 'Оптимизация затрат', 'Повышение эффективности', 'Отчётность']],
                ['title' => 'Обучение и развитие', 'description' => 'Программы обучения и развития персонала для повышения компетенций вашей команды.', 'features' => ['Корпоративное обучение', 'Тренинги', 'Коучинг', 'Менторство']],
            ]) as $index => $service)
            <div class="bg-white border rounded-xl p-8 hover:shadow-xl transition-shadow" style="border-color: var(--color-surface);">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center text-xl font-bold text-white" style="background-color: var(--color-primary);">
                        {{ $index + 1 }}
                    </div>
                    <div class="flex-grow">
                        <h3 class="text-xl font-bold mb-3" style="color: var(--color-text);">{{ $service['title'] }}</h3>
                        <p class="mb-4" style="color: var(--color-text-muted);">{{ $service['description'] }}</p>
                        <ul class="grid grid-cols-2 gap-2">
                            @foreach($service['features'] ?? [] as $feature)
                            <li class="flex items-center text-sm" style="color: var(--color-text-muted);">
                                <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-primary);">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                {{ $feature }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Process Section -->
<section class="py-20" style="background-color: var(--color-surface);">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-4" style="color: var(--color-text);">
            {{ $content['process']['title'] ?? 'Как мы работаем' }}
        </h2>
        <p class="text-center text-lg mb-16 max-w-2xl mx-auto" style="color: var(--color-text-muted);">
            {{ $content['process']['subtitle'] ?? 'Прозрачный и эффективный процесс сотрудничества' }}
        </p>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach(($content['process']['steps'] ?? [
                ['number' => '01', 'title' => 'Консультация', 'description' => 'Обсуждаем ваши задачи и цели, изучаем специфику бизнеса'],
                ['number' => '02', 'title' => 'Анализ', 'description' => 'Проводим глубокий анализ и разрабатываем план действий'],
                ['number' => '03', 'title' => 'Реализация', 'description' => 'Выполняем работы с соблюдением сроков и стандартов качества'],
                ['number' => '04', 'title' => 'Поддержка', 'description' => 'Обеспечиваем сопровождение и помощь после завершения проекта'],
            ]) as $step)
            <div class="text-center">
                <div class="text-5xl font-bold mb-4" style="color: var(--color-primary); opacity: 0.3;">{{ $step['number'] }}</div>
                <h3 class="text-xl font-bold mb-3" style="color: var(--color-text);">{{ $step['title'] }}</h3>
                <p style="color: var(--color-text-muted);">{{ $step['description'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Pricing Section -->
<section class="py-20 bg-white">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-4" style="color: var(--color-text);">
            {{ $content['pricing']['title'] ?? 'Тарифные планы' }}
        </h2>
        <p class="text-center text-lg mb-16 max-w-2xl mx-auto" style="color: var(--color-text-muted);">
            {{ $content['pricing']['subtitle'] ?? 'Выберите оптимальный вариант сотрудничества' }}
        </p>
        <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
            @foreach(($content['pricing']['plans'] ?? [
                ['name' => 'Базовый', 'price' => 'от 50 000 ₽', 'description' => 'Для небольших проектов', 'features' => ['Консультация', 'Базовый анализ', 'Отчёт', 'Email-поддержка'], 'popular' => false],
                ['name' => 'Стандарт', 'price' => 'от 150 000 ₽', 'description' => 'Оптимальное решение', 'features' => ['Всё из Базового', 'Глубокий анализ', 'Реализация', 'Приоритетная поддержка', 'Месяц сопровождения'], 'popular' => true],
                ['name' => 'Премиум', 'price' => 'от 300 000 ₽', 'description' => 'Максимальный результат', 'features' => ['Всё из Стандарт', 'VIP-менеджер', 'Расширенная аналитика', 'Квартал сопровождения', 'Обучение команды'], 'popular' => false],
            ]) as $plan)
            <div class="relative rounded-2xl p-8 {{ $plan['popular'] ? 'shadow-2xl scale-105' : 'shadow-lg' }}" style="background-color: {{ $plan['popular'] ? 'var(--color-primary)' : 'white' }}; {{ $plan['popular'] ? 'color: white;' : '' }}">
                @if($plan['popular'])
                <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 px-4 py-1 bg-yellow-400 text-yellow-900 text-sm font-bold rounded-full">
                    Популярный
                </div>
                @endif
                <h3 class="text-xl font-bold mb-2">{{ $plan['name'] }}</h3>
                <p class="text-sm mb-4 {{ $plan['popular'] ? 'opacity-80' : '' }}" style="{{ !$plan['popular'] ? 'color: var(--color-text-muted);' : '' }}">{{ $plan['description'] }}</p>
                <div class="text-3xl font-bold mb-6">{{ $plan['price'] }}</div>
                <ul class="space-y-3 mb-8">
                    @foreach($plan['features'] as $feature)
                    <li class="flex items-center">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        {{ $feature }}
                    </li>
                    @endforeach
                </ul>
                <a href="/contacts.html" class="block text-center py-3 px-6 rounded-lg font-semibold transition-all {{ $plan['popular'] ? 'bg-white hover:bg-gray-100' : '' }}" style="{{ $plan['popular'] ? 'color: var(--color-primary);' : 'background-color: var(--color-primary); color: white;' }}">
                    Выбрать план
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- CTA Section -->
@include('templates.base.components.cta')
@endsection
