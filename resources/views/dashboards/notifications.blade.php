<x-app-layout>
    <link rel="stylesheet" href="{{ asset('css/modern-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fontawesome.css') }}">

    @php
    function getNotificationColor($type) {
        return match($type) {
            'emergency' => 'bg-red-500',
            'warning' => 'bg-orange-500',
            'doctor_message' => 'bg-blue-500',
            'medical_note' => 'bg-green-500',
            'info' => 'bg-blue-500',
            default => 'bg-gray-500'
        };
    }

    function getNotificationIcon($type) {
        return match($type) {
            'emergency' => 'ambulance',
            'warning' => 'exclamation-triangle',
            'doctor_message' => 'user-md',
            'medical_note' => 'notes-medical',
            'info' => 'info-circle',
            default => 'bell'
        };
    }
    @endphp

    @include('components.header-nav', ['title' => 'التنبيهات', 'unreadNotifications' => $notifications->where('is_read', false)->count()])

    <div class="p-5">
        <div class="flex items-center gap-4 mb-8">
            <a href="{{ route('dashboard') }}" class="text-gray-600"><i class="fas fa-chevron-right"></i></a>
            <h2 class="text-xl font-bold">التنبيهات</h2>
        </div>

        <!-- High Alert Card -->
        @if($notifications->where('is_read', false)->count() > 0)
            <div class="bg-red-500 text-white p-6 rounded-[32px] shadow-xl shadow-red-200 mb-8 relative overflow-hidden">
                <div class="relative z-10">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold">تحذير عالي</h3>
                    </div>
                    <p class="text-sm opacity-90 mb-4">{{ $notifications->where('is_read', false)->first()->message ?? 'احتمال نوبة مرتفع خلال 30 دقيقة القادمة' }}</p>
                    <p class="text-xs font-bold bg-white/20 inline-block px-3 py-1 rounded-lg mb-4">يرجى اتخاذ احتياطاتك والجلوس في مكان آمن</p>
                    <div class="text-[10px] opacity-70">{{ $notifications->where('is_read', false)->first()->created_at->format('H:i') ?? now()->format('H:i') }}</div>
                </div>
                <i class="fas fa-bell absolute -bottom-4 -left-4 text-8xl opacity-10 rotate-12"></i>
            </div>
        @endif

        <!-- Device Data Section -->
        <div class="bg-white p-6 rounded-[32px] shadow-xl mb-8">
            <h3 class="font-bold mb-6 flex items-center gap-2">
                <i class="fas fa-heartbeat text-primary"></i>
                بيانات الأجهزة الحية
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-primary" id="liveHeartRate">--</div>
                    <div class="text-xs text-gray-500">معدل النبض</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-primary" id="liveBloodPressure">--/--</div>
                    <div class="text-xs text-gray-500">ضغط الدم</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-primary" id="liveMuscleTension">--</div>
                    <div class="text-xs text-gray-500">توتر العضلات</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-primary" id="liveBrainActivity">--</div>
                    <div class="text-xs text-gray-500">نشاط المخ</div>
                </div>
            </div>
            <div class="text-center">
                <button id="refreshDeviceData" class="btn-modern-secondary text-sm">
                    <i class="fas fa-sync-alt"></i> تحديث البيانات
                </button>
            </div>
        </div>

        <h3 class="font-bold mb-6">التنبيهات السابقة</h3>
        <div class="space-y-4">
            @forelse($notifications as $notification)
                <div class="bg-white p-4 rounded-2xl shadow-sm flex items-center gap-4 {{ $notification->is_read ? 'opacity-60' : '' }}">
                    <div class="w-10 h-10 {{ getNotificationColor($notification->type) }} rounded-xl flex items-center justify-center">
                        <i class="fas fa-{{ getNotificationIcon($notification->type) }}"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-bold text-sm">{{ $notification->title }}</h4>
                        <p class="text-[10px] text-gray-400">{{ $notification->message }}</p>
                        @if($notification->type === 'emergency')
                            <span class="inline-block bg-red-100 text-red-600 text-xs px-2 py-1 rounded-full mt-1">طوارئ</span>
                        @elseif($notification->type === 'warning')
                            <span class="inline-block bg-orange-100 text-orange-600 text-xs px-2 py-1 rounded-full mt-1">تحذير</span>
                        @else
                            <span class="inline-block bg-blue-100 text-blue-600 text-xs px-2 py-1 rounded-full mt-1">معلومات</span>
                        @endif
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-gray-400">{{ $notification->created_at->diffForHumans() }}</p>
                        @if(!$notification->is_read)
                            <span class="inline-block w-2 h-2 bg-blue-500 rounded-full"></span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-8">
                    <i class="fas fa-bell-slash text-gray-300 text-4xl mb-4"></i>
                    <p class="text-gray-500">لا توجد تنبيهات</p>
                </div>
            @endforelse
        </div>
    </div>

    <script>
        // Live device data update function
        async function updateLiveData() {
            try {
                const response = await fetch('{{ route('devices.live-data') }}', { cache: 'no-store' });
                const data = await response.json();

                if (data.success) {
                    document.getElementById('liveHeartRate').textContent = data.heart_rate || '--';
                    document.getElementById('liveBloodPressure').textContent = `${data.blood_pressure_systolic || '--'}/${data.blood_pressure_diastolic || '--'}`;
                    document.getElementById('liveMuscleTension').textContent = data.muscle_tension || '--';
                    document.getElementById('liveBrainActivity').textContent = data.brain_activity || '--';
                }
            } catch (error) {
                console.error('Error updating live data:', error);
            }
        }

        // Update data every 30 seconds
        setInterval(updateLiveData, 30000);

        // Initial load
        updateLiveData();

        // Manual refresh button
        document.getElementById('refreshDeviceData').addEventListener('click', function() {
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري التحديث...';
            updateLiveData().then(() => {
                this.innerHTML = '<i class="fas fa-sync-alt"></i> تحديث البيانات';
            });
        });
    </script>

    <!-- Bottom Navigation -->
    @include('components.bottom-nav', ['active' => ''])
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
        <a href="{{ route('settings') }}" class="nav-item">
            <i class="fas fa-cog"></i>
            <span>الإعدادات</span>
        </a>
    </div>
</x-app-layout>
