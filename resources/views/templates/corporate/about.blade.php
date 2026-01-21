@extends('templates.base.layout')

@section('content')
<!-- Hero Section -->
<section class="py-20" style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);">
    <div class="container mx-auto px-4">
        <nav class="text-white/70 text-sm mb-8">
            <a href="/" class="hover:text-white">Главная</a>
            <span class="mx-2">/</span>
            <span class="text-white">О компании</span>
        </nav>
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">О компании</h1>
        <p class="text-xl text-white/90 max-w-2xl">{{ $content['about_hero']['subtitle'] ?? 'Узнайте больше о нашей истории, ценностях и команде' }}</p>
    </div>
</section>

<!-- Main About Section -->
<section class="py-20 bg-white">
    <div class="container mx-auto px-4">
        <div class="grid lg:grid-cols-2 gap-16 items-start">
            <div>
                <h2 class="text-3xl font-bold mb-6" style="color: var(--color-text);">
                    {{ $content['about']['title'] ?? 'Наша история' }}
                </h2>
                <div class="prose prose-lg" style="color: var(--color-text-muted);">
                    {!! nl2br(e($content['about']['text'] ?? 'Мы начали свой путь с небольшой команды энтузиастов, объединённых общей целью — создавать качественные решения для бизнеса. За годы работы мы выросли в надёжную компанию с сильной экспертизой и преданной командой профессионалов.')) !!}
                </div>
            </div>
            <div class="space-y-6">
                <div class="p-6 rounded-xl" style="background-color: var(--color-surface);">
                    <h3 class="text-xl font-bold mb-3" style="color: var(--color-text);">Наша миссия</h3>
                    <p style="color: var(--color-text-muted);">{{ $content['about']['mission'] ?? 'Помогать компаниям достигать амбициозных целей через инновационные решения и профессиональный подход.' }}</p>
                </div>
                <div class="p-6 rounded-xl" style="background-color: var(--color-surface);">
                    <h3 class="text-xl font-bold mb-3" style="color: var(--color-text);">Наше видение</h3>
                    <p style="color: var(--color-text-muted);">{{ $content['about']['vision'] ?? 'Стать лидером отрасли, задавая стандарты качества и клиентского сервиса.' }}</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- History Timeline -->
<section class="py-20" style="background-color: var(--color-surface);">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-16" style="color: var(--color-text);">
            {{ $content['history']['title'] ?? 'Ключевые вехи' }}
        </h2>
        <div class="max-w-4xl mx-auto">
            @foreach(($content['history']['milestones'] ?? [
                ['year' => '2015', 'title' => 'Основание компании', 'description' => 'Начало пути с командой из 5 человек'],
                ['year' => '2017', 'title' => 'Первый крупный клиент', 'description' => 'Заключение контракта с ведущей компанией отрасли'],
                ['year' => '2019', 'title' => 'Расширение команды', 'description' => 'Штат вырос до 30 специалистов'],
                ['year' => '2021', 'title' => 'Новый офис', 'description' => 'Переезд в современный бизнес-центр'],
                ['year' => '2023', 'title' => 'Международное признание', 'description' => 'Выход на международный рынок'],
            ]) as $index => $milestone)
            <div class="flex gap-8 mb-8 last:mb-0">
                <div class="flex-shrink-0 w-24 text-right">
                    <span class="text-2xl font-bold" style="color: var(--color-primary);">{{ $milestone['year'] }}</span>
                </div>
                <div class="flex-shrink-0 relative">
                    <div class="w-4 h-4 rounded-full" style="background-color: var(--color-primary);"></div>
                    @if(!$loop->last)
                    <div class="absolute top-4 left-1/2 transform -translate-x-1/2 w-0.5 h-full" style="background-color: var(--color-primary); opacity: 0.3;"></div>
                    @endif
                </div>
                <div class="flex-grow pb-8">
                    <h3 class="text-xl font-bold mb-2" style="color: var(--color-text);">{{ $milestone['title'] }}</h3>
                    <p style="color: var(--color-text-muted);">{{ $milestone['description'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="py-20 bg-white">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-4" style="color: var(--color-text);">
            {{ $content['team']['title'] ?? 'Наша команда' }}
        </h2>
        <p class="text-center text-lg mb-16 max-w-2xl mx-auto" style="color: var(--color-text-muted);">
            {{ $content['team']['subtitle'] ?? 'Профессионалы, которые делают нашу компанию успешной' }}
        </p>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach(($content['team']['members'] ?? [
                ['name' => 'Иван Петров', 'position' => 'Генеральный директор', 'description' => 'Более 15 лет опыта в управлении'],
                ['name' => 'Елена Сидорова', 'position' => 'Коммерческий директор', 'description' => 'Эксперт по развитию бизнеса'],
                ['name' => 'Алексей Козлов', 'position' => 'Технический директор', 'description' => 'Специалист по инновациям'],
                ['name' => 'Мария Новикова', 'position' => 'HR-директор', 'description' => 'Создаёт сильные команды'],
            ]) as $member)
            <div class="text-center">
                <div class="w-32 h-32 rounded-full mx-auto mb-4 flex items-center justify-center text-3xl font-bold text-white" style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);">
                    {{ mb_substr($member['name'], 0, 1) }}
                </div>
                <h3 class="text-xl font-bold" style="color: var(--color-text);">{{ $member['name'] }}</h3>
                <p class="font-medium mb-2" style="color: var(--color-primary);">{{ $member['position'] }}</p>
                <p class="text-sm" style="color: var(--color-text-muted);">{{ $member['description'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Values Section -->
<section class="py-20" style="background-color: var(--color-surface);">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-16" style="color: var(--color-text);">
            {{ $content['values']['title'] ?? 'Наши ценности' }}
        </h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach(($content['values']['items'] ?? [
                ['title' => 'Качество', 'description' => 'Мы не идём на компромиссы в качестве наших решений'],
                ['title' => 'Честность', 'description' => 'Прозрачность и открытость в отношениях с клиентами'],
                ['title' => 'Инновации', 'description' => 'Постоянное развитие и внедрение новых технологий'],
                ['title' => 'Команда', 'description' => 'Наши сотрудники — наша главная ценность'],
            ]) as $value)
            <div class="bg-white p-8 rounded-xl shadow-lg text-center">
                <div class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center" style="background-color: var(--color-primary); opacity: 0.1;">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center absolute">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-primary);">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="text-xl font-bold mb-3" style="color: var(--color-text);">{{ $value['title'] }}</h3>
                <p style="color: var(--color-text-muted);">{{ $value['description'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- CTA Section -->
@include('templates.base.components.cta')
@endsection
