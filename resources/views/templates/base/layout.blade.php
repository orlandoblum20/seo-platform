<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $seo['description'] ?? '' }}">
    <meta name="keywords" content="{{ $seo['keywords'] ?? '' }}">
    <meta name="robots" content="index, follow">
    
    <title>{{ $seo['title'] ?? $site->title }}</title>
    
    <!-- Open Graph -->
    <meta property="og:title" content="{{ $seo['title'] ?? $site->title }}">
    <meta property="og:description" content="{{ $seo['description'] ?? '' }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://{{ $site->domain->domain }}">
    <meta property="og:site_name" content="{{ $site->title }}">
    <meta property="og:locale" content="ru_RU">
    
    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo['title'] ?? $site->title }}">
    <meta name="twitter:description" content="{{ $seo['description'] ?? '' }}">
    <meta name="twitter:url" content="https://{{ $site->domain->domain }}">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect fill='{{ $colors['primary'] ?? '%232563eb' }}' width='100' height='100' rx='20'/></svg>">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <style>
        :root {
            --color-primary: {{ $colors['primary'] ?? '#2563eb' }};
            --color-primary-dark: {{ $colors['primary_dark'] ?? '#1d4ed8' }};
            --color-secondary: {{ $colors['secondary'] ?? '#64748b' }};
            --color-accent: {{ $colors['accent'] ?? '#f59e0b' }};
            --color-background: {{ $colors['background'] ?? '#ffffff' }};
            --color-surface: {{ $colors['surface'] ?? '#f8fafc' }};
            --color-text: {{ $colors['text'] ?? '#1e293b' }};
            --color-text-muted: {{ $colors['muted'] ?? '#64748b' }};
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--color-text);
            background-color: var(--color-background);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 28px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary {
            background: var(--color-primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--color-primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .btn-secondary {
            background: transparent;
            color: var(--color-primary);
            border: 2px solid var(--color-primary);
        }
        
        .btn-secondary:hover {
            background: var(--color-primary);
            color: white;
        }
        
        .section {
            padding: 80px 0;
        }
        
        .section-alt {
            background: var(--color-surface);
        }
        
        .section-title {
            font-size: 36px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 16px;
        }
        
        .section-subtitle {
            font-size: 18px;
            color: var(--color-text-muted);
            text-align: center;
            max-width: 600px;
            margin: 0 auto 48px;
        }
        
        .grid {
            display: grid;
            gap: 32px;
        }
        
        .grid-2 { grid-template-columns: repeat(2, 1fr); }
        .grid-3 { grid-template-columns: repeat(3, 1fr); }
        .grid-4 { grid-template-columns: repeat(4, 1fr); }
        
        @media (max-width: 768px) {
            .grid-2, .grid-3, .grid-4 {
                grid-template-columns: 1fr;
            }
            .section {
                padding: 60px 0;
            }
            .section-title {
                font-size: 28px;
            }
        }
    </style>
    
    @if(!empty($site->custom_head))
    {!! $site->custom_head !!}
    @endif
    
    <!-- Schema.org -->
    {!! $schema ?? '' !!}
    
    <!-- Analytics -->
    @if(!empty($analytics['yandex_metrika']))
    <script type="text/javascript">
        (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
        (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");
        ym({{ $analytics['yandex_metrika'] }}, "init", { clickmap:true, trackLinks:true, accurateTrackBounce:true, webvisor:true });
    </script>
    <noscript><img src="https://mc.yandex.ru/watch/{{ $analytics['yandex_metrika'] }}" style="position:absolute;left:-9999px;" alt=""/></noscript>
    @endif
    
    @if(!empty($analytics['google_analytics']))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $analytics['google_analytics'] }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $analytics['google_analytics'] }}');
    </script>
    @endif
    
    @if(!empty($analytics['google_tag_manager']))
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $analytics['google_tag_manager'] }}');</script>
    @endif
</head>
<body>
    @if(!empty($analytics['google_tag_manager']))
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $analytics['google_tag_manager'] }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif

    @yield('content')
    
    <!-- Backlinks Footer -->
    @if(!empty($backlinks['footer']))
    <div style="font-size: 12px; color: #94a3b8; text-align: center; padding: 20px;">
        {!! $backlinks['footer'] !!}
    </div>
    @endif
    
    @if(!empty($site->custom_js))
    <script>{!! $site->custom_js !!}</script>
    @endif
    
    @if(!empty($analytics['custom']))
    {!! $analytics['custom'] !!}
    @endif
</body>
</html>
