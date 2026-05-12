<x-app-layout>
    <link rel="stylesheet" href="{{ asset('css/modern-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fontawesome.css') }}">

    @include('components.header-nav', ['title' => 'سجل النوبات'])

    <div class="container py-6">
        <div class="page-title">
            <div>
                <h2>سجل النوبات</h2>
                <p class="page-subtitle">تابع حالة المستخدم وحدد الاتجاهات الرئيسية في النوبات.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('data-entry') }}" class="btn-modern"><i class="fas fa-plus ml-2"></i>إضافة نوبة</a>
                <button class="btn-secondary" onclick="window.print()"><i class="fas fa-print ml-2"></i>طباعة</button>
            </div>
        </div>

        <div class="dashboard-card text-center mb-6">
            @php
                $seizuresRiskScore = is_numeric($riskScore) ? (int) round($riskScore) : 0;
                $seizuresRiskLabel = $seizuresRiskScore >= 70 ? 'عالي' : ($seizuresRiskScore >= 40 ? 'متوسط' : 'منخفض');
            @endphp
            <p class="text-sm text-gray-500 mb-3">مؤشر الخطر الحالي</p>
            <div class="progress-circle mb-4" style="background: conic-gradient(var(--primary) {{ $seizuresRiskScore }}%, #EDF2F7 0deg);">
                <div class="progress-center">
                    <strong>{{ $seizuresRiskScore }}</strong>
                    <small>من 100</small>
                </div>
            </div>
            <p class="font-bold mb-1">{{ $seizuresRiskLabel }}</p>
            <p class="text-sm text-gray-500">الخطر الحالي: {{ $seizuresRiskLabel }}.</p>
        </div>

        <div class="tabs mb-6">
            <button id="tab-records" type="button" class="tab active" onclick="switchSeizureTab('records')">سجل النوبات</button>
            <button id="tab-device-data" type="button" class="tab" onclick="switchSeizureTab('device-data')">بيانات الأجهزة</button>
            <button id="tab-stats" type="button" class="tab" onclick="switchSeizureTab('stats')">إحصائيات</button>
        </div>

        @php
            $totalSeizures = $seizures->count();
            $averageDuration = $seizures->whereNotNull('end_time')->map(fn($s) => $s->end_time->diffInMinutes($s->start_time))->average() ?? 0;
            $activeSeizures = $seizures->whereNull('end_time')->count();
            $predictedSeizures = $seizures->where('is_predicted', true)->count();
        @endphp

        <div id="recordsTab" class="space-y-4">
            @forelse($seizures as $seizure)
                <div class="seizure-card">
                    <div class="seizure-meta">
                        <h3>{{ $seizure->start_time->format('d M Y') }}</h3>
                        <p class="text-sm text-gray-500">{{ $seizure->start_time->format('H:i') }} · {{ $seizure->end_time ? $seizure->end_time->diffInMinutes($seizure->start_time) . ' دقيقة' : 'جارية' }}</p>
                        <p class="mt-2 text-sm">{{ $seizure->notes ?? 'بدون ملاحظات' }}</p>
                    </div>
                    <div class="flex flex-col gap-3 items-end">
                        <span class="risk-pill {{ $seizure->intensity === 'high' ? 'high' : ($seizure->intensity === 'medium' ? 'medium' : 'low') }}">
                            {{ $seizure->intensity === 'high' ? 'شدة عالية' : ($seizure->intensity === 'medium' ? 'شدة متوسطة' : 'شدة منخفضة') }}
                        </span>
                        <a href="#" class="btn-secondary text-xs">عرض التفاصيل</a>
                    </div>
                </div>
            @empty
                <div class="text-center py-8">
                    <i class="fas fa-clipboard-list text-gray-300 text-4xl mb-4"></i>
                    <p class="text-gray-500">لا توجد نوبات مسجلة</p>
                </div>
            @endforelse
        </div>

        <div id="deviceDataTab" class="hidden space-y-4">
            <div class="dashboard-card">
                <h4 class="section-title">بيانات الأجهزة الحية</h4>
                <div class="stats-grid mb-4">
                    <div class="stat-box">
                        <h4>معدل النبض</h4>
                        <p id="liveHeartRate">--</p>
                    </div>
                    <div class="stat-box">
                        <h4>ضغط الدم</h4>
                        <p id="liveBloodPressure">--/--</p>
                    </div>
                    <div class="stat-box">
                        <h4>توتر العضلات</h4>
                        <p id="liveMuscleTension">--</p>
                    </div>
                    <div class="stat-box">
                        <h4>نشاط المخ</h4>
                        <p id="liveBrainActivity">--</p>
                    </div>
                </div>
                <div class="text-center">
                    <button id="refreshDeviceData" class="btn-modern">
                        <i class="fas fa-sync-alt ml-2"></i>تحديث البيانات
                    </button>
                </div>
            </div>
        </div>

        <div id="statsTab" class="hidden space-y-4">
            <div class="stats-grid">
                <div class="stat-box">
                    <h4>مجموع النوبات</h4>
                    <p>{{ $totalSeizures }}</p>
                </div>
                <div class="stat-box">
                    <h4>متوسط المدة</h4>
                    <p>{{ round($averageDuration, 1) }} دقيقة</p>
                </div>
                <div class="stat-box">
                    <h4>النوبات النشطة</h4>
                    <p>{{ $activeSeizures }}</p>
                </div>
            </div>
            <div class="dashboard-card">
                <h4 class="section-title">النوبات المتوقعة</h4>
                <p class="text-sm text-gray-500 mb-3">عدد النوبات المسجلة على أنها متوقعة.</p>
                <span class="text-2xl font-bold text-secondary">{{ $predictedSeizures }}</span>
            </div>
        </div>
    </div>

    <script>
        function switchSeizureTab(tab) {
            const records = document.getElementById('recordsTab');
            const deviceData = document.getElementById('deviceDataTab');
            const stats = document.getElementById('statsTab');
            const recordsBtn = document.getElementById('tab-records');
            const deviceDataBtn = document.getElementById('tab-device-data');
            const statsBtn = document.getElementById('tab-stats');

            records.classList.add('hidden');
            deviceData.classList.add('hidden');
            stats.classList.add('hidden');

            [recordsBtn, deviceDataBtn, statsBtn].forEach(btn => {
                btn.classList.remove('active');
            });

            if (tab === 'records') {
                records.classList.remove('hidden');
                recordsBtn.classList.add('active');
            } else if (tab === 'device-data') {
                deviceData.classList.remove('hidden');
                deviceDataBtn.classList.add('active');
            } else if (tab === 'stats') {
                stats.classList.remove('hidden');
                statsBtn.classList.add('active');
            }
        }

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

        document.addEventListener('DOMContentLoaded', function () {
            switchSeizureTab('records');
            updateLiveData();

            document.getElementById('refreshDeviceData').addEventListener('click', function() {
                this.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i>جاري التحديث...';
                updateLiveData().then(() => {
                    this.innerHTML = '<i class="fas fa-sync-alt ml-2"></i>تحديث البيانات';
                });
            });
        });
    </script>

    @include('components.bottom-nav', ['active' => 'seizures'])
</x-app-layout>
