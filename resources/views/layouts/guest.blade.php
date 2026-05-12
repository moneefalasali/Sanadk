<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'سندك') }}</title>

    <link rel="stylesheet" href="{{ asset('css/bootstrap.rtl.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sanadk-style.css') }}">
    <style>
        body {
            min-height: 100vh;
            background: radial-gradient(circle at top left, rgba(74, 144, 226, 0.16), transparent 34%),
                        radial-gradient(circle at bottom right, rgba(80, 200, 120, 0.16), transparent 30%);
        }
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .auth-card {
            width: 100%;
            max-width: 520px;
            border-radius: 24px;
            border: 1px solid rgba(44, 62, 80, 0.08);
            background: #ffffff;
            box-shadow: 0 30px 70px rgba(44, 62, 80, 0.1);
            overflow: hidden;
        }
        .auth-header {
            background: linear-gradient(135deg, #4A90E2 0%, #50C878 100%);
            color: white;
            padding: 2rem 1.5rem;
            text-align: center;
        }
        .auth-header h1 {
            margin-bottom: 0.5rem;
            font-size: 1.9rem;
            font-weight: 700;
        }
        .auth-header p {
            margin-bottom: 0;
            opacity: 0.92;
        }
        .auth-body {
            padding: 2rem 1.75rem 2.5rem;
        }
        .form-control:focus {
            border-color: #4A90E2;
            box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.18);
        }
        .auth-footer {
            margin-top: 1.5rem;
            text-align: center;
        }
        .auth-footer a {
            color: #4A90E2;
            text-decoration: none;
            font-weight: 600;
        }
        .auth-footer a:hover {
            text-decoration: underline;
        }
        .auth-brand img {
            max-width: 72px;
            margin-bottom: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-brand">
                    <img src="{{ asset('img/logo.png') }}" alt="logo" onerror="this.style.display='none'">
                    <h1>{{ config('app.name', 'سندك') }}</h1>
                </div>
                <p>مرحباً بك في لوحة التحكم الخاصة بسندك</p>
            </div>
            <div class="auth-body">
                {{ $slot }}
            </div>
        </div>
    </div>

    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
