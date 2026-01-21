@extends('templates.base.layout')

@section('content')
<!-- Hero Section -->
<section class="relative min-h-[70vh] flex items-center" style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);">
    <div class="absolute inset-0 bg-black/10"></div>
    <div class="container mx-auto px-4 py-20 relative z-10">
        <div class="max-w-4xl">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6 leading-tight">
                {{ $content['hero']['headline'] ?? $site->title }}
            </h1>
            <p class="text-xl md:text-2xl text-white/90 mb-8 leading-relaxed max-w-2xl">
                {{ $content['hero']['subheadline'] ?? $site->seo_description }}
            </p>
            <div class="flex flex-wrap gap-4">
                <a href="/services.html" class="inline-flex items-center px-8 py-4 bg-white text-gray-900 font-semibold rounded-lg hover:bg-gray-100 transition-all shadow-lg">
                    Наши услуги
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </a>
                <a href="/contacts.html" class="inline-flex items-center px-8 py-4 border-2 border-white text-white font-semibold rounded-lg hover:bg-white/10 transition-all">
                    Связаться с нами
                </a>
            </div>
        </div>
    </div>
</section>

<!-- About Preview Section -->
<section class="py-20 bg-white">
    <div class="container mx-auto px-4">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <div>
                <span class="text-sm font-semibold uppercase tracking-wider" style="color: var(--color-primary);">О компании</span>
                <h2 class="text-3xl md:text-4xl font-bold mt-2 mb-6" style="color: var(--color-text);">
                    {{ $content['about_preview']['title'] ?? 'Надёжный партнёр для вашего бизнеса' }}
                </h2>
                <p class="text-lg leading-relaxed mb-6" style="color: var(--color-text-muted);">
                    {{ $content['about_preview']['text'] ?? 'Мы помогаем компаниям достигать поставленных целей, предоставляя профессиональные услуги высочайшего качества.' }}
                </p>
                <div class="grid grid-cols-2 gap-6 mb-8">
                    @foreach(($content['about_preview']['stats'] ?? [
                        ['value' => '10+', 'label' => 'Лет опыта'],
                        ['value' => '500+', 'label' => 'Клиентов'],
                        ['value' => '1000+', 'label' => 'Проектов'],
                        ['value' => '50+', 'label' => 'Специалистов']
                    ]) as $stat)
                    <div class="text-center p-4 rounded-lg" style="background-color: var(--color-surface);">
                        <div class="text-3xl font-bold" style="color: var(--color-primary);">{{ $stat['value'] }}</div>
                        <div class="text-sm" style="color: var(--color-text-muted);">{{ $stat['label'] }}</div>
                    </div>
                    @endforeach
                </div>
                <a href="/about.html" class="inline-flex items-center font-semibold" style="color: var(--color-primary);">
                    Узнать больше о нас
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </a>
            </div>
            <div class="relative">
                <div class="aspect-square rounded-2xl overflow-hidden shadow-2xl" style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);">
                    <div class="absolute inset-0 flex items-center justify-center text-white/20">
                        <svg class="w-48 h-48" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Preview Section -->
<section class="py-20" style="background-color: var(--color-surface);">
    <div class="container mx-auto px-4">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="text-sm font-semibold uppercase tracking-wider" style="color: var(--color-primary);">Услуги</span>
            <h2 class="text-3xl md:text-4xl font-bold mt-2 mb-4" style="color: var(--color-text);">
                {{ $content['services_preview']['title'] ?? 'Что мы предлагаем' }}
            </h2>
            <p class="text-lg" style="color: var(--color-text-muted);">
                {{ $content['services_preview']['subtitle'] ?? 'Комплексные решения для развития вашего бизнеса' }}
            </p>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach(($content['services_preview']['items'] ?? [
                ['title' => 'Консалтинг', 'description' => 'Экспертная поддержка и стратегическое планирование'],
                ['title' => 'Разработка', 'description' => 'Создание решений под ваши задачи'],
                ['title' => 'Поддержка', 'description' => 'Техническое сопровождение и обслуживание'],
            ]) as $index => $service)
            <div class="bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-shadow">
                <div class="w-14 h-14 rounded-lg flex items-center justify-center mb-6" style="background-color: var(--color-primary); opacity: 0.1;">
                    <div class="w-14 h-14 rounded-lg flex items-center justify-center absolute" style="color: var(--color-primary);">
                        <span class="text-2xl font-bold">{{ $index + 1 }}</span>
                    </div>
                </div>
                <h3 class="text-xl font-bold mb-3" style="color: var(--color-text);">{{ $service['title'] }}</h3>
                <p style="color: var(--color-text-muted);">{{ $service['description'] }}</p>
            </div>
            @endforeach
        </div>
        
        <div class="text-center mt-12">
            <a href="/services.html" class="inline-flex items-center px-8 py-4 font-semibold rounded-lg transition-all" style="background-color: var(--color-primary); color: white;">
                Все услуги
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                </svg>
            </a>
        </div>
    </div>
</section>

<!-- Features Section -->
@include('templates.base.components.features')

<!-- CTA Section -->
@include('templates.base.components.cta')
@endsection
