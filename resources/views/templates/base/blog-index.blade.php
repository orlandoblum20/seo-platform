@extends('templates.base.layout')

@section('content')
    @include('templates.base.components.header')
    
    <main class="blog-page">
        <div class="container">
            <div class="blog-header">
                <h1 class="blog-title">Блог</h1>
                <p class="blog-subtitle">Полезные статьи и новости</p>
            </div>
            
            <div class="posts-grid">
                @forelse($posts as $post)
                <article class="post-card">
                    <div class="post-card-content">
                        <div class="post-meta">
                            <span class="post-date">{{ $post->published_at?->format('d.m.Y') }}</span>
                            <span class="post-type">{{ $post->type }}</span>
                        </div>
                        <h2 class="post-title">
                            <a href="/blog/{{ $post->slug }}.html">{{ $post->title }}</a>
                        </h2>
                        @if($post->excerpt)
                        <p class="post-excerpt">{{ $post->excerpt }}</p>
                        @endif
                        <a href="/blog/{{ $post->slug }}.html" class="post-link">
                            Читать далее →
                        </a>
                    </div>
                </article>
                @empty
                <div class="no-posts">
                    <p>Статьи скоро появятся</p>
                </div>
                @endforelse
            </div>
        </div>
    </main>
    
    @include('templates.base.components.footer')
@endsection

<style>
.blog-page {
    padding: 140px 0 80px;
    min-height: 60vh;
}

.blog-header {
    text-align: center;
    margin-bottom: 60px;
}

.blog-title {
    font-size: 48px;
    font-weight: 800;
    margin-bottom: 16px;
}

.blog-subtitle {
    font-size: 20px;
    color: var(--color-text-muted);
}

.posts-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 32px;
}

.post-card {
    background: var(--color-background);
    border-radius: 20px;
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: transform 0.3s, box-shadow 0.3s;
}

.post-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.post-card-content {
    padding: 32px;
}

.post-meta {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
    font-size: 14px;
    color: var(--color-text-muted);
}

.post-type {
    background: var(--color-surface);
    padding: 4px 12px;
    border-radius: 100px;
}

.post-title {
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 12px;
    line-height: 1.3;
}

.post-title a {
    color: var(--color-text);
    text-decoration: none;
    transition: color 0.2s;
}

.post-title a:hover {
    color: var(--color-primary);
}

.post-excerpt {
    color: var(--color-text-muted);
    font-size: 15px;
    line-height: 1.7;
    margin-bottom: 20px;
}

.post-link {
    color: var(--color-primary);
    font-weight: 600;
    text-decoration: none;
    font-size: 15px;
}

.post-link:hover {
    text-decoration: underline;
}

.no-posts {
    grid-column: span 3;
    text-align: center;
    padding: 60px;
    color: var(--color-text-muted);
}

@media (max-width: 992px) {
    .posts-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .no-posts {
        grid-column: span 2;
    }
}

@media (max-width: 576px) {
    .posts-grid {
        grid-template-columns: 1fr;
    }
    .no-posts {
        grid-column: span 1;
    }
    .blog-title {
        font-size: 36px;
    }
}
</style>
