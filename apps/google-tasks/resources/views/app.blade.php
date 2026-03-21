<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0f172a">
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
                } catch (e) {}
            })();
        </script>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-900 dark:bg-slate-950 dark:text-slate-100">
        @inertia
    </body>
</html>
