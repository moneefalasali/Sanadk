<x-app-layout>
    @include('components.header-nav', ['title' => 'لوحة المريض', 'unreadNotifications' => $unreadNotifications ?? 0])

    <div class="container p-4">
        @if(session('success'))
            <div class="alert alert-success rounded-4 shadow-sm p-3 mb-4">
                {{ session('success') }}
            </div>
        @endif

        <!-- Risk Score Card -->
        <div class="card-modern text-center">
            @php
                $riskScoreValue = is_numeric($riskScore) ? (int) round($riskScore) : 0;
                $riskLabel = $riskScoreValue >= 70 ? 'عالي' : ($riskScoreValue >= 40 ? 'متوسط' : 'منخفض');
            @endphp
            <h3 class="text-muted small mb-4">درجة الخطر اليوم <i class="fas fa-info-circle"></i></h3>
            <div id="riskProgress" class="circular-progress mb-4" style="background: conic-gradient(var(--primary) {{ $riskScoreValue }}%, #EDF2F7 0deg);">
                <div id="riskScoreValue" class="progress-value">{{ $riskScoreValue }}</div>
            </div>
            <p id="riskScoreLabel" class="h5 fw-bold text-dark">{{ $riskLabel }}</p>
            <p id="riskPredictionText" class="small text-muted mt-2 d-none">احتمال حدوث نوبة خلال 30 دقيقة القادمة {{ $riskLabel }}. يرجى أخذ احتياطاتك.</p>
            <div class="d-flex justify-content-center gap-2 flex-wrap mt-3">
                <form action="{{ route('devices.simulate') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">تشغيل المحاكاة</button>
                </form>
                <button type="button" id="stopSimulationBtn" class="btn btn-outline-secondary btn-sm">إيقاف المحاكاة</button>
            </div>
        </div>

        <div class="card-modern mb-4">
            <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h3 class="h6 fw-bold mb-1">حالة الوقت الحقيقي</h3>
                    <p class="small text-muted mb-0">البث المباشر عبر WebSocket</p>
                </div>
                <span id="connectionBadge" class="badge rounded-pill bg-secondary p-2">جاري الاتصال...</span>
            </div>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <div class="bg-white rounded-4 shadow-sm p-3">
                        <p class="small text-muted mb-1">حالة EEG</p>
                        <p id="eegStatus" class="fw-bold mb-0">غير معروف</p>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="bg-white rounded-4 shadow-sm p-3">
                        <p class="small text-muted mb-1">آخر تحديث</p>
                        <p id="lastReceivedAt" class="fw-bold mb-0">--</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="mb-4">
            <h3 class="h6 fw-bold mb-3">ملخص الحالة السريعة</h3>
            <div class="row g-3">
                <div class="col-6 col-md-4">
                    <div class="bg-white p-3 rounded-4 shadow-sm d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-primary bg-opacity-10 p-2 text-primary">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                        <div>
                            <p class="small text-muted mb-0" style="font-size: 10px;">معدل النبض</p>
                            <p class="small fw-bold mb-0" id="liveHeartRate">--</p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="bg-white p-3 rounded-4 shadow-sm d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-danger bg-opacity-10 p-2 text-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div>
                            <p class="small text-muted mb-0" style="font-size: 10px;">التوتر</p>
                            <p class="small fw-bold mb-0" id="liveStressLevel">--</p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="bg-white p-3 rounded-4 shadow-sm d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-info bg-opacity-10 p-2 text-info">
                            <i class="fas fa-running"></i>
                        </div>
                        <div>
                            <p class="small text-muted mb-0" style="font-size: 10px;">النشاط</p>
                            <p class="small fw-bold mb-0" id="liveActivityLevel">--</p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="bg-white p-3 rounded-4 shadow-sm d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-danger bg-opacity-10 p-2 text-danger">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                        <div>
                            <p class="small text-muted mb-0" style="font-size: 10px;">آخر نوبة</p>
                            <p class="small fw-bold mb-0">{{ $lastSeizure ? $lastSeizure->start_time->diffForHumans() : 'لا يوجد' }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="bg-white p-3 rounded-4 shadow-sm d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-secondary bg-opacity-10 p-2 text-secondary">
                            <i class="fas fa-microchip"></i>
                        </div>
                        <div>
                            <p class="small text-muted mb-0" style="font-size: 10px;">أجهزة متصلة</p>
                            <p class="small fw-bold mb-0">{{ $connectedDevices }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="bg-white p-3 rounded-4 shadow-sm d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-success bg-opacity-10 p-2 text-success">
                            <i class="fas fa-signal"></i>
                        </div>
                        <div>
                            <p class="small text-muted mb-0" style="font-size: 10px;">أنواع الأجهزة</p>
                            <p class="small fw-bold mb-0">{{ $deviceTypes }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="bg-white p-3 rounded-4 shadow-sm d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-warning bg-opacity-10 p-2 text-warning">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <p class="small text-muted mb-0" style="font-size: 10px;">أفراد العائلة</p>
                            <p class="small fw-bold mb-0">{{ $familyContacts }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="bg-white p-3 rounded-4 shadow-sm d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-secondary bg-opacity-10 p-2 text-secondary">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div>
                            <p class="small text-muted mb-0" style="font-size: 10px;">الأطباء المرتبطين</p>
                            <p class="small fw-bold mb-0">{{ $linkedDoctors }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Analysis Section -->
        <div class="card-modern mb-4">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h3 class="h6 fw-bold mb-0">التحليل الذكي الحي</h3>
                <button id="analyzeBtn" class="btn btn-primary btn-sm">
                    <i class="fas fa-brain"></i> تحليل الآن
                </button>
            </div>

            <div id="analysisResults" class="d-none">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="bg-light rounded-3 p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-3 bg-primary bg-opacity-10 p-2 text-primary">
                                    <i class="fas fa-running"></i>
                                </div>
                                <div>
                                    <p class="small text-muted mb-0">النشاط الحالي</p>
                                    <p class="fw-bold mb-0" id="currentActivity">جاري التحليل...</p>
                                    <p class="small text-muted mb-0" id="riskLevel">جاري التحليل...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-light rounded-3 p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-3 bg-warning bg-opacity-10 p-2 text-warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div>
                                    <p class="small text-muted mb-0">الوقت المتوقع</p>
                                    <p class="fw-bold mb-0" id="timeToEvent">جاري التحليل...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="recommendations" class="d-none">
                    <h5 class="fw-bold mb-3">التوصيات الطبية</h5>
                    <div id="recommendationsList" class="list-group">
                        <!-- Recommendations will be loaded here -->
                    </div>
                </div>

                <div id="emergencyInfo" class="d-none mt-4">
                    <div class="alert alert-danger">
                        <h5 class="alert-heading"><i class="fas fa-ambulance"></i> تنبيه طوارئ!</h5>
                        <p class="mb-2">تم اكتشاف خطر عالي. تم إرسال إشعارات طوارئ لعائلتك وأطبائك.</p>
                        <div id="nearestHospitals">
                            <!-- Nearest hospitals will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>

            <div id="analysisLoading" class="text-center py-4 d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">جاري التحليل...</span>
                </div>
                <p class="mt-2 text-muted">جاري تحليل البيانات من الأجهزة...</p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4">
                <a href="{{ route('data-entry') }}" class="text-decoration-none">
                    <div class="bg-white rounded-4 shadow-sm p-3 h-100 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="rounded-3 bg-primary bg-opacity-10 p-2 text-primary"><i class="fas fa-edit"></i></span>
                            <span class="badge bg-primary rounded-pill">اليومي</span>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">إدخال البيانات</h5>
                            <p class="small text-muted mb-0">سجل وضعك اليومي.</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-4">
                <a href="{{ route('reports') }}" class="text-decoration-none">
                    <div class="bg-white rounded-4 shadow-sm p-3 h-100 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="rounded-3 bg-info bg-opacity-10 p-2 text-info"><i class="fas fa-chart-line"></i></span>
                            <span class="badge bg-info rounded-pill">تقارير</span>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">عرض الرسوم</h5>
                            <p class="small text-muted mb-0">متابعة الأداء الصحي.</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-4">
                <a href="{{ route('devices') }}" class="text-decoration-none">
                    <div class="bg-white rounded-4 shadow-sm p-3 h-100 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="rounded-3 bg-secondary bg-opacity-10 p-2 text-secondary"><i class="fas fa-microchip"></i></span>
                            <span class="badge bg-secondary rounded-pill">الأجهزة</span>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">الاتصال بالجهاز</h5>
                            <p class="small text-muted mb-0">عرض الأجهزة المتصلة.</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-4">
                <a href="{{ route('map') }}" class="text-decoration-none">
                    <div class="bg-white rounded-4 shadow-sm p-3 h-100 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="rounded-3 bg-success bg-opacity-10 p-2 text-success"><i class="fas fa-map"></i></span>
                            <span class="badge bg-success rounded-pill">خريطة</span>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">مواقع الحالة</h5>
                            <p class="small text-muted mb-0">استعرض موقعك والمراقبة.</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-4">
                <a href="{{ route('family') }}" class="text-decoration-none">
                    <div class="bg-white rounded-4 shadow-sm p-3 h-100 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="rounded-3 bg-warning bg-opacity-10 p-2 text-warning"><i class="fas fa-users"></i></span>
                            <span class="badge bg-warning rounded-pill">عائلة</span>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">فرد العائلة</h5>
                            <p class="small text-muted mb-0">تابع التواصل والدعم.</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-4">
                <a href="{{ route('doctor') }}" class="text-decoration-none">
                    <div class="bg-white rounded-4 shadow-sm p-3 h-100 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="rounded-3 bg-danger bg-opacity-10 p-2 text-danger"><i class="fas fa-user-md"></i></span>
                            <span class="badge bg-danger rounded-pill">طبيب</span>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">التواصل مع الطبيب</h5>
                            <p class="small text-muted mb-0">رؤية الطبيب المخصص لك.</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-12">
                <a href="{{ route('notifications') }}" class="text-decoration-none">
                    <div class="bg-white rounded-4 shadow-sm p-3 d-flex align-items-center gap-3">
                        <span class="rounded-3 bg-dark bg-opacity-10 p-3 text-dark"><i class="fas fa-bell"></i></span>
                        <div>
                            <h5 class="fw-bold mb-1">التنبيهات والإشعارات</h5>
                            <p class="small text-muted mb-0">افتح التنبيهات الواردة الآن.</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <button type="button" onclick="triggerEmergency()" class="btn btn-danger btn-lg mb-4 w-100">طلب مساعدة الطوارئ</button>

        <a href="{{ route('risk-check') }}" class="btn btn-modern mb-4 d-block text-center text-decoration-none">فحص الخطر الآن</a>

        <!-- Vital Signs Section -->
        <div class="card-modern">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="h6 fw-bold mb-1">العلامات الحيوية الحالية</h3>
                    <p class="small text-muted mb-0">بيانات حية من الأجهزة المتصلة</p>
                </div>
                <span class="badge rounded-pill bg-success p-2" id="deviceStatus">متصل</span>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4">
                    <div class="bg-white rounded-4 shadow-sm p-3 text-center">
                        <p class="text-muted small mb-2">معدل النبض</p>
                        <div class="circular-chart mx-auto mb-3" id="heartRateChart">
                            <span class="chart-value" id="heartRate">{{ $liveDeviceData['ecg']['heart_rate'] ?? '--' }}</span>
                        </div>
                        <p class="small text-muted">نبضة/دقيقة</p>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="bg-white rounded-4 shadow-sm p-3 text-center">
                        <p class="text-muted small mb-2">ضغط الدم</p>
                        <div class="circular-chart mx-auto mb-3" id="bloodPressureChart">
                            <span class="chart-value" id="bloodPressure">{{ $liveDeviceData['ecg']['blood_pressure_systolic'] ?? '--' }}/{{ $liveDeviceData['ecg']['blood_pressure_diastolic'] ?? '--' }}</span>
                        </div>
                        <p class="small text-muted">mmHg</p>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="bg-white rounded-4 shadow-sm p-3 text-center">
                        <p class="text-muted small mb-2">توتر العضلات</p>
                        <div class="circular-chart mx-auto mb-3" id="muscleTensionChart">
                            <span class="chart-value" id="muscleTension">{{ $liveDeviceData['emg']['tension'] ?? '--' }}</span>
                        </div>
                        <p class="small text-muted">%</p>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="bg-white rounded-4 shadow-sm p-3 text-center">
                        <p class="text-muted small mb-2">نشاط المخ (ألفا)</p>
                        <div class="circular-chart mx-auto mb-3" id="brainAlphaChart">
                            <span class="chart-value" id="brainAlpha">{{ $liveDeviceData['eeg']['alpha'] ?? '--' }}</span>
                        </div>
                        <p class="small text-muted">Hz</p>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="bg-white rounded-4 shadow-sm p-3 text-center">
                        <p class="text-muted small mb-2">نشاط المخ (بيتا)</p>
                        <div class="circular-chart mx-auto mb-3" id="brainBetaChart">
                            <span class="chart-value" id="brainBeta">{{ $liveDeviceData['eeg']['beta'] ?? '--' }}</span>
                        </div>
                        <p class="small text-muted">Hz</p>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="bg-white rounded-4 shadow-sm p-3 text-center">
                        <p class="text-muted small mb-2">إشارات الأعصاب</p>
                        <div class="circular-chart mx-auto mb-3" id="nerveSignalsChart">
                            <span class="chart-value" id="nerveSignals">{{ $liveDeviceData['emg']['nerve_signals'] ?? '--' }}</span>
                        </div>
                        <p class="small text-muted">%</p>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-between gap-2 flex-wrap">
                <div class="stat-box flex-1 bg-white rounded-4 shadow-sm p-3">
                    <h4 class="mb-2">حالة الأجهزة</h4>
                    <p class="text-{{ $connectedDevices > 0 ? 'success' : 'danger' }} small mb-0">
                        {{ $connectedDevices > 0 ? 'متصلة وتعمل' : 'غير متصلة' }}
                    </p>
                </div>
                <div class="stat-box flex-1 bg-white rounded-4 shadow-sm p-3">
                    <h4 class="mb-2">آخر تحديث</h4>
                    <p class="small mb-0" id="lastUpdate">
                        {{ $liveDeviceData['last_updated'] ? $liveDeviceData['last_updated']->diffForHumans() : 'لا توجد بيانات' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    @include('components.bottom-nav', ['active' => 'dashboard'])

    <!-- PWA Install Banner -->
    <div id="install-banner" class="d-none fixed-top m-3 bg-white p-3 rounded-4 shadow-lg border border-primary border-2 z-3">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <img src="/img/logo.png" class="rounded-3" style="width: 40px; height: 40px;">
                <div>
                    <p class="fw-bold small mb-0">تثبيت تطبيق سندك</p>
                    <p class="text-muted mb-0" style="font-size: 10px;">للوصول السريع وتنبيهات الطوارئ</p>
                </div>
            </div>
            <button id="install-button" class="btn btn-primary btn-sm rounded-3 px-3">تثبيت</button>
        </div>
    </div>

    <form id="emergency-form" action="{{ route('emergency.trigger') }}" method="POST" class="d-none">
        @csrf
    </form>

    <script>
        const patientId = {{ auth()->id() }};
        const appDebug = {{ config('app.debug') ? 'true' : 'false' }};
        const cacheKey = `patient_${patientId}_realtime`;

        let deferredPrompt;
        const installBanner = document.getElementById('install-banner');
        const installButton = document.getElementById('install-button');

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            installBanner.classList.remove('d-none');
        });

        installButton.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    installBanner.classList.add('d-none');
                }
                deferredPrompt = null;
            }
        });

        function triggerEmergency() {
            if (confirm('هل أنت متأكد من إرسال تنبيه الطوارئ؟')) {
                document.getElementById('emergency-form').submit();
            }
        }

        function parseNumeric(value) {
            if (value === null || value === undefined || value === '') {
                return null;
            }
            const number = parseFloat(value);
            return Number.isFinite(number) ? number : null;
        }

        function calculateRiskScoreFromLive(data) {
            let score = 50;
            const heartRate = parseNumeric(data.heart_rate);
            const muscleTension = parseNumeric(data.muscle_tension);
            const brainAlpha = parseNumeric(data.brain_alpha);
            const brainBeta = parseNumeric(data.brain_beta);
            const brainActivity = data.brain_activity;

            if (heartRate !== null) {
                score += (heartRate - 70) * 0.3;
            }
            if (muscleTension !== null) {
                score += (muscleTension - 50) * 0.25;
            }
            if (brainAlpha !== null) {
                score += (brainAlpha - 10) * 0.15;
            }
            if (brainBeta !== null) {
                score += (brainBeta - 20) * 0.1;
            }
            if (brainActivity && brainActivity.toLowerCase() === 'abnormal') {
                score += 8;
            }

            return Math.round(Math.max(10, Math.min(95, score)));
        }

        function getRiskLabel(score) {
            if (score >= 70) return 'عالي';
            if (score >= 40) return 'متوسط';
            return 'منخفض';
        }

        function updateRiskCard(score) {
            const label = getRiskLabel(score);
            document.getElementById('riskScoreValue').textContent = score;
            document.getElementById('riskScoreLabel').textContent = label;
            document.getElementById('riskPredictionText').classList.remove('d-none');
            document.getElementById('riskPredictionText').textContent = `احتمال حدوث نوبة خلال 30 دقيقة القادمة ${label}. يرجى أخذ احتياطاتك.`;
            document.getElementById('riskProgress').style.background = `conic-gradient(var(--primary) ${score}%, #EDF2F7 0deg)`;
        }

        function calculateStressLabel(heartRate) {
            if (!heartRate) return '--';
            if (heartRate > 100) return 'عالي';
            if (heartRate > 80) return 'متوسط';
            return 'منخفض';
        }

        function calculateActivityLabel(muscleTension) {
            if (!muscleTension) return '--';
            if (muscleTension > 70) return 'عالي';
            if (muscleTension > 40) return 'متوسط';
            return 'منخفض';
        }

        function setConnectionStatus(status, label) {
            const badge = document.getElementById('connectionBadge');
            if (!badge) return;
            badge.textContent = label;
            badge.className = 'badge rounded-pill p-2';
            if (status === 'connected') {
                badge.classList.add('bg-success');
            } else if (status === 'warning') {
                badge.classList.add('bg-warning', 'text-dark');
            } else {
                badge.classList.add('bg-danger');
            }
        }

        function formatTimestamp(date) {
            if (!date) return '--';
            const parsed = new Date(date);
            if (Number.isNaN(parsed.getTime())) return '--';
            return parsed.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }

        async function cacheRealtimeData(payload) {
            try {
                if (!window.indexedDB) {
                    localStorage.setItem(cacheKey, JSON.stringify(payload));
                    return;
                }
                const dbName = 'sanadak-patient-cache';
                const storeName = 'patient_store';
                const request = indexedDB.open(dbName, 1);
                request.onupgradeneeded = () => {
                    request.result.createObjectStore(storeName);
                };
                request.onsuccess = () => {
                    const db = request.result;
                    const tx = db.transaction(storeName, 'readwrite');
                    tx.objectStore(storeName).put(payload, patientId);
                };
            } catch (error) {
                localStorage.setItem(cacheKey, JSON.stringify(payload));
            }
        }

        async function loadCachedRealtimeData() {
            try {
                if (window.indexedDB) {
                    return new Promise((resolve) => {
                        const dbName = 'sanadak-patient-cache';
                        const storeName = 'patient_store';
                        const request = indexedDB.open(dbName, 1);
                        request.onsuccess = () => {
                            const db = request.result;
                            const tx = db.transaction(storeName, 'readonly');
                            const getReq = tx.objectStore(storeName).get(patientId);
                            getReq.onsuccess = () => resolve(getReq.result || null);
                            getReq.onerror = () => resolve(null);
                        };
                        request.onerror = () => resolve(null);
                    });
                }
                return JSON.parse(localStorage.getItem(cacheKey) || 'null');
            } catch (error) {
                return null;
            }
        }

        function normalizeRealtimeData(payload) {
            const eventData = payload?.data || payload || {};
            const vital = eventData.vital_sign || {};
            const analysis = eventData.analysis || {};
            const data = {
                heart_rate: vital.heart_rate,
                blood_pressure_systolic: vital.blood_pressure?.split('/')?.[0] || vital.systolic || vital.blood_pressure_systolic,
                blood_pressure_diastolic: vital.blood_pressure?.split('/')?.[1] || vital.diastolic || vital.blood_pressure_diastolic,
                muscle_tension: vital.muscle_tension,
                nerve_signals: vital.nerve_signals,
                brain_alpha: vital.brain_alpha,
                brain_beta: vital.brain_beta,
                brain_activity: eventData.eeg_status || vital.brain_activity,
                oxygen_level: vital.oxygen_level,
                temperature: vital.temperature,
                last_updated: eventData.timestamp || new Date().toISOString(),
                analysis,
                eeg_status: eventData.eeg_status || 'غير معروف',
            };
            if (eventData.eeg_wave && typeof eventData.eeg_wave === 'object') {
                const eegValues = Object.values(eventData.eeg_wave).map(Number).filter(Number.isFinite);
                if (eegValues.length) {
                    const alphaChunk = eegValues.slice(0, 2);
                    const betaChunk = eegValues.slice(2);
                    data.brain_alpha = Math.round(alphaChunk.reduce((a,b)=>a+b,0) / Math.max(alphaChunk.length, 1));
                    data.brain_beta = Math.round(betaChunk.reduce((a,b)=>a+b,0) / Math.max(betaChunk.length, 1));
                }
            }
            return data;
        }

        function applyRealtimeData(payload) {
            const normalized = normalizeRealtimeData(payload);
            const riskScore = normalized.analysis?.risk_score !== undefined
                ? Math.round(Number(normalized.analysis.risk_score) * 100)
                : calculateRiskScoreFromLive(normalized);

            updateRiskCard(riskScore);
            document.getElementById('liveHeartRate').textContent = normalized.heart_rate || '--';
            document.getElementById('liveStressLevel').textContent = calculateStressLabel(normalized.heart_rate);
            document.getElementById('liveActivityLevel').textContent = calculateActivityLabel(normalized.muscle_tension);
            document.getElementById('eegStatus').textContent = normalized.eeg_status || 'غير معروف';
            document.getElementById('lastReceivedAt').textContent = formatTimestamp(normalized.last_updated);
            document.getElementById('deviceStatus').textContent = 'متصل';
            document.getElementById('deviceStatus').className = 'badge rounded-pill bg-success p-2';
            updateDeviceDisplay(normalized);

            if (appDebug) {
                console.log('Realtime payload received:', payload);
            }
        }

        // Live device data update function
        async function fetchLiveDeviceData() {
            try {
                const response = await fetch('{{ route("devices.live-data") }}', { cache: 'no-store' });
                const data = await response.json();

                if (data.success) {
                    const normalized = {
                        heart_rate: data.heart_rate,
                        blood_pressure_systolic: data.blood_pressure_systolic,
                        blood_pressure_diastolic: data.blood_pressure_diastolic,
                        muscle_tension: data.muscle_tension,
                        nerve_signals: data.nerve_signals,
                        brain_alpha: data.brain_alpha,
                        brain_beta: data.brain_beta,
                        brain_activity: data.brain_activity,
                        oxygen_level: data.oxygen_level,
                        temperature: data.temperature,
                        last_updated: data.last_updated,
                    };
                    document.getElementById('liveHeartRate').textContent = data.heart_rate || '--';
                    document.getElementById('liveStressLevel').textContent = calculateStressLabel(data.heart_rate);
                    document.getElementById('liveActivityLevel').textContent = calculateActivityLabel(data.muscle_tension);
                    document.getElementById('eegStatus').textContent = data.brain_activity || 'غير معروف';
                    document.getElementById('lastReceivedAt').textContent = formatTimestamp(data.last_updated);
                    document.getElementById('deviceStatus').textContent = 'متصل';
                    document.getElementById('deviceStatus').className = 'badge rounded-pill bg-success p-2';
                    updateDeviceDisplay(normalized);
                    setConnectionStatus('connected', 'متصل');
                    updateRiskCard(calculateRiskScoreFromLive(normalized));
                }
            } catch (error) {
                console.error('Error updating live data:', error);
            }
        }

        function subscribeRealtime() {
            if (!window.Echo) {
                setConnectionStatus('warning', 'إعادة الاتصال...');
                setTimeout(subscribeRealtime, 1500);
                return;
            }

            try {
                const channel = window.Echo.private(`patient.${patientId}`);
                channel.listen('MedicalDataUpdated', (event) => {
                    setConnectionStatus('connected', 'متصل');
                    const payload = event?.data || event;
                    cacheRealtimeData({ payload, receivedAt: new Date().toISOString() });
                    applyRealtimeData(payload);
                    if (appDebug) {
                        console.log('MedicalDataUpdated received:', payload);
                    }
                });

                const connector = window.Echo.connector;
                if (connector && connector.socket && connector.socket.on) {
                    connector.socket.on('connect', () => {
                        setConnectionStatus('connected', 'متصل');
                    });
                    connector.socket.on('disconnect', () => {
                        setConnectionStatus('warning', 'إعادة الاتصال...');
                    });
                }
            } catch (error) {
                console.error('Echo subscribe error:', error);
                setConnectionStatus('warning', 'إعادة الاتصال...');
            }
        }

        async function initializeRealtime() {
            const cached = await loadCachedRealtimeData();
            if (cached?.payload) {
                applyRealtimeData(cached.payload);
            }
            fetchLiveDeviceData();
            subscribeRealtime();
        }

        // AI Analysis functionality
        document.getElementById('analyzeBtn').addEventListener('click', function() {
            const btn = this;
            const originalText = btn.innerHTML;
            const analysisResults = document.getElementById('analysisResults');
            const analysisLoading = document.getElementById('analysisLoading');

            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;
            analysisResults.classList.add('d-none');
            analysisLoading.classList.remove('d-none');

            fetch('{{ route("ai.analysis") }}', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                displayAnalysisResults(data);
                analysisResults.classList.remove('d-none');
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء التحليل. تأكد من وجود أجهزة متصلة.');
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                analysisLoading.classList.add('d-none');
            });
        });

        function displayAnalysisResults(data) {
            document.getElementById('currentActivity').textContent = getActivityText(data.activity);
            document.getElementById('riskLevel').textContent = getRiskText(data.risk_level) + ` (${Math.round(data.probability * 100)}%)`;

            document.getElementById('timeToEvent').textContent = data.time_to_event || 'غير محدد';

            const riskPredictionText = document.getElementById('riskPredictionText');
            riskPredictionText.classList.remove('d-none');
            const timeText = data.time_to_event ? `خلال ${data.time_to_event}` : 'في وقت غير محدد';
            riskPredictionText.textContent = `احتمال حدوث نوبة ${timeText}. ${getRiskText(data.risk_level)}. يرجى أخذ احتياطاتك.`;

            const recommendationsDiv = document.getElementById('recommendations');
            const recommendationsList = document.getElementById('recommendationsList');

            if (data.recommendations && data.recommendations.length > 0) {
                recommendationsList.innerHTML = data.recommendations.map(rec =>
                    `<div class="list-group-item"><i class="fas fa-lightbulb text-warning me-2"></i>${rec}</div>`
                ).join('');
                recommendationsDiv.classList.remove('d-none');
            }

            const emergencyInfo = document.getElementById('emergencyInfo');
            if (data.emergency_trigger) {
                emergencyInfo.classList.remove('d-none');
            }

            if (data.ai_explanation) {
                const explanationDiv = document.createElement('div');
                explanationDiv.className = 'mt-3 p-3 bg-light rounded-3';
                explanationDiv.innerHTML = `<h6 class="fw-bold mb-2">تفسير الذكاء الاصطناعي:</h6><p class="small mb-0">${data.ai_explanation}</p>`;
                document.getElementById('analysisResults').appendChild(explanationDiv);
            }
        }

        function getActivityText(activity) {
            const activities = {
                'walking': 'المشي',
                'running': 'الجري',
                'resting': 'الراحة',
                'sleeping': 'النوم',
                'driving': 'القيادة',
                'working': 'العمل'
            };
            return activities[activity] || activity;
        }

        function getRiskText(risk) {
            const risks = {
                'low': 'منخفض',
                'medium': 'متوسط',
                'high': 'عالي'
            };
            return risks[risk] || risk;
        }

        function clamp(value, min, max) {
            return Math.min(Math.max(value, min), max);
        }

        function gaugePercent(metric, data) {
            switch (metric) {
                case 'heartRate': {
                    const val = Number(data.heart_rate);
                    if (!val) return 0;
                    return clamp(((val - 40) / 140) * 100, 0, 100);
                }
                case 'bloodPressure': {
                    const sys = Number(data.blood_pressure_systolic);
                    const dia = Number(data.blood_pressure_diastolic);
                    if (!sys || !dia) return 0;
                    const avg = ((sys / 180) + (dia / 120)) / 2 * 100;
                    return clamp(avg, 0, 100);
                }
                case 'muscleTension':
                    return clamp(Number(data.muscle_tension) || 0, 0, 100);
                case 'nerveSignals':
                    return clamp(Number(data.nerve_signals) || 0, 0, 100);
                case 'brainAlpha': {
                    if (data.brain_alpha) {
                        return clamp(((Number(data.brain_alpha) - 4) / 40) * 100, 0, 100);
                    }
                    return data.brain_activity_alpha === 'normal' || data.brain_activity === 'normal' ? 65 : 0;
                }
                case 'brainBeta': {
                    if (data.brain_beta) {
                        return clamp(((Number(data.brain_beta) - 12) / 50) * 100, 0, 100);
                    }
                    return data.brain_activity_beta === 'normal' || data.brain_activity === 'normal' ? 75 : 0;
                }
                default:
                    return 0;
            }
        }

        function updateRing(chartId, percent, color) {
            const element = document.getElementById(chartId);
            if (!element) return;
            element.style.background = `conic-gradient(${color} ${percent}%, #EDF2F7 0deg)`;
        }

        function updateDeviceDisplay(data) {
            document.getElementById('heartRate').textContent = data.heart_rate || '--';
            document.getElementById('bloodPressure').textContent =
                (data.blood_pressure_systolic || '--') + '/' + (data.blood_pressure_diastolic || '--');

            document.getElementById('muscleTension').textContent = data.muscle_tension || '--';
            document.getElementById('nerveSignals').textContent = data.nerve_signals || '--';

            document.getElementById('brainAlpha').textContent = data.brain_alpha || data.brain_activity || '--';
            document.getElementById('brainBeta').textContent = data.brain_beta || data.brain_activity || '--';

            if (data.last_updated) {
                document.getElementById('lastUpdate').textContent = formatTimestamp(data.last_updated);
            }

            const hasData = data.heart_rate || data.muscle_tension || data.brain_alpha || data.brain_beta || data.brain_activity;
            const statusElement = document.getElementById('deviceStatus');
            statusElement.textContent = hasData ? 'متصل' : 'غير متصل';
            statusElement.className = hasData ? 'badge rounded-pill bg-success p-2' : 'badge rounded-pill bg-danger p-2';

            updateRing('heartRateChart', gaugePercent('heartRate', data), 'var(--primary)');
            updateRing('bloodPressureChart', gaugePercent('bloodPressure', data), '#F59E0B');
            updateRing('muscleTensionChart', gaugePercent('muscleTension', data), 'var(--secondary)');
            updateRing('nerveSignalsChart', gaugePercent('nerveSignals', data), '#EC4899');
            updateRing('brainAlphaChart', gaugePercent('brainAlpha', data), '#0EA5E9');
            updateRing('brainBetaChart', gaugePercent('brainBeta', data), '#7C3AED');
        }

        document.getElementById('stopSimulationBtn').addEventListener('click', async function() {
            try {
                const response = await fetch('{{ route('devices.stop-simulation') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                }
            } catch (error) {
                console.error('Error stopping simulation:', error);
                alert('فشل في إيقاف المحاكاة. حاول مرة أخرى.');
            }
        });

        initializeRealtime();
    </script>

    @php
        $reverbHost = config('broadcasting.connections.reverb.host', request()->getHost());
        $reverbPort = (int) config('broadcasting.connections.reverb.port', 6001);
        $reverbScheme = config('broadcasting.connections.reverb.scheme', request()->getScheme());
        $reverbUseTls = config('broadcasting.connections.reverb.useTLS', $reverbScheme === 'https') ? 'true' : 'false';
        $reverbKey = config('broadcasting.connections.reverb.key');
    @endphp

    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.3/dist/echo.iife.js"></script>
    <script>
        if (typeof window.Echo === 'undefined' && typeof Echo !== 'undefined') {
            window.Echo = new Echo({
                broadcaster: 'pusher',
                key: '{{ $reverbKey }}',
                wsHost: '{{ $reverbHost }}',
                wsPort: {{ $reverbPort }},
                wssPort: {{ $reverbPort }},
                forceTLS: {{ $reverbUseTls }},
                enabledTransports: ['ws', 'wss'],
                disableStats: true,
                authEndpoint: '/broadcasting/auth',
            });
        }
    </script>
</x-app-layout>
