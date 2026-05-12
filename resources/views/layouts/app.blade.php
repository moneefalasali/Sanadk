<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'سندك') }}</title>

    <!-- PWA Meta Tags -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#4A90E2">
    <link rel="apple-touch-icon" href="/img/logo.png">

    <!-- Bootstrap 5 RTL Local -->
    <link rel="stylesheet" href="{{ asset('css/bootstrap.rtl.min.css') }}">
    
    <!-- Tailwind CSS Local -->
    <link rel="stylesheet" href="{{ asset('css/tailwind.css') }}">
    
    <!-- Font Awesome Local -->
    <link rel="stylesheet" href="{{ asset('css/fontawesome.css') }}">

    <!-- Custom Style -->
    <link rel="stylesheet" href="{{ asset('css/sanadk-style.css') }}">

    <!-- Chart.js Local -->
    <script src="{{ asset('js/chart.min.js') }}"></script>
</head>
<body>
    <div class="min-vh-100">
        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>

    <!-- Bootstrap JS Local -->
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>

    <!-- PWA Service Worker - registration disabled to prevent caching and ensure real-time behavior -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', async () => {
                try {
                    const registration = await navigator.serviceWorker.getRegistration('/sw.js');
                    if (registration) {
                        await registration.unregister();
                        console.log('SW unregistered from layout: /sw.js');
                    }
                } catch (error) {
                    console.log('SW unregister failed:', error);
                }
            });
        }
    </script>
</body>
</html>
