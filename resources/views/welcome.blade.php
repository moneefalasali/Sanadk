<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>سندك - SANADK</title>
        <link rel="stylesheet" href="{{ asset('css/welcome.css') }}">
    </head>
    <body class="antialiased" style="background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
        <div class="relative sm:flex sm:justify-center sm:items-center min-h-screen bg-center bg-gray-100 selection:bg-blue-500 selection:text-white">
            @if (Route::has('login'))
                <div class="sm:fixed sm:top-0 sm:left-0 p-6 text-left z-10">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="font-semibold text-gray-600 hover:text-gray-900 focus:outline focus:outline-2 focus:rounded-sm focus:outline-blue-500">لوحة التحكم</a>
                    @else
                        <a href="{{ route('login') }}" class="font-semibold text-gray-600 hover:text-gray-900 focus:outline focus:outline-2 focus:rounded-sm focus:outline-blue-500">تسجيل الدخول</a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="mr-4 font-semibold text-gray-600 hover:text-gray-900 focus:outline focus:outline-2 focus:rounded-sm focus:outline-blue-500">إنشاء حساب</a>
                        @endif
                    @endauth
                </div>
            @endif

            <div class="max-w-7xl mx-auto p-6 lg:p-8 text-center">
                <img src="/img/logo.png" alt="SANADK Logo" class="h-32 mx-auto mb-8">
                <h1 class="text-5xl font-bold text-blue-600 mb-4">سندك - SANADK</h1>
                <p class="text-2xl text-gray-600 mb-8">نظامك الذكي للتنبؤ بنوبات الصرع وحماية حياتك</p>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-16">
                    <div class="bg-white p-8 rounded-2xl shadow-lg border-t-4 border-blue-500">
                        <div class="text-blue-500 mb-4">
                            <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <h3 class="font-bold text-xl mb-2">تنبؤ ذكي</h3>
                        <p class="text-gray-500">تحليل لحظي للبيانات الحيوية للتنبؤ بالنوبة قبل حدوثها باستخدام الذكاء الاصطناعي.</p>
                    </div>
                    <div class="bg-white p-8 rounded-2xl shadow-lg border-t-4 border-red-500">
                        <div class="text-red-500 mb-4">
                            <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                        </div>
                        <h3 class="font-bold text-xl mb-2">استجابة طارئة</h3>
                        <p class="text-gray-500">تنبيه تلقائي للأهل والطبيب والجهات المختصة عند الطوارئ مع تحديد الموقع الجغرافي.</p>
                    </div>
                    <div class="bg-white p-8 rounded-2xl shadow-lg border-t-4 border-green-500">
                        <div class="text-green-500 mb-4">
                            <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        </div>
                        <h3 class="font-bold text-xl mb-2">متابعة طبية</h3>
                        <p class="text-gray-500">لوحة تحكم خاصة للطبيب لمتابعة العلامات الحيوية لحظياً وتعديل خطة العلاج.</p>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
