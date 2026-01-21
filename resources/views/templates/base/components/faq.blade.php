@php
    $faq = $content['faq'] ?? [];
@endphp

<section class="section" id="faq">
    <div class="container">
        <h2 class="section-title">{{ $faq['title'] ?? 'Часто задаваемые вопросы' }}</h2>
        <p class="section-subtitle">{{ $faq['subtitle'] ?? 'Ответы на популярные вопросы' }}</p>
        
        <div class="faq-container">
            @foreach(($faq['items'] ?? []) as $index => $item)
            <div class="faq-item" data-faq="{{ $index }}">
                <button class="faq-question" onclick="toggleFaq({{ $index }})">
                    <span>{{ $item['question'] }}</span>
                    <svg class="faq-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                <div class="faq-answer">
                    <p>{{ $item['answer'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<style>
.faq-container {
    max-width: 800px;
    margin: 0 auto;
}

.faq-item {
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.faq-question {
    width: 100%;
    padding: 24px 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    background: none;
    border: none;
    cursor: pointer;
    text-align: left;
    font-size: 18px;
    font-weight: 600;
    color: var(--color-text);
    transition: color 0.2s;
}

.faq-question:hover {
    color: var(--color-primary);
}

.faq-icon {
    flex-shrink: 0;
    transition: transform 0.3s;
    color: var(--color-text-muted);
}

.faq-item.active .faq-icon {
    transform: rotate(180deg);
    color: var(--color-primary);
}

.faq-answer {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease, padding 0.3s ease;
}

.faq-item.active .faq-answer {
    max-height: 500px;
    padding-bottom: 24px;
}

.faq-answer p {
    color: var(--color-text-muted);
    font-size: 16px;
    line-height: 1.7;
}
</style>

<script>
function toggleFaq(index) {
    const item = document.querySelector('[data-faq="' + index + '"]');
    const allItems = document.querySelectorAll('.faq-item');
    
    allItems.forEach(function(el) {
        if (el !== item) {
            el.classList.remove('active');
        }
    });
    
    item.classList.toggle('active');
}
</script>
