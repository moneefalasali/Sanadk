<x-app-layout>
    <link rel="stylesheet" href="{{ asset('css/modern-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fontawesome.css') }}">

    @include('components.header-nav', ['title' => 'الإعدادات'])

    <div class="p-5">
        <div class="flex items-center gap-4 mb-8">
            <a href="{{ route('dashboard') }}" class="text-gray-600"><i class="fas fa-chevron-right"></i></a>
            <h2 class="text-xl font-bold">الإعدادات</h2>
        </div>

        <!-- Profile Card -->
        @php
            $settingsNameParts = preg_split('/\s+/', trim(Auth::user()->name));
            $settingsInitials = '';
            foreach ($settingsNameParts as $part) {
                $settingsInitials .= mb_substr($part, 0, 1);
            }
            $settingsInitials = mb_strtoupper(mb_substr($settingsInitials, 0, 2));
        @endphp
        <div class="bg-white p-6 rounded-[32px] shadow-sm flex items-center gap-4 mb-8">
            <div class="w-16 h-16 rounded-2xl bg-blue-600 text-white flex items-center justify-center text-xl font-bold">
                {{ $settingsInitials }}
            </div>
            <div class="flex-1">
                <h3 class="font-bold text-lg">{{ Auth::user()->name }}</h3>
                <p class="text-xs text-gray-400">{{ Auth::user()->email }}</p>
                <button class="text-primary text-[10px] font-bold mt-1">تعديل الملف الشخصي</button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
            <div class="bg-white p-5 rounded-3xl shadow-sm">
                <p class="text-sm text-gray-500 mb-2">جهات الاتصال للطوارئ</p>
                <p class="text-3xl font-bold text-blue-600">{{ $contacts->count() }}</p>
                <p class="text-xs text-gray-400">يمكنك تعديل أو حذف جهات الاتصال من لوحة العائلة.</p>
            </div>
            <div class="bg-white p-5 rounded-3xl shadow-sm">
                <p class="text-sm text-gray-500 mb-2">الأجهزة المربوطة</p>
                <p class="text-3xl font-bold text-emerald-600">{{ $devices->count() }}</p>
                <p class="text-xs text-gray-400">راجع حالة الأجهزة أو أضف جهازًا جديدًا من صفحة الأجهزة.</p>
            </div>
        </div>

        <!-- Settings List -->
        <div class="space-y-2 mb-8">
            <a href="{{ route('profile.edit') }}" class="bg-white p-5 rounded-2xl flex items-center justify-between group">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400 group-hover:text-primary transition">
                        <i class="fas fa-file-medical"></i>
                    </div>
                    <span class="text-sm font-bold">الملف الطبي</span>
                </div>
                <i class="fas fa-chevron-left text-gray-300 text-xs"></i>
            </a>

            <a href="{{ route('data-entry') }}" class="bg-white p-5 rounded-2xl flex items-center justify-between group">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400 group-hover:text-primary transition">
                        <i class="fas fa-pills"></i>
                    </div>
                    <span class="text-sm font-bold">الأدوية</span>
                </div>
                <i class="fas fa-chevron-left text-gray-300 text-xs"></i>
            </a>

            <a href="{{ route('settings') }}#emergency-contacts" class="bg-white p-5 rounded-2xl flex items-center justify-between group">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400 group-hover:text-primary transition">
                        <i class="fas fa-users"></i>
                    </div>
                    <span class="text-sm font-bold">جهات الاتصال للطوارئ</span>
                </div>
                <i class="fas fa-chevron-left text-gray-300 text-xs"></i>
            </a>

            <a href="{{ route('notifications') }}" class="bg-white p-5 rounded-2xl flex items-center justify-between group">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400 group-hover:text-primary transition">
                        <i class="fas fa-bell"></i>
                    </div>
                    <span class="text-sm font-bold">الإشعارات</span>
                </div>
                <i class="fas fa-chevron-left text-gray-300 text-xs"></i>
            </a>

            <a href="{{ route('settings') }}#privacy" class="bg-white p-5 rounded-2xl flex items-center justify-between group">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400 group-hover:text-primary transition">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <span class="text-sm font-bold">الخصوصية والأمان</span>
                </div>
                <i class="fas fa-chevron-left text-gray-300 text-xs"></i>
            </a>

            <a href="{{ route('settings') }}#about" class="bg-white p-5 rounded-2xl flex items-center justify-between group">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400 group-hover:text-primary transition">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <span class="text-sm font-bold">حول التطبيق</span>
                </div>
                <i class="fas fa-chevron-left text-gray-300 text-xs"></i>
            </a>
        </div>

        <div id="emergency-contacts" class="mb-8">
            <h3 class="text-lg font-bold mb-4">جهات الاتصال للطوارئ</h3>
            @forelse($contacts as $contact)
                <div class="bg-white p-5 rounded-2xl shadow-sm flex items-center justify-between mb-3">
                    <div>
                        <p class="font-bold">{{ $contact->name }}</p>
                        <p class="text-xs text-gray-400">{{ $contact->phone }}</p>
                    </div>
                    <span class="text-xs text-primary font-bold">نشط</span>
                </div>
            @empty
                <div class="bg-white p-5 rounded-2xl shadow-sm text-gray-500">
                    لا توجد جهات اتصال للطوارئ مسجلة.
                </div>
            @endforelse
        </div>

        <div id="privacy" class="mb-8">
            <h3 class="text-lg font-bold mb-4">الخصوصية والأمان</h3>
            <div class="bg-white p-5 rounded-2xl shadow-sm space-y-3">
                <p class="text-sm text-gray-500">تحكم في إعدادات الخصوصية والأمان لحماية بياناتك الشخصية وإدارة وصول التطبيق.</p>
                <a href="{{ route('profile.edit') }}" class="text-primary font-bold">تحديث إعدادات الملف الشخصي والإشعارات</a>
            </div>
        </div>

        <div id="about" class="mb-8">
            <h3 class="text-lg font-bold mb-4">حول التطبيق</h3>
            <div class="bg-white p-5 rounded-2xl shadow-sm">
                <p class="text-sm text-gray-500">تطبيق سندك يساعدك على متابعة حالتك الصحية اليومية وسجل النوبات والتقارير بصورة منظمة وسهلة.</p>
                <p class="mt-3 text-xs text-gray-400">الإصدار 1.0.0</p>
            </div>
        </div>

        <!-- Logout Button -->
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full py-4 border-2 border-gray-100 rounded-2xl text-gray-400 font-bold hover:bg-red-50 hover:text-red-500 hover:border-red-100 transition">
                تسجيل الخروج
            </button>
        </form>
    </div>

    <!-- Bottom Navigation -->
    @include('components.bottom-nav', ['active' => 'settings'])
            <i class="fas fa-home"></i>
            <span>الرئيسية</span>
        </a>
        <a href="{{ route('reports') }}" class="nav-item">
            <i class="fas fa-chart-bar"></i>
            <span>التقارير</span>
        </a>
        <a href="{{ route('data-entry') }}" class="nav-item">
            <i class="fas fa-edit"></i>
            <span>إدخال البيانات</span>
        </a>
        <a href="{{ route('seizures') }}" class="nav-item">
            <i class="fas fa-clipboard-list"></i>
            <span>السجل</span>
        </a>
        <a href="{{ route('settings') }}" class="nav-item active">
            <i class="fas fa-cog"></i>
            <span>الإعدادات</span>
        </a>
    </div>
</x-app-layout>
