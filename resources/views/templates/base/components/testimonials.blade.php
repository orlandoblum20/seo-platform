@php
    $testimonials = $content['testimonials'] ?? [];
@endphp

<section class="section section-alt" id="testimonials">
    <div class="container">
        <h2 class="section-title">{{ $testimonials['title'] ?? 'Отзывы клиентов' }}</h2>
        <p class="section-subtitle">{{ $testimonials['subtitle'] ?? 'Что говорят о нас наши клиенты' }}</p>
        
        <div class="testimonials-grid">
            @foreach(($testimonials['items'] ?? []) as $review)
            <div class="testimonial-card">
                <div class="testimonial-rating">
                    @for($i = 0; $i < 5; $i++)
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="{{ $i < ($review['rating'] ?? 5) ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                    @endfor
                </div>
                
                <p class="testimonial-text">"{{ $review['text'] }}"</p>
                
                <div class="testimonial-author">
                    <div class="testimonial-avatar">
                        {{ mb_substr($review['name'] ?? 'A', 0, 1) }}
                    </div>
                    <div class="testimonial-info">
                        <div class="testimonial-name">{{ $review['name'] }}</div>
                        @if(!empty($review['position']))
                        <div class="testimonial-position">{{ $review['position'] }}</div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<style>
.testimonials-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 32px;
}

.testimonial-card {
    background: var(--color-background);
    padding: 32px;
    border-radius: 20px;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.testimonial-rating {
    display: flex;
    gap: 4px;
    color: #fbbf24;
    margin-bottom: 20px;
}

.testimonial-text {
    font-size: 16px;
    line-height: 1.7;
    color: var(--color-text);
    margin-bottom: 24px;
    font-style: italic;
}

.testimonial-author {
    display: flex;
    align-items: center;
    gap: 16px;
}

.testimonial-avatar {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 18px;
}

.testimonial-name {
    font-weight: 600;
    color: var(--color-text);
}

.testimonial-position {
    font-size: 14px;
    color: var(--color-text-muted);
}

@media (max-width: 992px) {
    .testimonials-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 576px) {
    .testimonials-grid {
        grid-template-columns: 1fr;
    }
}
</style>
