@extends('templates.base.layout')

@section('content')
    @include('templates.base.components.header')
    
    <article class="post-page">
        <div class="container">
            <div class="post-header">
                <a href="/blog/" class="back-link">← Вернуться к блогу</a>
                <div class="post-meta">
                    <span class="post-date">{{ $post->published_at?->format('d F Y') }}</span>
                    <span class="post-type">{{ $post->type }}</span>
                </div>
                <h1 class="post-title">{{ $post->title }}</h1>
                @if($post->excerpt)
                <p class="post-excerpt">{{ $post->excerpt }}</p>
                @endif
            </div>
            
            <div class="post-content">
                {!! $post->content !!}
            </div>
            
            <div class="post-footer">
                <a href="/blog/" class="btn btn-secondary">← Все статьи</a>
                <a href="#contacts" class="btn btn-primary">Связаться с нами</a>
            </div>
        </div>
    </article>
    
    @include('templates.base.components.cta')
    @include('templates.base.components.footer')
@endsection

<style>
.post-page {
    padding: 140px 0 80px;
}

.post-page .container {
    max-width: 800px;
}

.back-link {
    display: inline-block;
    color: var(--color-primary);
    text-decoration: none;
    font-weight: 500;
    margin-bottom: 32px;
}

.back-link:hover {
    text-decoration: underline;
}

.post-header {
    margin-bottom: 48px;
}

.post-header .post-meta {
    display: flex;
    gap: 16px;
    margin-bottom: 20px;
    font-size: 14px;
    color: var(--color-text-muted);
}

.post-header .post-type {
    background: var(--color-surface);
    padding: 4px 12px;
    border-radius: 100px;
}

.post-header .post-title {
    font-size: 44px;
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 20px;
}

.post-header .post-excerpt {
    font-size: 20px;
    color: var(--color-text-muted);
    line-height: 1.7;
}

.post-content {
    font-size: 18px;
    line-height: 1.8;
    color: var(--color-text);
}

.post-content h2 {
    font-size: 28px;
    font-weight: 700;
    margin: 48px 0 20px;
}

.post-content h3 {
    font-size: 22px;
    font-weight: 700;
    margin: 40px 0 16px;
}

.post-content p {
    margin-bottom: 24px;
}

.post-content ul, .post-content ol {
    margin-bottom: 24px;
    padding-left: 24px;
}

.post-content li {
    margin-bottom: 12px;
}

.post-content blockquote {
    border-left: 4px solid var(--color-primary);
    padding-left: 24px;
    margin: 32px 0;
    font-style: italic;
    color: var(--color-text-muted);
}

.post-content a {
    color: var(--color-primary);
}

.post-content img {
    max-width: 100%;
    height: auto;
    border-radius: 12px;
    margin: 32px 0;
}

.post-footer {
    margin-top: 60px;
    padding-top: 40px;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
    display: flex;
    justify-content: space-between;
    gap: 16px;
}

@media (max-width: 768px) {
    .post-header .post-title {
        font-size: 32px;
    }
    
    .post-content {
        font-size: 16px;
    }
    
    .post-footer {
        flex-direction: column;
    }
}
</style>
