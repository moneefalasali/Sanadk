@extends('layouts.app')

@section('content')
<div class="container-fluid medical-monitoring-dashboard">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="dashboard-title">
                <i class="fas fa-heartbeat"></i> Medical Monitoring Dashboard
            </h1>
        </div>
    </div>

    <!-- Device Status Section -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="device-status-card">
                <div class="device-icon polar">
                    <i class="fas fa-watch"></i>
                </div>
                <h5>Polar Device</h5>
                <div class="status-indicator" id="polar-status">
                    <span class="badge badge-secondary">Disconnected</span>
                </div>
                <small id="polar-battery">Battery: N/A</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="device-status-card">
                <div class="device-icon emotiv">
                    <i class="fas fa-brain"></i>
                </div>
                <h5>Emotiv EEG</h5>
                <div class="status-indicator" id="emotiv-status">
                    <span class="badge badge-secondary">Disconnected</span>
                </div>
                <small id="emotiv-quality">Quality: N/A</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="device-status-card">
                <div class="device-icon esp32">
                    <i class="fas fa-microchip"></i>
                </div>
                <h5>ESP32 Sensor</h5>
                <div class="status-indicator" id="esp32-status">
                    <span class="badge badge-secondary">Disconnected</span>
                </div>
                <small id="esp32-signal">Signal: N/A</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="device-control-card">
                <h5>Device Control</h5>
                <button class="btn btn-sm btn-primary w-100 mb-2" id="connect-devices">
                    <i class="fas fa-plug"></i> Connect Devices
                </button>
                <button class="btn btn-sm btn-danger w-100" id="disconnect-devices">
                    <i class="fas fa-unplug"></i> Disconnect
                </button>
            </div>
        </div>
    </div>

    <!-- Vital Signs Display -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card medical-card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-heartbeat"></i> ECG / Heart Rate
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="ecg-display" width="500" height="200"></canvas>
                    <div class="vital-signs-info mt-3">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Heart Rate</small>
                                <h4 id="heart-rate-value">-- BPM</h4>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">HRV</small>
                                <h4 id="hrv-value">-- ms</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card medical-card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-brain"></i> EEG Brain Signals
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="eeg-display" width="500" height="200"></canvas>
                    <div class="eeg-channels-info mt-3">
                        <div class="row" id="eeg-channels-list">
                            <!-- Populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vital Signs Grid -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="vital-sign-box">
                <div class="vital-icon oxygen">
                    <i class="fas fa-lungs"></i>
                </div>
                <div class="vital-info">
                    <small>Oxygen Level</small>
                    <h3 id="oxygen-value">-- %</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="vital-sign-box">
                <div class="vital-icon temp">
                    <i class="fas fa-thermometer-half"></i>
                </div>
                <div class="vital-info">
                    <small>Temperature</small>
                    <h3 id="temp-value">-- °C</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="vital-sign-box">
                <div class="vital-icon bp">
                    <i class="fas fa-tint"></i>
                </div>
                <div class="vital-info">
                    <small>Blood Pressure</small>
                    <h3 id="bp-value">-- / --</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="vital-sign-box alert-box" id="alert-box">
                <div class="vital-icon alert">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="vital-info">
                    <small>Alert Level</small>
                    <h3 id="alert-level">NORMAL</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Seizure Analysis -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card medical-card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line"></i> Seizure Risk Analysis
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="analysis-metric">
                                <h6>Prediction Score</h6>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar" id="prediction-score-bar" role="progressbar" 
                                         style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                        <span id="prediction-score-text">0%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="analysis-metric">
                                <h6>EEG Risk Level</h6>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-info" id="eeg-risk-bar" role="progressbar" 
                                         style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                        <span id="eeg-risk-text">0%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="analysis-metric">
                                <h6>Overall Risk</h6>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-danger" id="overall-risk-bar" role="progressbar" 
                                         style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                        <span id="overall-risk-text">0%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <h6>Recommendations:</h6>
                        <ul id="recommendations-list" class="list-group">
                            <li class="list-group-item">No active recommendations</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Real-time Alerts -->
    <div class="row">
        <div class="col-12">
            <div class="card medical-card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-bell"></i> Real-time Alerts
                    </h5>
                </div>
                <div class="card-body">
                    <div id="alerts-container" class="alerts-list">
                        <p class="text-muted">No active alerts</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles -->
<style>
    .medical-monitoring-dashboard {
        background-color: #f8f9fa;
        padding: 20px;
    }

    .dashboard-title {
        color: #2c3e50;
        font-weight: 600;
        margin-bottom: 30px;
    }

    .device-status-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-left: 4px solid #007bff;
    }

    .device-status-card .device-icon {
        font-size: 2.5rem;
        margin-bottom: 10px;
    }

    .device-status-card .device-icon.polar {
        color: #ff6b6b;
    }

    .device-status-card .device-icon.emotiv {
        color: #4ecdc4;
    }

    .device-status-card .device-icon.esp32 {
        color: #95e1d3;
    }

    .device-control-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-left: 4px solid #28a745;
    }

    .medical-card {
        border: none;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }

    .medical-card .card-header {
        border-radius: 8px 8px 0 0;
        border: none;
    }

    .vital-sign-box {
        background: white;
        border-radius: 8px;
        padding: 20px;
        display: flex;
        align-items: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-left: 4px solid #007bff;
    }

    .vital-sign-box .vital-icon {
        font-size: 2rem;
        margin-right: 15px;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background-color: #f0f0f0;
    }

    .vital-sign-box .vital-icon.oxygen {
        color: #3498db;
        background-color: #e3f2fd;
    }

    .vital-sign-box .vital-icon.temp {
        color: #e74c3c;
        background-color: #ffebee;
    }

    .vital-sign-box .vital-icon.bp {
        color: #9b59b6;
        background-color: #f3e5f5;
    }

    .vital-sign-box .vital-icon.alert {
        color: #f39c12;
        background-color: #fff3cd;
    }

    .vital-sign-box.alert-box {
        border-left-color: #f39c12;
    }

    .vital-info h3 {
        margin: 0;
        font-weight: 600;
        color: #2c3e50;
    }

    .vital-info small {
        color: #7f8c8d;
        display: block;
        margin-bottom: 5px;
    }

    .analysis-metric {
        margin-bottom: 15px;
    }

    .analysis-metric h6 {
        color: #2c3e50;
        margin-bottom: 10px;
        font-weight: 600;
    }

    .alerts-list {
        max-height: 300px;
        overflow-y: auto;
    }

    .alert-item {
        padding: 10px;
        margin-bottom: 10px;
        border-left: 4px solid #f39c12;
        background-color: #fffbea;
        border-radius: 4px;
    }

    .alert-item.critical {
        border-left-color: #e74c3c;
        background-color: #fadbd8;
    }

    #ecg-display,
    #eeg-display {
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        background-color: #0a0a0a;
    }

    .eeg-channels-info {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 10px;
    }

    .eeg-channel-item {
        text-align: center;
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 4px;
        border: 1px solid #e0e0e0;
    }

    .eeg-channel-item small {
        display: block;
        color: #7f8c8d;
        margin-bottom: 5px;
    }

    .eeg-channel-item strong {
        color: #00ff00;
        font-family: 'Courier New', monospace;
    }
</style>

<!-- Scripts -->
<script src="{{ asset('js/services/PolarBLEManager.js') }}"></script>
<script src="{{ asset('js/services/EmotivEEGManager.js') }}"></script>
<script src="{{ asset('js/services/MedicalWaveformVisualizer.js') }}"></script>

<script>
    // Initialize managers
    const polarManager = new PolarBLEManager();
    const emotivManager = new EmotivEEGManager();

    // Initialize visualizers
    const ecgVisualizer = new MedicalWaveformVisualizer('ecg-display', {
        waveColor: '#00ff00',
        backgroundColor: '#faf6f6',
        gridSize: 10,
        speed: 2,
    });

    const eegVisualizer = new MedicalWaveformVisualizer('eeg-display', {
        waveColor: '#00ff00',
        backgroundColor: '#0a0a0a',
    });

    // Start visualizers
    ecgVisualizer.start();
    eegVisualizer.start();

    // Connect Devices Button
    document.getElementById('connect-devices').addEventListener('click', async () => {
        try {
            // Connect Polar
            const scanResult = await polarManager.scanForDevices();
            if (scanResult.success) {
                await polarManager.connect();
                updatePolarStatus(true);
            }

            // Connect Emotiv
            await emotivManager.initialize();
            await emotivManager.connect();
            updateEmotivStatus(true);
        } catch (error) {
            console.error('Connection error:', error);
            alert('Error connecting devices: ' + error.message);
        }
    });

    // Disconnect Devices Button
    document.getElementById('disconnect-devices').addEventListener('click', async () => {
        await polarManager.disconnect();
        await emotivManager.disconnect();
        updatePolarStatus(false);
        updateEmotivStatus(false);
    });

    // Polar Event Listeners
    polarManager.addEventListener('heartRateUpdate', (data) => {
        document.getElementById('heart-rate-value').textContent = data.heartRate + ' BPM';
        ecgVisualizer.addDataPoint(data.heartRate);
        ecgVisualizer.drawECGWaveform(data.heartRate);
    });

    polarManager.addEventListener('batteryUpdate', (data) => {
        document.getElementById('polar-battery').textContent = 'Battery: ' + data.batteryLevel + '%';
    });

    // Emotiv Event Listeners
    emotivManager.addEventListener('eegUpdate', (data) => {
        updateEEGDisplay(data.channels);
        const analysis = emotivManager.analyzeForSeizures();
        updateSeizureAnalysis(analysis);
    });

    // Listen for real-time updates via Echo
    if (window.Echo) {
        const userId = document.querySelector('meta[name="user-id"]')?.content;
        if (userId) {
            window.Echo.private(`user.${userId}`)
                .listen('VitalSignUpdated', (data) => {
                    updateVitalSigns(data.vital_sign);
                    updateAlertLevel(data.analysis);
                })
                .listen('EEGDataUpdated', (data) => {
                    updateEEGDisplay(data.eeg_data);
                });
        }
    }

    // Update Functions
    function updatePolarStatus(connected) {
        const badge = document.querySelector('#polar-status .badge');
        badge.textContent = connected ? 'Connected' : 'Disconnected';
        badge.className = connected ? 'badge badge-success' : 'badge badge-secondary';
    }

    function updateEmotivStatus(connected) {
        const badge = document.querySelector('#emotiv-status .badge');
        badge.textContent = connected ? 'Connected' : 'Disconnected';
        badge.className = connected ? 'badge badge-success' : 'badge badge-secondary';
    }

    function updateVitalSigns(vitalSign) {
        if (vitalSign.oxygen_level) {
            document.getElementById('oxygen-value').textContent = vitalSign.oxygen_level + ' %';
        }
        if (vitalSign.temperature) {
            document.getElementById('temp-value').textContent = vitalSign.temperature + ' °C';
        }
        if (vitalSign.blood_pressure_systolic) {
            document.getElementById('bp-value').textContent = 
                vitalSign.blood_pressure_systolic + ' / ' + vitalSign.blood_pressure_diastolic;
        }
    }

    function updateEEGDisplay(channels) {
        const channelsList = document.getElementById('eeg-channels-list');
        channelsList.innerHTML = Object.entries(channels).map(([channel, value]) => `
            <div class="col-6 col-md-4">
                <div class="eeg-channel-item">
                    <small>${channel}</small>
                    <strong>${value.toFixed(2)}</strong>
                </div>
            </div>
        `).join('');
    }

    function updateSeizureAnalysis(analysis) {
        if (analysis) {
            const riskPercent = Math.round(analysis.confidence * 100);
            document.getElementById('eeg-risk-bar').style.width = riskPercent + '%';
            document.getElementById('eeg-risk-text').textContent = riskPercent + '%';
        }
    }

    function updateAlertLevel(analysis) {
        const alertBox = document.getElementById('alert-box');
        const alertLevel = document.getElementById('alert-level');

        alertLevel.textContent = analysis.alert_level.toUpperCase();

        alertBox.className = 'vital-sign-box alert-box';
        if (analysis.alert_level === 'emergency') {
            alertBox.style.borderLeftColor = '#e74c3c';
            alertBox.style.backgroundColor = '#fadbd8';
        } else if (analysis.alert_level === 'warning') {
            alertBox.style.borderLeftColor = '#f39c12';
            alertBox.style.backgroundColor = '#fff3cd';
        } else {
            alertBox.style.borderLeftColor = '#27ae60';
            alertBox.style.backgroundColor = '#d5f4e6';
        }
    }
</script>
@endsection
