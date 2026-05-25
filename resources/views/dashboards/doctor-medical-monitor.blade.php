@extends('layouts.app')

@section('content')
@php
    $analysisRisk = round((optional($analysis)->risk_score ?? 0.15) * 100);
    $analysisLabel = optional($analysis)->alert_level ?? (optional($currentPatient)->status_text ?? 'مستقر');

    $initialEeg = $currentVitals?->eeg_signal
        ? json_decode($currentVitals->eeg_signal, true)
        : ['AF3' => 34, 'AF4' => 36, 'F3' => 28, 'F4' => 31];

    if (!is_array($initialEeg)) {
        $initialEeg = ['AF3' => 34, 'AF4' => 36, 'F3' => 28, 'F4' => 31];
    }

    $emgDevice = collect(optional($currentPatient)->devices ?? [])->firstWhere('type', 'emg');
    $emgPayload = $emgDevice?->last_data;
    if (!is_array($emgPayload)) {
        $emgPayload = [];
    }

    $initialEmg = [
        'tension' => $currentVitals?->emg_signal ?? $emgPayload['tension'] ?? 42,
        'nerve_signals' => $emgPayload['nerve_signals'] ?? 28,
        'muscle_activity' => $emgPayload['muscle_activity'] ?? 36,
    ];

    $patientDevices = collect(optional($currentPatient)->devices ?? [])->map(function ($device) {
        return [
            'name' => $device->name ?? $device->model ?? 'جهاز',
            'type' => $device->type ?? 'device',
            'status' => $device->status ?? 'connected',
        ];
    })->values()->all();
@endphp

<div class="doctor-monitor-container">
    <div class="medical-header">
        <div class="hospital-info">
            <i class="fas fa-hospital-user"></i>
            <div>
                <h3>سندك</h3>
                <p>نظام مراقبة المرضى - لوحة الطبيب</p>
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
            <span class="badge-online">متصل الآن</span>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-8">
            <div class="patient-selector-card">
                <h5><i class="fas fa-users"></i> قائمة المرضى النشطين</h5>
                <div class="patient-grid">
                    @foreach(($patients ?? collect()) as $p)
                        <a href="{{ route('doctor.monitor', ['patient' => $p->id]) }}" class="patient-mini-card {{ isset($currentPatient) && $p->id === $currentPatient->id ? 'active' : '' }}">
                            <div class="avatar">{{ strtoupper(substr($p->name, 0, 1)) }}</div>
                            <div>
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
                    <span class="value">{{ $activeAlertsCount ?? count($alerts ?? []) }}</span>
                </div>
                <div class="stat-item">
                    <span class="label">حالات التنبؤ</span>
                    <span class="value">{{ $analysisRisk }}%</span>
                </div>
            </div>
        </div>
    </div>

    <div class="hospital-monitor-area">
        <div class="monitor-sidebar">
            <div class="patient-profile">
                <img src="{{ asset('img/patient-avatar.png') }}" alt="Patient">
                <h4>{{ optional($currentPatient)->name ?? 'مريض غير محدد' }}</h4>
                <p>ID: #PT-{{ optional($currentPatient)->external_id ?? (optional($currentPatient)->id ?? '----') }}</p>
                <div style="margin-top: 8px;">
                    <span id="patient-connection-status" class="badge-online">متصل</span>
                </div>
                <div class="tags">
                    <span class="tag">{{ optional($currentPatient)->diagnosis ?? 'بدون تشخيص' }}</span>
                    @if(optional($analysis)->alert_level === 'emergency' || optional($currentPatient)->high_risk)
                        <span class="tag high-risk">عالي الخطورة</span>
                    @endif
                </div>
            </div>

            <div class="device-status-list">
                <div class="device-item" id="eeg-status">
                    <i class="fas fa-brain"></i>
                    <span class="device-label">EEG: <strong id="eeg-status-text">نشط</strong></span>
                    <span class="status-dot connected"></span>
                </div>
                @foreach(optional($currentPatient)->devices ?? [] as $device)
                    @php
                        $deviceClass = 'device-item';
                        if (($device->status ?? '') === 'connected') $deviceClass .= ' connected';
                        if (($device->status ?? '') === 'warning') $deviceClass .= ' warning';
                    @endphp
                    <div class="{{ $deviceClass }}" data-device-name="{{ $device->name ?? $device->model ?? 'جهاز' }}">
                        @if(($device->type ?? '') === 'ecg')
                            <i class="fas fa-heartbeat"></i>
                        @elseif(($device->type ?? '') === 'eeg')
                            <i class="fas fa-brain"></i>
                        @else
                            <i class="fas fa-microchip"></i>
                        @endif
                        <span class="device-label">{{ $device->name ?? $device->model ?? 'جهاز' }}</span>
                        <span class="status-dot"></span>
                    </div>
                @endforeach
            </div>

            <div class="actions">
                <a href="{{ route('doctor') }}" class="btn btn-report"><i class="fas fa-home"></i> لوحة الطبيب</a>
                <button class="btn btn-emergency"><i class="fas fa-phone"></i> اتصال طارئ</button>
                <button class="btn btn-report"><i class="fas fa-file-medical"></i> تقرير فوري</button>
            </div>
        </div>

        <div class="monitor-main">
            <div class="monitor-dashboard">
                <div class="monitor-summary">
                    <div>
                        <p class="eyebrow">استقبال البيانات الحيوية</p>
                        <h3>مراقبة ديناميكية للمريض المرتبط</h3>
                        <p class="monitor-copy">عرض مرن ومتكامل يطابق جميع أحجام الشاشات مع تحديث مباشر للقراءات.</p>
                    </div>
                    <div class="monitor-summary-tags">
                        <span class="summary-tag" id="live-time-tag">--:--</span>
                        <span class="summary-tag" id="risk-flavor-badge">{{ $analysisLabel }}</span>
                    </div>
                </div>

                <div class="waveform-grid">
                    <div class="waveform-panel">
                        <div class="waveform-header-row">
                            <div>
                                <p class="panel-label">ECG</p>
                                <h4>Lead II</h4>
                            </div>
                            <div class="waveform-metrics">
                                <span class="metric-pill">HR <strong id="hr-value">{{ optional($currentVitals)->heart_rate ?? 72 }}</strong></span>
                                <span class="metric-pill">حالة <strong id="ecg-status-badge">مستقرة</strong></span>
                            </div>
                        </div>
                        <div class="canvas-shell">
                            <canvas id="ecg-chart"></canvas>
                        </div>
                    </div>

                    <div class="waveform-panel">
                        <div class="waveform-header-row">
                            <div>
                                <p class="panel-label">EEG</p>
                                <h4>موجات الدماغ متعددة القنوات</h4>
                            </div>
                            <div class="waveform-metrics">
                                <span class="metric-pill">EEG <strong id="eeg-status-text">نشط</strong></span>
                                <span class="metric-pill">قنوات <strong>AF3 / AF4 / F3 / F4</strong></span>
                            </div>
                        </div>
                        <div class="canvas-shell">
                            <canvas id="eeg-chart"></canvas>
                        </div>
                    </div>

                    <div class="waveform-panel">
                        <div class="waveform-header-row">
                            <div>
                                <p class="panel-label">EMG</p>
                                <h4>توتر العضلات وإشارات الأعصاب</h4>
                            </div>
                            <div class="waveform-metrics">
                                <span class="metric-pill">EMG <strong id="emg-status-text">نشط</strong></span>
                                <span class="metric-pill">تنبيه <strong id="emg-alert-pill">مستقر</strong></span>
                            </div>
                        </div>
                        <div class="canvas-shell">
                            <canvas id="emg-chart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="vitals-strip">
                    <div class="vital-stat">
                        <small>SpO2</small>
                        <div class="value-group">
                            <span id="spo2-value" class="val">{{ optional($currentVitals)->oxygen_level ?? '—' }}</span>
                            <span class="unit">%</span>
                        </div>
                    </div>
                    <div class="vital-stat">
                        <small>RESP</small>
                        <div class="value-group">
                            <span id="resp-value" class="val">{{ optional($currentVitals)->respiratory_rate ?? '—' }}</span>
                            <span class="unit">RPM</span>
                        </div>
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
                            <span id="bp-value" class="val">{{ optional($currentVitals)->blood_pressure ?? '—' }}</span>
                            <span class="unit">mmHg</span>
                        </div>
                    </div>
                    <div class="vital-stat">
                        <small>EMG</small>
                        <div class="value-group">
                            <span id="emg-value" class="val">{{ $initialEmg['tension'] }}</span>
                            <span class="unit">%</span>
                        </div>
                    </div>
                    <div class="vital-stat">
                        <small>NERVE</small>
                        <div class="value-group">
                            <span id="nerve-value" class="val">{{ $initialEmg['nerve_signals'] }}</span>
                            <span class="unit">%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card prediction-card">
                <div class="card-header">
                    <h5><i class="fas fa-robot"></i> تحليل الذكاء الاصطناعي</h5>
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
    :root {
        --page-bg: #ffffff;
        --panel-bg: #ffffff;
        --panel-border: rgba(15, 23, 42, 0.1);
        --text-main: #0f172a;
        --text-muted: #475569;
        --accent: #0ea5e9;
        --accent-strong: #16a34a;
        --warning: #d97706;
        --danger: #dc2626;
        --shadow: 0 18px 42px rgba(15, 23, 42, 0.08);
    }

    .doctor-monitor-container {
        background: #ffffff;
        color: var(--text-main);
        padding: 20px;
        min-height: 100vh;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .medical-header,
    .patient-selector-card,
    .quick-stats-card,
    .hospital-monitor-area,
    .waveform-panel,
    .vital-stat,
    .prediction-card,
    .alerts-card {
        box-shadow: var(--shadow);
    }

    .medical-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
        border-bottom: 1px solid var(--panel-border);
        padding-bottom: 16px;
        margin-bottom: 20px;
    }

    .hospital-info { display: flex; align-items: center; gap: 15px; }
    .hospital-info i { font-size: 2.5rem; color: var(--accent); }
    .doctor-nav { display: flex; flex-wrap: wrap; gap: 10px; }
    .doctor-nav-link {
        color: var(--text-main);
        background: #ffffff;
        border: 1px solid var(--panel-border);
        border-radius: 999px;
        padding: 8px 14px;
        text-decoration: none;
    }
    .doctor-info p { margin: 0 0 6px; }

    .badge-online,
    .badge-warning,
    .badge-offline {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 999px;
        font-size: 0.8rem;
    }
    .badge-online { background: rgba(22, 163, 74, 0.12); color: #166534; }
    .badge-warning { background: rgba(217, 119, 6, 0.12); color: #92400e; }
    .badge-offline { background: rgba(220, 38, 38, 0.12); color: #991b1b; }

    .patient-selector-card,
    .quick-stats-card,
    .waveform-panel,
    .vital-stat,
    .prediction-card,
    .alerts-card {
        background: var(--panel-bg);
        border: 1px solid var(--panel-border);
        border-radius: 18px;
    }

    .patient-selector-card { padding: 18px; }
    .patient-grid { display: flex; gap: 12px; overflow-x: auto; padding-top: 10px; }
    .patient-mini-card {
        min-width: 170px;
        background: linear-gradient(180deg, rgba(30, 64, 175, 0.92), rgba(14, 116, 144, 0.82));
        color: #fff;
        border-radius: 16px;
        padding: 12px;
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
    }
    .patient-mini-card.active { border: 1px solid rgba(56, 189, 248, 0.95); }
    .patient-mini-card .avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        background: rgba(14, 165, 233, 0.95);
        font-weight: 700;
    }
    .hr-mini { margin-left: auto; font-weight: 700; }

    .quick-stats-card { padding: 18px; display: grid; gap: 12px; }
    .stat-item {
        display: flex;
        justify-content: space-between;
        padding: 10px 12px;
        border-radius: 14px;
        background: #f8fafc;
    }
    .stat-item .label { color: var(--text-muted); }
    .stat-item .value { color: var(--text-main); font-weight: 700; }

    .hospital-monitor-area {
        display: grid;
        grid-template-columns: minmax(290px, 320px) minmax(0, 1fr);
        gap: 20px;
        border-radius: 22px;
        overflow: hidden;
    }
    .monitor-sidebar {
        padding: 22px;
        background: #ffffff;
        border-right: 1px solid var(--panel-border);
    }
    .patient-profile { text-align: center; margin-bottom: 28px; }
    .patient-profile img {
        width: 84px;
        height: 84px;
        border-radius: 50%;
        border: 3px solid rgba(14, 165, 233, 0.8);
        object-fit: cover;
    }
    .patient-profile h4 { margin: 8px 0 4px; color: var(--text-main); }
    .patient-profile p { color: var(--text-muted); font-size: 0.85rem; }
    .tags { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; margin-top: 10px; }
    .tag {
        padding: 4px 10px;
        border-radius: 999px;
        background: #f8fafc;
        color: var(--text-main);
        font-size: 0.75rem;
        border: 1px solid var(--panel-border);
    }
    .tag.high-risk { background: rgba(220, 38, 38, 0.92); color: #fff; border-color: transparent; }
    .device-status-list { display: grid; gap: 10px; margin-bottom: 22px; }
    .device-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-radius: 14px;
        background: #f8fafc;
        color: var(--text-main);
        border: 1px solid var(--panel-border);
    }
    .device-item .device-label { flex: 1; }
    .device-item .status-dot {
        width: 10px;
        height: 10px;
        border-radius: 999px;
        background: #cbd5e1;
    }
    .device-item.connected .status-dot { background: #16a34a; box-shadow: 0 0 12px rgba(22, 163, 74, 0.35); }
    .device-item.warning .status-dot { background: #d97706; box-shadow: 0 0 12px rgba(217, 119, 6, 0.35); }
    .btn-emergency { background: linear-gradient(180deg, #dc2626, #b91c1c); color: #fff; width: 100%; margin-bottom: 10px; }
    .btn-report { background: linear-gradient(180deg, #2563eb, #1d4ed8); color: #fff; width: 100%; }
    .monitor-main { padding: 20px; }
    .monitor-dashboard { display: flex; flex-direction: column; gap: 18px; }
    .monitor-summary {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: end;
        padding: 18px 18px 8px;
        border-radius: 18px;
        background: #ffffff;
        border: 1px solid var(--panel-border);
    }
    .eyebrow { margin: 0 0 6px; color: var(--accent); font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.03em; }
    .monitor-summary h3 { margin: 0; color: var(--text-main); }
    .monitor-copy { margin: 8px 0 0; color: var(--text-muted); max-width: 660px; }
    .monitor-summary-tags { display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-end; }
    .summary-tag {
        padding: 8px 12px;
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid var(--panel-border);
        color: var(--text-main);
    }

    .waveform-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px; }
    .waveform-panel { padding: 16px; }
    .waveform-header-row {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: start;
        margin-bottom: 12px;
    }
    .waveform-header-row h4 { margin: 0; color: var(--text-main); }
    .panel-label { margin: 0 0 4px; color: var(--accent); font-size: 0.8rem; text-transform: uppercase; }
    .waveform-metrics { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
    .metric-pill {
        padding: 6px 10px;
        border-radius: 999px;
        background: #f8fafc;
        color: var(--text-main);
        font-size: 0.8rem;
        border: 1px solid var(--panel-border);
    }
    .metric-pill strong { color: var(--text-main); margin-left: 4px; }
    .canvas-shell { height: 280px; position: relative; }
    .canvas-shell canvas { width: 100% !important; height: 100% !important; display: block; }

    .vitals-strip { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 14px; }
    .vital-stat { padding: 16px; text-align: center; }
    .vital-stat small { display: block; color: var(--text-muted); font-weight: 700; margin-bottom: 8px; }
    .value-group { display: flex; align-items: baseline; justify-content: center; gap: 6px; }
    .vital-stat .val { font-size: 1.8rem; font-weight: 800; color: #0f172a; }
    .vital-stat .unit { font-size: 0.85rem; color: var(--text-muted); }
    .prediction-card .card-header,
    .alerts-card .card-header { background: transparent; border-bottom: 1px solid var(--panel-border); }
    .risk-meter { margin-bottom: 20px; }
    .meter-bg { height: 15px; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
    .meter-fill { height: 100%; background: linear-gradient(90deg, #22c55e, #f59e0b, #ef4444); border-radius: 999px; }
    .meter-labels { display: flex; justify-content: space-between; margin-top: 6px; font-size: 0.75rem; color: var(--text-muted); }
    .alert-item-mini { display: flex; gap: 15px; padding: 10px 0; border-bottom: 1px solid var(--panel-border); font-size: 0.9rem; }
    .alert-item-mini.warning { color: #b45309; }
    .alert-item-mini.normal { color: var(--text-main); }

    @media (max-width: 991px) {
        .hospital-monitor-area { grid-template-columns: 1fr; }
        .monitor-sidebar { border-right: 0; border-bottom: 1px solid var(--panel-border); }
        .monitor-summary { align-items: start; flex-direction: column; }
        .monitor-summary-tags { justify-content: flex-start; }
    }

    @media (max-width: 640px) {
        .doctor-monitor-container { padding: 14px; }
        .waveform-header-row { flex-direction: column; }
        .canvas-shell { height: 240px; }
        .vitals-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
</style>

<script>
    const patientId = @json(optional($currentPatient)->id ?? optional($patient)->id ?? null);
    const initialVitals = {
        heart_rate: @json((int) optional($currentVitals)->heart_rate ?: 72),
        oxygen_level: @json(optional($currentVitals)->oxygen_level ?? 98),
        temperature: @json(optional($currentVitals)->temperature ?? 37.0),
        respiratory_rate: @json(optional($currentVitals)->respiratory_rate ?? 18),
        blood_pressure: @json(optional($currentVitals)->blood_pressure ?? '—'),
    };
    const initialEeg = @json($initialEeg);
    const initialEmg = @json($initialEmg);
    const initialRisk = @json($analysisRisk ?? 15);
    const initialAlertLevel = @json($analysisLabel ?? 'stable');
    const patientDevices = @json($patientDevices);

    const monitorState = {
        heartRate: initialVitals.heart_rate,
        oxygenLevel: initialVitals.oxygen_level,
        temperature: Number(initialVitals.temperature),
        respiratoryRate: initialVitals.respiratory_rate,
        bloodPressure: initialVitals.blood_pressure,
        riskScore: initialRisk,
        alertLevel: String(initialAlertLevel),
        eegChannels: { ...initialEeg },
        emgTension: normalizeNumber(initialEmg.tension, 42),
        emgNerveSignals: normalizeNumber(initialEmg.nerve_signals, 28),
        emgMuscleActivity: normalizeNumber(initialEmg.muscle_activity, 36),
        devices: patientDevices,
    };

    const chartColors = ['#38bdf8', '#f472b6', '#facc15', '#34d399'];
    let ecgChart = null;
    let eegChart = null;
    let emgChart = null;

    function normalizeNumber(value, fallback) {
        if (value === null || value === undefined || value === '') return fallback;
        const parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : fallback;
    }

    function formatAlertLevel(level) {
        const normalized = String(level || 'stable').toLowerCase();
        if (normalized === 'emergency') return 'طارئ';
        if (normalized === 'warning') return 'تحذير';
        return 'مستقر';
    }

    function applyBadgeState(level) {
        const normalized = String(level || 'stable').toLowerCase();
        const connEl = document.getElementById('patient-connection-status');
        const badge = document.getElementById('risk-flavor-badge');
        const statusBadge = document.getElementById('ecg-status-badge');

        if (badge) {
            badge.textContent = formatAlertLevel(normalized);
            badge.className = 'summary-tag';
            if (normalized === 'emergency') badge.classList.add('badge-offline');
            else if (normalized === 'warning') badge.classList.add('badge-warning');
            else badge.classList.add('badge-online');
        }

        if (statusBadge) {
            statusBadge.textContent = normalized === 'emergency' ? 'طارئ' : normalized === 'warning' ? 'تحذير' : 'مستقرة';
        }

        if (connEl) {
            connEl.className = 'badge-online';
            if (normalized === 'warning') connEl.className = 'badge-warning';
            if (normalized === 'emergency') connEl.className = 'badge-offline';
        }
    }

    function generateECGSeries(heartRate) {
        const bpm = Math.max(48, Math.min(180, heartRate || 72));
        const samples = 80;
        const baseStep = (Math.PI * 2) / Math.max(12, Math.round(240 / bpm));
        return Array.from({ length: samples }, (_, index) => {
            const phase = index * baseStep;
            const qrs = Math.sin(phase * 3.2) * 8.5;
            const pWave = Math.sin(phase * 1.1) * 2.5;
            const baseline = Math.sin(phase * 0.55) * 1.5;
            const noise = (Math.random() - 0.5) * 1.2;
            return baseline + pWave + qrs + noise;
        });
    }

    function generateEEGSeries(channels) {
        const labels = Object.keys(channels);
        const sampleCount = 60;
        return labels.map((channel, index) => ({
            label: channel,
            data: Array.from({ length: sampleCount }, (_, point) => {
                const amplitude = normalizeNumber(channels[channel], 28);
                const wave = Math.sin(point * 0.28 + index * 0.7) * 10 + Math.cos(point * 0.15 + index * 0.35) * 6;
                return wave * (0.45 + amplitude / 120);
            }),
            borderColor: chartColors[index % chartColors.length],
            borderWidth: 1.6,
            tension: 0.35,
            pointRadius: 0,
            fill: false,
        }));
    }

    function generateEMGSeries(tension, nerveSignals) {
        const muscleLoad = normalizeNumber(tension, 42);
        const nerveLoad = normalizeNumber(nerveSignals, 28);
        const sampleCount = 80;

        return Array.from({ length: sampleCount }, (_, point) => {
            const muscleWave = Math.sin(point * 0.38) * (8 + muscleLoad / 8);
            const nerveWave = Math.cos(point * 0.19) * (5 + nerveLoad / 10);
            const twitch = Math.sin(point * 1.2) * (1.2 + muscleLoad / 40);
            const noise = (Math.random() - 0.5) * 1.5;
            return muscleWave + nerveWave + twitch + noise;
        });
    }

    function createECGChart() {
        const ctx = document.getElementById('ecg-chart');
        if (!ctx) return;
        ecgChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: Array.from({ length: 80 }, (_, i) => i),
                datasets: [{
                    label: 'ECG',
                    data: generateECGSeries(monitorState.heartRate),
                    borderColor: '#22c55e',
                    borderWidth: 2.2,
                    tension: 0.3,
                    pointRadius: 0,
                    fill: false,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                scales: {
                    x: { display: false },
                    y: {
                        display: true,
                        ticks: { color: '#cbd5e1' },
                        grid: { color: 'rgba(56, 189, 248, 0.12)' },
                        suggestedMin: -18,
                        suggestedMax: 18,
                    },
                },
                plugins: { legend: { display: false } },
            },
        });
    }

    function createEEGChart() {
        const ctx = document.getElementById('eeg-chart');
        if (!ctx) return;
        eegChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: Array.from({ length: 60 }, (_, i) => i),
                datasets: generateEEGSeries(monitorState.eegChannels),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                scales: {
                    x: { display: false },
                    y: {
                        display: true,
                        ticks: { color: '#cbd5e1' },
                        grid: { color: 'rgba(56, 189, 248, 0.12)' },
                        suggestedMin: -25,
                        suggestedMax: 25,
                    },
                },
                plugins: { legend: { display: false } },
            },
        });
    }

    function createEMGChart() {
        const ctx = document.getElementById('emg-chart');
        if (!ctx) return;
        emgChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: Array.from({ length: 80 }, (_, i) => i),
                datasets: [{
                    label: 'EMG',
                    data: generateEMGSeries(monitorState.emgTension, monitorState.emgNerveSignals),
                    borderColor: '#f472b6',
                    borderWidth: 2.1,
                    tension: 0.35,
                    pointRadius: 0,
                    fill: false,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                scales: {
                    x: { display: false },
                    y: {
                        display: true,
                        ticks: { color: '#cbd5e1' },
                        grid: { color: 'rgba(244, 114, 182, 0.15)' },
                        suggestedMin: -30,
                        suggestedMax: 30,
                    },
                },
                plugins: { legend: { display: false } },
            },
        });
    }

    function updateVitalsUI() {
        document.getElementById('hr-value').textContent = Math.round(monitorState.heartRate);
        document.getElementById('spo2-value').textContent = Math.round(monitorState.oxygenLevel);
        document.getElementById('temp-value').textContent = monitorState.temperature.toFixed(1);
        document.getElementById('resp-value').textContent = String(monitorState.respiratoryRate);
        document.getElementById('bp-value').textContent = monitorState.bloodPressure || '—';
        document.getElementById('emg-value').textContent = Math.round(monitorState.emgTension);
        document.getElementById('nerve-value').textContent = Math.round(monitorState.emgNerveSignals);
        document.getElementById('risk-meter-fill').style.width = `${Math.min(100, Math.max(0, monitorState.riskScore))}%`;
        document.getElementById('risk-percent').textContent = `${Math.round(monitorState.riskScore)}%`;
        document.getElementById('risk-label').textContent = formatAlertLevel(monitorState.alertLevel);
        applyBadgeState(monitorState.alertLevel);

        const emgStatusText = document.getElementById('emg-status-text');
        const emgAlertPill = document.getElementById('emg-alert-pill');
        if (emgStatusText) {
            emgStatusText.textContent = monitorState.emgTension > 70 ? 'تنبيه' : monitorState.emgTension > 40 ? 'مراقبة' : 'نشط';
        }
        if (emgAlertPill) {
            emgAlertPill.textContent = monitorState.emgTension > 70 ? 'عالي' : monitorState.emgTension > 40 ? 'متوسط' : 'مستقر';
        }
    }

    function updateDeviceStatusUI() {
        document.querySelectorAll('.device-item[data-device-name]').forEach((item) => {
            const name = item.dataset.deviceName;
            const match = monitorState.devices.find((device) => device.name === name);
            item.classList.remove('connected', 'warning');
            if (!match) return;
            if (match.status === 'warning') item.classList.add('warning');
            else if (match.status === 'connected') item.classList.add('connected');
        });
    }

    function updateChartData() {
        if (ecgChart) {
            ecgChart.data.datasets[0].data = generateECGSeries(monitorState.heartRate);
            ecgChart.update('none');
        }
        if (eegChart) {
            eegChart.data.datasets = generateEEGSeries(monitorState.eegChannels);
            eegChart.update('none');
        }
        if (emgChart) {
            emgChart.data.datasets[0].data = generateEMGSeries(monitorState.emgTension, monitorState.emgNerveSignals);
            emgChart.update('none');
        }
    }

    function resolveEmgPayload(source) {
        if (!source || typeof source !== 'object') {
            return null;
        }

        const direct = source.emg ?? source.emg_data ?? source.device_data?.emg ?? source.device_data ?? null;
        if (direct && typeof direct === 'object' && !Array.isArray(direct)) {
            return direct;
        }

        if (source.vital_sign && typeof source.vital_sign === 'object' && source.vital_sign.emg_signal !== undefined) {
            return { tension: source.vital_sign.emg_signal };
        }

        if (typeof source.emg_signal === 'number') {
            return { tension: source.emg_signal };
        }

        return null;
    }

    function appendAlert(message, variant = 'warning') {
        const alertList = document.querySelector('.alert-list-mini');
        if (!alertList) return;
        const item = document.createElement('div');
        item.className = `alert-item-mini ${variant}`;
        item.innerHTML = `
            <span class="time">${new Date().toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' })}</span>
            <span class="msg">${message}</span>
        `;
        alertList.prepend(item);
        while (alertList.children.length > 5) alertList.removeChild(alertList.lastChild);
    }

    function updateDoctorPatientUI(event) {
        const source = event?.data ?? event ?? {};
        const vital = source.vital_sign ?? source.vitalSign ?? {};
        const analysis = source.analysis ?? {};

        if (vital.heart_rate !== undefined) monitorState.heartRate = normalizeNumber(vital.heart_rate, monitorState.heartRate);
        if (vital.oxygen_level !== undefined) monitorState.oxygenLevel = normalizeNumber(vital.oxygen_level, monitorState.oxygenLevel);
        if (vital.temperature !== undefined) monitorState.temperature = normalizeNumber(vital.temperature, monitorState.temperature);
        if (vital.respiratory_rate !== undefined) monitorState.respiratoryRate = normalizeNumber(vital.respiratory_rate, monitorState.respiratoryRate);
        if (vital.blood_pressure !== undefined) monitorState.bloodPressure = vital.blood_pressure;
        if (analysis.risk_score !== undefined) monitorState.riskScore = Math.min(100, Math.max(0, Number(analysis.risk_score) * 100));
        if (analysis.alert_level) monitorState.alertLevel = analysis.alert_level;

        const emgPayload = resolveEmgPayload(source);
        if (emgPayload) {
            monitorState.emgTension = normalizeNumber(emgPayload.tension ?? emgPayload.muscle_tension, monitorState.emgTension);
            monitorState.emgNerveSignals = normalizeNumber(emgPayload.nerve_signals ?? emgPayload.nerve, monitorState.emgNerveSignals);
            monitorState.emgMuscleActivity = normalizeNumber(emgPayload.muscle_activity ?? emgPayload.activity, monitorState.emgMuscleActivity);
        }

        const eegPayload = source.eeg_data ?? source.eegData ?? source.eeg_wave ?? null;
        if (eegPayload && typeof eegPayload === 'object') {
            const nextChannels = { ...monitorState.eegChannels };
            Object.keys(eegPayload).forEach((channel) => {
                nextChannels[channel] = normalizeNumber(eegPayload[channel], nextChannels[channel] || 28);
            });
            monitorState.eegChannels = nextChannels;
        }

        if (Array.isArray(source.devices) && source.devices.length) {
            monitorState.devices = source.devices;
        }

        updateVitalsUI();
        updateDeviceStatusUI();
        updateChartData();
        appendAlert(`تحديث طبي مباشر: معدل ضربات القلب ${Math.round(monitorState.heartRate)} BPM`, monitorState.alertLevel === 'emergency' ? 'warning' : 'normal');

        const eegText = document.getElementById('eeg-status-text');
        if (eegText) eegText.textContent = monitorState.alertLevel === 'emergency' ? 'تنبيه' : 'نشط';
    }

    function updateClock() {
        const liveTime = document.getElementById('live-time-tag');
        if (liveTime) {
            liveTime.textContent = new Date().toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }
    }

    function initRealtime() {
        if (!patientId || !window.Echo) {
            if (!window.Echo) setTimeout(initRealtime, 1500);
            return;
        }

        const channel = window.Echo.private(`doctor.patient.${patientId}`);
        channel.listen('MedicalDataUpdated', updateDoctorPatientUI);
        channel.listen('VitalSignUpdated', updateDoctorPatientUI);
        channel.listen('EEGDataUpdated', updateDoctorPatientUI);

        const sock = window.Echo.connector && (window.Echo.connector.socket || window.Echo.connector.reverb || window.Echo.connector.connection);
        if (sock && sock.on) {
            sock.on('connect', () => {
                const connEl = document.getElementById('patient-connection-status');
                if (connEl) {
                    connEl.textContent = 'متصل';
                    connEl.className = 'badge-online';
                }
            });
            sock.on('disconnect', () => {
                const connEl = document.getElementById('patient-connection-status');
                if (connEl) {
                    connEl.textContent = 'غير متصل';
                    connEl.className = 'badge-offline';
                }
            });
        }
    }

    function bootMonitor() {
        updateVitalsUI();
        updateDeviceStatusUI();
        createECGChart();
        createEEGChart();
        createEMGChart();
        updateChartData();
        updateClock();
        setInterval(updateClock, 1000);
        setInterval(updateChartData, 180);
        initRealtime();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootMonitor);
    } else {
        bootMonitor();
    }
</script>
@endsection