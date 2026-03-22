<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#f8fafc">
        <link rel="manifest" href="{{ route('pwa.manifest') }}">
        <link rel="apple-touch-icon" href="{{ url('/icons/apple-touch-icon.png') }}">

        <script>
            (function () {
                try {
                    var t = localStorage.getItem('gt-theme') || 'dark';
                    if (t === 'dark') document.documentElement.classList.add('dark');
                    else document.documentElement.classList.remove('dark');
                    document.documentElement.setAttribute(
                        'data-density',
                        localStorage.getItem('gt-density') || 'comfortable',
                    );
                    var tc = document.querySelector('meta[name="theme-color"]');
                    if (tc) {
                        tc.setAttribute(
                            'content',
                            t === 'dark' ? '#020617' : '#f8fafc',
                        );
                    }
                } catch (e) {}
            })();
        </script>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link
            href="https://fonts.bunny.net/css?family=outfit:500,600,700|figtree:400,500,600&display=swap"
            rel="stylesheet"
        />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-gt-canvas text-gt-ink">
        @inertia
    </body>
</html>
