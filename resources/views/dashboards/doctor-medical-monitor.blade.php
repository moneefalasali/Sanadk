@extends('layouts.app')

@section('content')
@php
    $analysisRisk = round((optional($analysis)->risk_score ?? 0.15) * 100);
    $analysisLabel = optional($analysis)->alert_level ?? (optional($currentPatient)->status_text ?? 'غير معروفة');
@endphp
<div class="doctor-monitor-container">
    <!-- Medical Header -->
    <div class="medical-header">
        <div class="hospital-info">
            <i class="fas fa-hospital-user"></i>
            <div>
                <h3> سندك  </h3>
                <p>نظام مراقبة المرضى  - لوحة الطبيب</p>
            </div>
        </div>
        <div class="doctor-nav">
            <a href="{{ route('doctor') }}" class="doctor-nav-link"><i class="fas fa-home"></i> لوحة الطبيب</a>
            <a href="{{ route('doctor.monitor') }}" class="doctor-nav-link"><i class="fas fa-heartbeat"></i> المراقبة</a>
            <a href="{{ route('map') }}" class="doctor-nav-link"><i class="fas fa-map-marked-alt"></i> الخريطة</a>
            <a href="{{ route('doctor.reports') }}" class="doctor-nav-link"><i class="fas fa-file-medical"></i> التقارير</a>
            <a href="{{ route('notifications') }}" class="doctor-nav-link"><i class="fas fa-bell"></i> الإشعارات</a>
        </div>
        <div class="doctor-info">
            <p>د. {{ Auth::user()->name }}</p>
            <span class="badge badge-online">متصل الآن</span>
        </div>
    </div>

    <!-- Patient Selection & Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="patient-selector-card">
                <h5><i class="fas fa-users"></i> قائمة المرضى النشطين</h5>
                <div class="patient-grid" id="active-patients-list">
                    <!-- Populated from controller: $patients, $currentPatient -->
                    @foreach(($patients ?? collect()) as $p)
                        <a href="{{ route('doctor.monitor', ['patient' => $p->id]) }}" class="patient-mini-card {{ isset($currentPatient) && $p->id === $currentPatient->id ? 'active' : '' }}" data-patient-id="{{ $p->id }}">
                            <div class="avatar">{{ strtoupper(substr($p->name,0,1) . (isset($p->name) ? substr($p->name,1,1) : '')) }}</div>
                            <div class="details">
                                <h6>{{ $p->name }}</h6>
                                <small>{{ $p->status_text ?? ($p->status ?? 'غير معروف') }}</small>
                            </div>
                            <div class="hr-mini">{{ optional($p->latest_vitals)->heart_rate ?? '-' }} BPM</div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="quick-stats-card">
                <div class="stat-item">
                    <span class="label">إجمالي المرضى</span>
                    <span class="value">{{ $totalPatients ?? count($patients ?? []) }}</span>
                </div>
                <div class="stat-item alert">
                    <span class="label">تنبيهات نشطة</span>
                    <span class="value">{{ $activeAlertsCount ?? ($alerts ? count($alerts) : 0) }}</span>
                </div>
                <div class="stat-item">
                    <span class="label">حالات التنبؤ</span>
                    <span class="value">{{ isset($predictionPercent) ? $predictionPercent . '%' : ($predictionRate ?? 'N/A') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN MONITORING AREA (Hospital Style) -->
    <div class="hospital-monitor-area">
        <div class="monitor-sidebar">
            <div class="patient-profile">
                <img src="{{ asset('img/patient-avatar.png') }}" alt="Patient">
                <h4>{{ optional($currentPatient)->name ?? 'مريض غير محدد' }}</h4>
                <p>ID: #PT-{{ optional($currentPatient)->external_id ?? (optional($currentPatient)->id ?? '----') }}</p>
                <div style="margin-top:6px;">
                    <span id="patient-connection-status" class="badge badge-online">متصل</span>
                </div>
                <div class="tags">
                    <span class="tag">{{ optional($currentPatient)->diagnosis ?? 'بدون تشخيص' }}</span>
                    @if(optional($analysis)->alert_level === 'emergency' || optional($currentPatient)->high_risk)
                        <span class="tag high-risk">عالي الخطورة</span>
                    @endif
                </div>
            </div>
            
            <div class="device-status-list">
                <div id="eeg-status" class="device-item">
                    <i class="fas fa-brain"></i>
                    <span class="eeg-label">EEG: <strong id="eeg-status-text">غير معروف</strong></span>
                    <span class="status-dot"></span>
                </div>
                @foreach(optional($currentPatient)->devices ?? [] as $device)
                    @php
                        $cls = 'device-item';
                        if (($device->status ?? '') === 'connected') $cls .= ' connected';
                        if (($device->status ?? '') === 'warning') $cls .= ' warning';
                    @endphp
                    <div class="{{ $cls }}">
                        @if($device->type === 'ecg') <i class="fas fa-heartbeat"></i> @elseif($device->type === 'eeg') <i class="fas fa-brain"></i> @else <i class="fas fa-microchip"></i> @endif
                        {{ $device->name ?? $device->model ?? 'جهاز' }}
                        <span class="status-dot"></span>
                    </div>
                @endforeach
            </div>

            <div class="actions">
                <a href="{{ route('doctor') }}" class="btn btn-report"><i class="fas fa-home"></i> لوحة الطبيب</a>
                <button class="btn btn-emergency"><i class="fas fa-phone"></i> اتصال طارئ</button>
                <button class="btn btn-report"><i class="fas fa-file-medical"></i> تقرير فوري</button>
                @if(config('app.debug'))
                    <form id="test-event-form" method="POST" action="{{ url('/test/patient/' . (optional($currentPatient)->id ?? ($patient->id ?? 0)) . '/emit') }}" style="margin-top:8px;">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-light">إرسال حدث تجريبي</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="monitor-main">
            <!-- ECG WAVEFORM -->
            <div class="waveform-container">
                <div class="waveform-header">
                    <div class="title">ECG - LEAD II</div>
                    <div class="values">
                        <div class="v-item">HR: <span id="hr-value" class="v-green pulse-value">{{ optional($currentVitals)->heart_rate ?? 72 }}</span></div>
                        <div class="v-item">ST: <span class="v-green">+0.1</span></div>
                    </div>
                </div>
                <canvas id="ecg-monitor" class="medical-canvas"></canvas>
            </div>

            <!-- EEG WAVEFORM -->
            <div class="waveform-container">
                <div class="waveform-header">
                    <div class="title">EEG - BRAIN WAVES (AF3, AF4, F3, F4)</div>
                    <div class="values">
                        <div class="v-item">ALPHA: <span class="v-blue">12Hz</span></div>
                        <div class="v-item">BETA: <span class="v-blue">22Hz</span></div>
                    </div>
                </div>
                <canvas id="eeg-monitor" class="medical-canvas"></canvas>
            </div>

            <!-- VITAL SIGNS BAR -->
            <div class="vitals-bar">
                            <div class="vital-stat">
                    <small>SpO2</small>
                    <div class="value-group">
                        <span id="spo2-value" class="val">{{ optional($currentVitals)->oxygen_level ?? '—' }}</span>
                        <span class="unit">%</span>
                    </div>
                    <div class="mini-wave" id="spo2-wave"></div>
                </div>
                <div class="vital-stat">
                    <small>RESP</small>
                    <div class="value-group">
                        <span id="resp-value" class="val">{{ optional($currentVitals)->respiratory_rate ?? '—' }}</span>
                        <span class="unit">RPM</span>
                    </div>
                    <div class="mini-wave" id="resp-wave"></div>
                </div>
                <div class="vital-stat">
                    <small>TEMP</small>
                    <div class="value-group">
                        <span id="temp-value" class="val">{{ optional($currentVitals)->temperature ?? '—' }}</span>
                        <span class="unit">°C</span>
                    </div>
                </div>
                <div class="vital-stat">
                    <small>NIBP</small>
                    <div class="value-group">
                        <span class="val">{{ optional($currentVitals)->blood_pressure ?? '—' }}</span>
                        <span class="unit">mmHg</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Prediction & Alerts -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card prediction-card">
                <div class="card-header">
                    <h5><i class="fas fa-robot"></i> تحليل الذكاء الاصطناعي (التنبؤ بالنوبات)</h5>
                </div>
                <div class="card-body">
                    <div class="risk-meter">
                        <div class="meter-bg">
                                <div id="risk-meter-fill" class="meter-fill" style="width: {{ $analysisRisk }}%;"></div>
                            </div>
                        <div class="meter-labels">
                            <span>منخفض</span>
                            <span>متوسط</span>
                            <span>عالي</span>
                        </div>
                    </div>
                    <div class="prediction-text">
                        <p><strong>الاحتمالية:</strong> <span id="risk-percent">{{ $analysisRisk }}%</span> خلال الـ 15 دقيقة القادمة</p>
                        <p><strong>الحالة:</strong> <span id="risk-label">{{ $analysisLabel }}</span></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card alerts-card">
                <div class="card-header">
                    <h5><i class="fas fa-history"></i> سجل التنبيهات الأخيرة</h5>
                </div>
                <div class="card-body">
                    <div class="alert-list-mini">
                        @foreach(($alerts ?? collect()) as $a)
                            <div class="alert-item-mini {{ $a->level === 'warning' ? 'warning' : 'normal' }}">
                                <span class="time">{{ $a->created_at->format('H:i') }}</span>
                                <span class="msg">{{ $a->message }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Hospital Monitor Theme */
    .doctor-monitor-container {
        background-color: #fcfcfc;
        color: #0c0c0c;
        padding: 20px;
----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------        min-height: 100vh;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .medical-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #f5f4f4;
        padding-bottom: 15px;
        margin-bottom: 20px;
        gap: 20px;
        flex-wrap: wrap;
    }

    .hospital-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .doctor-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .doctor-nav-link {
        color: #dbeafe;
        text-decoration: none;
        background: rgba(15, 23, 42, 0.9);
        border: 1px solid #1d4ed8;
        border-radius: 999px;
        padding: 8px 14px;
        font-size: 0.9rem;
        transition: transform .2s ease, background .2s ease;
    }

    .doctor-nav-link:hover {
        transform: translateY(-1px);
        background: rgba(30, 64, 175, 0.9);
    }

    .hospital-info i {
        font-size: 2.5rem;
        color: #007bff;
    }

    .hospital-info h3 {
        margin: 0;
        font-size: 1.5rem;
        color: #fff;
    }

    .hospital-info p {
        margin: 0;
        color: #888;
        font-size: 0.9rem;
    }

    .badge-online {
        background-color: #28a745;
        color: #fff;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
    }
    .badge-offline {
        background-color: #6c757d;
        color: #fff;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
    }

    /* Patient Selector */
    .patient-selector-card {
        background: #f0eeee;
        padding: 15px;
        border-radius: 10px;
        border: 1px solid #222;
    }

    .patient-grid {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        padding: 10px 0;
    }

    .patient-mini-card {
        background: #344db0;
        min-width: 150px;
        padding: 10px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        border: 1px solid transparent;
        transition: all 0.3s;
        color: #fff;
        text-decoration: none;
    }

    .patient-mini-card:hover {
        border-color: #38bdf8;
        transform: translateY(-1px);
    }

    .patient-mini-card.active {
        border-color: #007bff;
        background: #3587d9;
    }

    .patient-mini-card .avatar {
        width: 35px;
        height: 35px;
        background: #007bff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }

    .patient-mini-card h6 {
        margin: 0;
        font-size: 0.9rem;
    }

    .patient-mini-card small {
        color: #888;
        font-size: 0.7rem;
    }

    .hr-mini {
        margin-left: auto;
        color: #ecefec;
        font-weight: bold;
        font-size: 0.8rem;
    }

    /* Hospital Monitor Area */
    .hospital-monitor-area {
        display: grid;
        grid-template-columns: 250px 1fr;
        gap: 20px;
        background: #fbf9f9;
        border: 2px solid #333;
        border-radius: 15px;
        overflow: hidden;
    }

    .monitor-sidebar {
        background: #fbf9f9;
        padding: 20px;
        border-right: 1px solid #333;
    }

    .patient-profile {
        text-align: center;
        margin-bottom: 30px;
    }

    .patient-profile img {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        border: 3px solid #007bff;
        margin-bottom: 10px;
    }

    .patient-profile h4 {
        margin: 5px 0;
        font-size: 1.2rem;
    }

    .patient-profile p {
        color: #888;
        font-size: 0.8rem;
    }

    .tags {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        justify-content: center;
        margin-top: 10px;
    }

    .tag {
        background: #f5f2f2;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.7rem;
    }

    .tag.high-risk {
        background: #dc3545;
    }

    .device-status-list {
        margin-bottom: 30px;
    }

    .device-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 0;
        font-size: 0.9rem;
        color: #ccc;
    }

    .device-item .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-left: auto;
    }

    .device-item.connected .status-dot { background: #28a745; box-shadow: 0 0 5px #28a745; }
    .device-item.warning .status-dot { background: #ffc107; box-shadow: 0 0 5px #ffc107; }

    .btn-emergency {
        background: #dc3545;
        color: #fff;
        width: 100%;
        margin-bottom: 10px;
        font-weight: bold;
    }

    .btn-report {
        background: #007bff;
        color: #fff;
        width: 100%;
    }

    .pulse-value {
        animation: pulseGlow 1.2s ease-in-out infinite;
    }

    @keyframes pulseGlow {
        0%, 100% { text-shadow: 0 0 0 rgba(0,255,0,0.4); }
        50% { text-shadow: 0 0 12px rgba(0,255,0,0.9); }
    }

    /* Main Monitor Display */
    .monitor-main {
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .waveform-container {
        background: #f3f1f1;
        border: 1px solid #222;
        border-radius: 8px;
        padding: 10px;
    }

    .waveform-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-size: 0.8rem;
        font-weight: bold;
        color: #070707;
    }

    .waveform-header .values {
        display: flex;
        gap: 20px;
    }

    .v-green { color: #00ff00; }
    .v-blue { color: #00ccff; }

    .medical-canvas {
        width: 100%;
        height: 180px;
        background: #f8f6f6;
    }

    /* Vitals Bar */
    .vitals-bar {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-top: 10px;
    }

    .vital-stat {
        background: #ededed;
        padding: 15px;
        border-radius: 8px;
        border: 1px solid #222;
        text-align: center;
    }

    .vital-stat small {
        color: #f9f7f7;
        display: block;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .value-group {
        display: flex;
        align-items: baseline;
        justify-content: center;
        gap: 5px;
    }

    .vital-stat .val {
        font-size: 1.8rem;
        font-weight: bold;
        color: #00ff00;
    }

    .vital-stat .unit {
        font-size: 0.8rem;
        color: #888;
    }

    /* Prediction Card */
    .prediction-card, .alerts-card {
        background: #f6f4f4;
        border: 1px solid #222;
        color: #121212;
    }

    .prediction-card .card-header, .alerts-card .card-header {
        background: #fefbfb;
        border-bottom: 1px solid #0a0a0a;
    }

    .risk-meter {
        margin-bottom: 20px;
    }

    .meter-bg {
        height: 15px;
        background: #222;
        border-radius: 10px;
        overflow: hidden;
    }

    .meter-fill {
        height: 100%;
        background: linear-gradient(to right, #28a745, #ffc107, #dc3545);
        border-radius: 10px;
    }

    .meter-labels {
        display: flex;
        justify-content: space-between;
        font-size: 0.7rem;
        color: #888;
        margin-top: 5px;
    }

    .alert-item-mini {
        display: flex;
        gap: 15px;
        padding: 8px 0;
        border-bottom: 1px solid #222;
        font-size: 0.9rem;
    }

    .alert-item-mini.warning { color: #ffc107; }
    .alert-item-mini.normal { color: #888; }
</style>

<script src="{{ asset('js/services/MedicalWaveformVisualizer.js') }}"></script>
<script>
    // Initialize Medical Monitors
    const ecgMonitor = new MedicalWaveformVisualizer('ecg-monitor', {
        waveColor: '#00ff00',
        backgroundColor: '#d5cfcf',
        lineWidth: 2,
        speed: 3
    });

    const eegMonitor = new MedicalWaveformVisualizer('eeg-monitor', {
        waveColor: '#00ccff',
        backgroundColor: '#f8efef',
        lineWidth: 1.5,
        speed: 2
    });

    ecgMonitor.start();
    eegMonitor.start();

    // Simulate Real-time Data for Demo
    setInterval(() => {
        // ECG Simulation
        const hr = 70 + Math.floor(Math.random() * 10);
        document.getElementById('hr-value').textContent = hr;
        ecgMonitor.addDataPoint(hr);
        
        // EEG Simulation
        const eegData = {
            'AF3': Math.random() * 50,
            'AF4': Math.random() * 50,
            'F3': Math.random() * 50,
            'F4': Math.random() * 50
        };
        eegMonitor.drawEEGWaveform(eegData);
    }, 100);
</script>
<script>
    const patientId = "{{ $currentPatient->id ?? ($patient->id ?? 88291) }}";

    function updateDoctorPatientUI(event) {
        const data = event.data || event.data || event;
        const vital = data.vital_sign || {};
        const analysis = data.analysis || {};

        // Update connection status
        if (data.connection_status !== undefined) {
            const connEl = document.getElementById('patient-connection-status');
            if (connEl) {
                if (data.connection_status === 'connected' || data.connection_status === true) {
                    connEl.textContent = 'متصل';
                    connEl.classList.remove('badge-offline');
                    connEl.classList.add('badge-online');
                } else {
                    connEl.textContent = 'غير متصل';
                    connEl.classList.remove('badge-online');
                    connEl.classList.add('badge-offline');
                }
            }
        }

        if (vital.heart_rate !== undefined && vital.heart_rate !== null) {
            const hrEl = document.getElementById('hr-value');
            if (hrEl) hrEl.textContent = vital.heart_rate;
            if (typeof ecgMonitor !== 'undefined' && ecgMonitor.addDataPoint) {
                ecgMonitor.addDataPoint(vital.heart_rate);
            }
        }

        if (vital.oxygen_level !== undefined) {
            const spo2El = document.getElementById('spo2-value');
            if (spo2El) spo2El.textContent = vital.oxygen_level;
        }

        if (vital.temperature !== undefined) {
            const tempEl = document.getElementById('temp-value');
            if (tempEl) tempEl.textContent = vital.temperature.toFixed(1);
        }

        if (analysis.risk_score !== undefined) {
            const predictionText = document.querySelector('.prediction-text p strong');
            const riskPercent = Math.round(analysis.risk_score * 100);
            const riskFill = document.getElementById('risk-meter-fill');
            const riskPercentEl = document.getElementById('risk-percent');
            const riskLabelEl = document.getElementById('risk-label');
            if (riskFill) {
                riskFill.style.width = Math.min(100, Math.max(0, riskPercent)) + '%';
            }
            if (riskPercentEl) riskPercentEl.textContent = riskPercent + '%';
            if (riskLabelEl) riskLabelEl.textContent = analysis.alert_level || 'غير معروفة';
        }

        if (analysis.alert_level) {
            const highRiskTag = document.querySelector('.tag.high-risk');
            if (analysis.alert_level === 'emergency' && !highRiskTag) {
                const tags = document.querySelector('.tags');
                if (tags) {
                    const tag = document.createElement('span');
                    tag.className = 'tag high-risk';
                    tag.textContent = 'عالي الخطورة';
                    tags.appendChild(tag);
                }
            }
        }

        // Update devices statuses if provided
        if (Array.isArray(data.devices)) {
            data.devices.forEach(d => {
                // try to find matching device element by name
                const items = Array.from(document.querySelectorAll('.device-item'));
                for (const el of items) {
                    if (el.textContent && d.name && el.textContent.includes(d.name)) {
                        el.classList.remove('connected','warning');
                        if (d.status === 'connected') el.classList.add('connected');
                        if (d.status === 'warning') el.classList.add('warning');
                    }
                }
            });
        }

        // Update EEG status and waveform
        if (data.eeg_status !== undefined) {
            const eegText = document.getElementById('eeg-status-text');
            if (eegText) eegText.textContent = data.eeg_status;
        }
        if (data.eeg_wave || data.eegData) {
            // prefer eeg_wave then eegData
            const wave = data.eeg_wave || data.eegData;
            if (typeof eegMonitor !== 'undefined' && eegMonitor.drawEEGWaveform) {
                eegMonitor.drawEEGWaveform(wave);
            }
        }

        const alertList = document.querySelector('.alert-list-mini');
        if (alertList) {
            const item = document.createElement('div');
            item.className = 'alert-item-mini warning';
            item.innerHTML = `
                <span class="time">${new Date().toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' })}</span>
                <span class="msg">تحديث طبي مباشر: معدل ضربات القلب ${vital.heart_rate || '-'} BPM</span>
            `;
            alertList.prepend(item);
            while (alertList.children.length > 5) {
                alertList.removeChild(alertList.lastChild);
            }
        }
    }

    // Enhanced Echo subscription with debug, reconnection and caching
    (function initRealtime() {
        if (!patientId) return;

        const CACHE_DB = 'sanadak-patient-cache';
        const CACHE_STORE = 'patient_store';

        // Simple IndexedDB helpers
        function openDB() {
            return new Promise((resolve) => {
                if (!window.indexedDB) return resolve(null);
                const req = indexedDB.open(CACHE_DB, 1);
                req.onupgradeneeded = () => {
                    try { req.result.createObjectStore(CACHE_STORE); } catch(e){}
                };
                req.onsuccess = () => resolve(req.result);
                req.onerror = () => resolve(null);
            });
        }

        async function idbPut(key, value) {
            const db = await openDB();
            if (!db) return localStorage.setItem(key, JSON.stringify(value));
            return new Promise((res) => {
                const tx = db.transaction(CACHE_STORE, 'readwrite');
                tx.objectStore(CACHE_STORE).put(value, key);
                tx.oncomplete = () => res();
            });
        }

        async function idbGet(key) {
            const db = await openDB();
            if (!db) return JSON.parse(localStorage.getItem(key) || 'null');
            return new Promise((res) => {
                const tx = db.transaction(CACHE_STORE, 'readonly');
                const req = tx.objectStore(CACHE_STORE).get(key);
                req.onsuccess = () => res(req.result);
                req.onerror = () => res(null);
            });
        }

        function cacheKey() { return `patient_${patientId}_last`; }

        function showDebug(payload, timestamp) {
            if (!window.SANADAK_DEBUG) return;
            const el = document.getElementById('realtime-debug');
            if (!el) return;
            const tsEl = el.querySelector('.last-ts');
            const rawEl = el.querySelector('.raw-payload');
            if (tsEl) tsEl.textContent = timestamp || new Date().toISOString();
            if (rawEl) rawEl.textContent = JSON.stringify(payload, null, 2);
            console.log('Realtime event:', payload);
        }

        function subscribe() {
            try {
                const channel = window.Echo.private(`patient.${patientId}`);
                channel.listen('MedicalDataUpdated', (e) => {
                    const payload = e.data || e || {};
                    idbPut(cacheKey(), { payload, ts: e.timestamp || new Date().toISOString() });
                    updateDoctorPatientUI(payload);
                    showDebug(payload, e.timestamp || new Date().toISOString());
                });

                const sock = window.Echo.connector && (window.Echo.connector.socket || window.Echo.connector.reverb || window.Echo.connector.connection);
                if (sock && sock.on) {
                    sock.on('connect', () => {
                        const connEl = document.getElementById('patient-connection-status');
                        if (connEl) { connEl.textContent = 'متصل'; connEl.classList.add('badge-online'); connEl.classList.remove('badge-offline'); }
                        console.info('Socket connected');
                    });
                    sock.on('disconnect', () => {
                        const connEl = document.getElementById('patient-connection-status');
                        if (connEl) { connEl.textContent = 'غير متصل'; connEl.classList.remove('badge-online'); connEl.classList.add('badge-offline'); }
                        console.warn('Socket disconnected — attempting reconnect');
                    });
                }
            } catch (err) {
                console.error('Subscribe error', err);
            }
        }

        let subscribeAttempts = 0;
        (function trySubscribe() {
            if (!window.Echo) {
                console.warn('Echo not found, retrying...');
                subscribeAttempts++; if (subscribeAttempts < 20) setTimeout(trySubscribe, 2000 * Math.min(10, subscribeAttempts));
                return;
            }
            subscribe();
        })();

        (async () => {
            const cached = await idbGet(cacheKey());
            if (cached && cached.payload) {
                updateDoctorPatientUI(cached.payload);
                showDebug(cached.payload, cached.ts);
            }
        })();

        window.SANADAK_DEBUG = {{ config('app.debug') ? 'true' : 'false' }};
    })();
</script>
@endsection
