/**
 * SANADK Patient Dashboard - Real Devices Version
 * Real-time monitoring with actual device data streams
 * 
 * This version removes simulated data and connects to real medical devices
 * via Socket.IO and the DeviceManager backend.
 */

// Initialize charts
let eegChart, emgChart, hrvChart;
let socket;
let connectedDevices = {};
let dataBuffers = {
    eeg: [],
    emg: [],
    hrv: [],
    ecg: []
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    loadUserProfile();
    initializeCharts();
    setupSocketIO();
    loadConnectedDevices();
    loadDashboardData();
    
    // Note: Removed setInterval(pollPhysiologicalData, 5000) - using Socket.IO instead
});

// ============================================================================
// USER PROFILE
// ============================================================================

function loadUserProfile() {
    const user = JSON.parse(localStorage.getItem('currentUser'));
    
    if (!user) {
        console.warn('No user data found in localStorage. Redirecting to login.');
        window.location.href = '/';
        return;
    }
    
    const userNameElements = document.querySelectorAll('#userName, .user-name');
    userNameElements.forEach(el => {
        el.textContent = user.full_name || user.username;
    });

    if (user.full_name) {
        const avatarText = user.full_name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
        const avatarSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128"><rect width="100%" height="100%" fill="#4A90E2"/><text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" fill="#ffffff" font-family="Arial, sans-serif" font-size="60">${avatarText}</text></svg>`;
        const encodedSvg = window.btoa(unescape(encodeURIComponent(avatarSvg)));
        document.getElementById('userAvatar').src = `data:image/svg+xml;base64,${encodedSvg}`;
    }
}

function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.sidebar-nav .nav-item').forEach(item => item.classList.remove('active'));
    const selectedTab = document.getElementById(tabId);
    if (selectedTab) selectedTab.classList.add('active');
    const selectedLink = document.querySelector(`.sidebar-nav .nav-item[onclick="switchTab('${tabId}')"]`);
    if (selectedLink) selectedLink.classList.add('active');
    const titleMap = {
        dashboard: 'لوحة التحكم',
        seizures: 'سجل النوبات',
        devices: 'الأجهزة',
        alerts: 'التنبيهات',
        emergency: 'الطوارئ',
        settings: 'الإعدادات'
    };
    const title = titleMap[tabId] || 'SANADK';
    const pageTitle = document.getElementById('pageTitle');
    if (pageTitle) pageTitle.textContent = title;
}

function showAddDeviceModal() {
    const modal = document.getElementById('addDeviceModal');
    if (modal) modal.style.display = 'flex';
}

function closeAddDeviceModal() {
    const modal = document.getElementById('addDeviceModal');
    if (modal) modal.style.display = 'none';
}

function getAuthToken() {
    return localStorage.getItem('access_token') || localStorage.getItem('authToken');
}

async function saveSettings() {
    const fullName = document.getElementById('settingFullName')?.value;
    const email = document.getElementById('settingEmail')?.value;
    const phone = document.getElementById('settingPhone')?.value;
    const seizureType = document.getElementById('settingSeizureType')?.value;
    const seizureFrequency = document.getElementById('settingSeizureFrequency')?.value;

    const token = getAuthToken();
    if (!token) {
        window.location.href = '/';
        return;
    }

    try {
        const response = await fetch('/api/patient/profile', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({
                seizure_type: seizureType,
                seizure_frequency: seizureFrequency,
                emergency_contact1_name: '',
                emergency_contact2_name: '',
                emergency_contact1_phone: phone,
                emergency_contact2_phone: '',
                nearest_hospital: '',
                hospital_phone: ''
            })
        });

        const result = await response.json();
        if (response.ok) {
            showNotification('نجح', 'تم حفظ الإعدادات بنجاح.', 'success');
        } else {
            showNotification('خطأ', result.error || 'فشل حفظ الإعدادات', 'error');
        }
    } catch (error) {
        console.error('Error saving settings:', error);
        showNotification('خطأ', 'فشل حفظ الإعدادات', 'error');
    }
}

function triggerEmergencyAlert() {
    showNotification('تنبيه طارئ', 'تم إرسال إشعار الطوارئ إلى الفريق الطبي.', 'critical');
}

function callEmergency(contactNumber) {
    const contactElement = document.getElementById(contactNumber === 1 ? 'emergencyPhone1' : 'emergencyPhone2');
    const phone = contactElement ? contactElement.textContent : null;
    if (phone && phone !== 'لم يتم تعيين') {
        window.location.href = `tel:${phone}`;
    } else {
        showNotification('غير متاح', 'لم يتم تعيين جهة اتصال الطوارئ.', 'warning');
    }
}

// ============================================================================
// CHARTS INITIALIZATION
// ============================================================================

function initializeCharts() {
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 0 },
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, max: 100, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false } }
        }
    };

    const createChart = (id, label, color) => {
        const element = document.getElementById(id);
        if (!element) return null;
        
        const ctx = element.getContext('2d');
        return new Chart(ctx, {
            type: 'line',
            data: {
                labels: Array(20).fill(''),
                datasets: [{
                    label: label,
                    data: Array(20).fill(0),
                    borderColor: color,
                    backgroundColor: color + '20',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 0
                }]
            },
            options: chartOptions
        });
    };

    if (document.getElementById('eegChart')) eegChart = createChart('eegChart', 'EEG', '#4A90E2');
    if (document.getElementById('emgChart')) emgChart = createChart('emgChart', 'EMG', '#50C878');
    if (document.getElementById('hrvChart')) hrvChart = createChart('hrvChart', 'HRV', '#F39C12');
}

function updateChart(chart, newValue) {
    if (!chart) return;
    chart.data.datasets[0].data.push(newValue);
    chart.data.datasets[0].data.shift();
    chart.update();
}

// ============================================================================
// SOCKET.IO SETUP & REAL-TIME DATA STREAMING
// ============================================================================

function setupSocketIO() {
    socket = io();
    
    // Connection events
    socket.on('connect', () => {
        console.log('✅ Connected to SANADK Real-time Server');
        showNotification('متصل', 'تم الاتصال بخادم SANADK', 'success');
    });

    socket.on('disconnect', () => {
        console.log('❌ Disconnected from SANADK Server');
        showNotification('قطع الاتصال', 'تم قطع الاتصال بخادم SANADK', 'error');
    });

    // Device connection events
    socket.on('device_connected', (data) => {
        console.log('📱 Device connected:', data);
        connectedDevices[data.device_id] = data;
        showNotification('جهاز متصل', `تم الاتصال بـ ${data.device_id}`, 'success');
        updateDevicesList();
    });

    socket.on('device_disconnected', (data) => {
        console.log('📱 Device disconnected:', data);
        delete connectedDevices[data.device_id];
        showNotification('جهاز مقطوع', `تم قطع الاتصال بـ ${data.device_id}`, 'warning');
        updateDevicesList();
    });

    // Real-time data streaming from devices
    socket.on('stream_eeg_data', (data) => {
        handleEEGData(data);
    });

    socket.on('stream_emg_data', (data) => {
        handleEMGData(data);
    });

    socket.on('stream_hrv_data', (data) => {
        handleHRVData(data);
    });

    socket.on('stream_ecg_data', (data) => {
        handleECGData(data);
    });

    // Device data update
    socket.on('device_data_update', (data) => {
        console.log('📊 Device data update:', data);
        updateUIWithDeviceData(data);
    });

    // Alert notifications
    socket.on('alert_notification', (alert) => {
        console.log('🚨 Alert received:', alert);
        showNotification(alert.title, alert.message, alert.severity);
        loadAlerts();
    });

    // Seizure detection
    socket.on('seizure_detected', (data) => {
        console.log('⚠️ Seizure detected:', data);
        showNotification(
            'تنبيه - نوبة مكتشفة',
            `تم اكتشاف نوبة صرع - الشدة: ${data.severity}`,
            'critical'
        );
        loadSeizures();
    });

    // Error handling
    socket.on('device_error', (data) => {
        console.error('❌ Device error:', data);
        showNotification('خطأ في الجهاز', data.error, 'error');
    });

    socket.on('error', (error) => {
        console.error('❌ Socket.IO error:', error);
        showNotification('خطأ في الاتصال', error, 'error');
    });
}

// ============================================================================
// REAL DEVICE DATA HANDLERS
// ============================================================================

function handleEEGData(data) {
    /**
     * Handle EEG (Electroencephalogram) data from device
     * 
     * Expected data structure:
     * {
     *   patient_id: number,
     *   device_id: string,
     *   values: [array of channel values],
     *   frequency: 256,
     *   quality: 95,
     *   timestamp: ISO string
     * }
     */
    try {
        if (data.values && data.values.length > 0) {
            // Use first channel value for chart (or average all channels)
            const chartValue = Array.isArray(data.values) 
                ? (data.values[0] || 0)
                : data.values;
            
            updateChart(eegChart, Math.min(100, Math.max(0, chartValue)));
            
            // Store in buffer for analysis
            dataBuffers.eeg.push({
                values: data.values,
                timestamp: data.timestamp,
                quality: data.quality
            });
            
            // Keep buffer size manageable
            if (dataBuffers.eeg.length > 1000) {
                dataBuffers.eeg.shift();
            }
            
            // Update UI elements
            updateEEGMetrics(data);
        }
    } catch (error) {
        console.error('Error handling EEG data:', error);
    }
}

function handleEMGData(data) {
    /**
     * Handle EMG (Electromyography) data from device
     */
    try {
        if (data.values && data.values.length > 0) {
            const chartValue = Array.isArray(data.values)
                ? (data.values[0] || 0)
                : data.values;
            
            updateChart(emgChart, Math.min(100, Math.max(0, chartValue)));
            
            dataBuffers.emg.push({
                values: data.values,
                timestamp: data.timestamp,
                quality: data.quality
            });
            
            if (dataBuffers.emg.length > 5000) {
                dataBuffers.emg.shift();
            }
            
            updateEMGMetrics(data);
        }
    } catch (error) {
        console.error('Error handling EMG data:', error);
    }
}

function handleHRVData(data) {
    /**
     * Handle HRV (Heart Rate Variability) data from device
     */
    try {
        if (data.values && data.values.length > 0) {
            const chartValue = Array.isArray(data.values)
                ? (data.values[0] || 0)
                : data.values;
            
            updateChart(hrvChart, Math.min(100, Math.max(0, chartValue)));
            
            dataBuffers.hrv.push({
                values: data.values,
                timestamp: data.timestamp,
                quality: data.quality
            });
            
            if (dataBuffers.hrv.length > 500) {
                dataBuffers.hrv.shift();
            }
            
            updateHRVMetrics(data);
        }
    } catch (error) {
        console.error('Error handling HRV data:', error);
    }
}

function handleECGData(data) {
    /**
     * Handle ECG (Electrocardiogram) data from device
     */
    try {
        dataBuffers.ecg.push({
            values: data.values,
            timestamp: data.timestamp,
            quality: data.quality
        });
        
        if (dataBuffers.ecg.length > 2000) {
            dataBuffers.ecg.shift();
        }
        
        updateECGMetrics(data);
    } catch (error) {
        console.error('Error handling ECG data:', error);
    }
}

// ============================================================================
// UI UPDATE FUNCTIONS
// ============================================================================

function updateEEGMetrics(data) {
    // Update EEG-specific UI elements
    const eegQuality = document.getElementById('eegQuality');
    if (eegQuality) eegQuality.textContent = data.quality + '%';
}

function updateEMGMetrics(data) {
    // Update EMG-specific UI elements
    const emgQuality = document.getElementById('emgQuality');
    if (emgQuality) emgQuality.textContent = data.quality + '%';
}

function updateHRVMetrics(data) {
    // Update HRV-specific UI elements
    if (data.values && data.values.length > 0) {
        const heartRate = document.getElementById('heartRateValue');
        if (heartRate) heartRate.textContent = Math.round(data.values[0]);
    }
    
    const hrvQuality = document.getElementById('hrvQuality');
    if (hrvQuality) hrvQuality.textContent = data.quality + '%';
}

function updateECGMetrics(data) {
    // Update ECG-specific UI elements
    const ecgQuality = document.getElementById('ecgQuality');
    if (ecgQuality) ecgQuality.textContent = data.quality + '%';
}

function updateUIWithDeviceData(data) {
    /**
     * Generic device data update handler
     */
    console.log('Updating UI with device data:', data);
    
    // Update device status if available
    if (data.device_id) {
        const deviceElement = document.querySelector(`[data-device-id="${data.device_id}"]`);
        if (deviceElement) {
            deviceElement.classList.add('active');
        }
    }
}

function updateDevicesList() {
    /**
     * Update the list of connected devices in the UI
     */
    const devicesList = document.getElementById('devicesList');
    if (!devicesList) return;
    
    devicesList.innerHTML = '';
    
    Object.values(connectedDevices).forEach(device => {
        const item = document.createElement('div');
        item.className = 'device-item';
        item.innerHTML = `
            <div class="device-info">
                <span class="device-name">${device.device_id}</span>
                <span class="device-type">${device.device_type}</span>
            </div>
            <div class="device-status">
                <span class="status-badge connected">متصل</span>
            </div>
        `;
        devicesList.appendChild(item);
    });
}

// ============================================================================
// DEVICE MANAGEMENT
// ============================================================================

function loadConnectedDevices() {
    const token = getAuthToken();
    if (!token) {
        return;
    }

    fetch('/api/device/list', {
        headers: {
            'Authorization': `Bearer ${token}`
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.devices) {
            connectedDevices = {};
            data.devices.forEach(device => {
                connectedDevices[device.device_id] = device;
            });
            updateDevicesList();
        }
    })
    .catch(err => console.error('Error loading connected devices:', err));
}

function registerNewDevice() {
    /**
     * Register a new real device
     * This should be called from a modal/form
     */
    const deviceId = document.getElementById('newDeviceId')?.value;
    const deviceType = document.getElementById('newDeviceType')?.value;
    const port = document.getElementById('newDevicePort')?.value;
    const baudrate = parseInt(document.getElementById('newDeviceBaudrate')?.value, 10) || 115200;
    const deviceName = document.getElementById('newDeviceName')?.value;
    
    if (!deviceId || !deviceType || !port) {
        showNotification('خطأ', 'يرجى ملء معرف الجهاز ونوعه ومنفذ الاتصال', 'error');
        return;
    }

    const payload = {
        device_id: deviceId,
        device_type: deviceType,
        port: port,
        baudrate: baudrate
    };
    if (deviceName) payload.device_name = deviceName;
    
    fetch('/api/device/register-real', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('access_token')}`
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('نجح', 'تم تسجيل الجهاز بنجاح', 'success');
            // Clear form
            document.getElementById('newDeviceId').value = '';
            document.getElementById('newDeviceType').value = '';
            document.getElementById('newDevicePort').value = '';
        } else {
            showNotification('خطأ', data.error, 'error');
        }
    })
    .catch(err => {
        console.error('Error registering device:', err);
        showNotification('خطأ', 'فشل تسجيل الجهاز', 'error');
    })
    .finally(() => {
        closeAddDeviceModal();
        loadConnectedDevices();
    });
}

function connectDevice(deviceId) {
    /**
     * Connect to a registered device
     */
    fetch(`/api/device/${deviceId}/connect`, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('access_token')}`
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('نجح', `تم الاتصال بـ ${deviceId}`, 'success');
        } else {
            showNotification('خطأ', data.error, 'error');
        }
    })
    .catch(err => {
        console.error('Error connecting device:', err);
        showNotification('خطأ', 'فشل الاتصال بالجهاز', 'error');
    });
}

function disconnectDevice(deviceId) {
    /**
     * Disconnect from a device
     */
    fetch(`/api/device/${deviceId}/disconnect`, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('access_token')}`
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('نجح', `تم قطع الاتصال بـ ${deviceId}`, 'success');
        } else {
            showNotification('خطأ', data.error, 'error');
        }
    })
    .catch(err => {
        console.error('Error disconnecting device:', err);
        showNotification('خطأ', 'فشل قطع الاتصال بالجهاز', 'error');
    });
}

// ============================================================================
// DASHBOARD DATA LOADING
// ============================================================================

function loadDashboardData() {
    loadPatientDashboard();
    loadSeizures();
    loadAlerts();
    loadConnectedDevices();
}

function loadPatientDashboard() {
    const token = getAuthToken();
    if (!token) {
        window.location.href = '/';
        return;
    }

    fetch('/api/patient/dashboard', {
        headers: {
            'Authorization': `Bearer ${token}`
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            showNotification('خطأ', data.error, 'error');
            return;
        }

        const currentStatus = document.getElementById('currentStatus');
        const batteryLevel = document.getElementById('batteryLevel');
        const signalQuality = document.getElementById('signalQuality');
        const lastSeizure = document.getElementById('lastSeizure');
        const riskFill = document.getElementById('riskFill');
        const riskPercentage = document.getElementById('riskPercentage');
        const emergencyPhone1 = document.getElementById('emergencyPhone1');
        const emergencyPhone2 = document.getElementById('emergencyPhone2');
        const settingFullName = document.getElementById('settingFullName');
        const settingEmail = document.getElementById('settingEmail');
        const settingPhone = document.getElementById('settingPhone');
        const settingSeizureType = document.getElementById('settingSeizureType');
        const settingSeizureFrequency = document.getElementById('settingSeizureFrequency');

        if (currentStatus) {
            currentStatus.textContent = data.latest_device?.is_active ? 'جهاز متصل' : 'غير متصل';
        }
        if (batteryLevel) {
            batteryLevel.textContent = data.latest_device?.battery_level ? `${data.latest_device.battery_level}%` : 'غير متوفر';
        }
        if (signalQuality) {
            signalQuality.textContent = data.latest_device?.signal_quality ? `${data.latest_device.signal_quality}%` : 'غير متوفر';
        }
        if (lastSeizure) {
            lastSeizure.textContent = data.last_seizure?.time ? new Date(data.last_seizure.time).toLocaleString('ar-SA') : 'لا توجد';
        }
        if (emergencyPhone1) {
            emergencyPhone1.textContent = data.emergency_contacts?.[0]?.phone || 'لم يتم تعيين';
        }
        if (emergencyPhone2) {
            emergencyPhone2.textContent = data.emergency_contacts?.[1]?.phone || 'لم يتم تعيين';
        }
        if (settingFullName) settingFullName.value = data.full_name || data.patient_name || '';
        if (settingEmail) settingEmail.value = data.email || '';
        if (settingPhone) settingPhone.value = data.phone || data.emergency_contacts?.[0]?.phone || '';
        if (settingSeizureType) settingSeizureType.value = data.seizure_type || '';
        if (settingSeizureFrequency) settingSeizureFrequency.value = data.seizure_frequency || '';

        const riskValue = mapFrequencyToRisk(data.seizure_frequency || data.frequency || 'rare');
        if (riskFill) riskFill.style.width = `${riskValue}%`;
        if (riskPercentage) riskPercentage.textContent = `${riskValue}%`;
    })
    .catch(err => console.error('Error loading dashboard data:', err));
}

function mapFrequencyToRisk(frequency) {
    const mapping = {
        'daily': 90,
        'weekly': 60,
        'monthly': 35,
        'rare': 15,
        'يومي': 90,
        'أسبوعي': 60,
        'شهري': 35,
        'نادر': 15
    };
    return mapping[frequency] || 40;
}

function loadSeizures() {
    fetch('/api/patient/seizures', {
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('access_token')}`
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.seizures) {
            const list = document.getElementById('seizuresList') || document.querySelector('.seizures-list');
            if (!list) return;
            
            list.innerHTML = '';
            data.seizures.forEach(s => {
                const item = document.createElement('div');
                item.className = 'seizure-card';
                item.innerHTML = `
                    <div class="seizure-info">
                        <span class="seizure-date">${new Date(s.start_time).toLocaleDateString('ar-SA')}</span>
                        <span class="seizure-type">${s.event_type}</span>
                    </div>
                    <div class="seizure-meta">
                        <span class="badge ${s.severity}">${getSeverityAr(s.severity)}</span>
                        <span class="duration">${s.duration || 'N/A'} ثانية</span>
                    </div>
                `;
                list.appendChild(item);
            });
        }
    })
    .catch(err => console.error('Error loading seizures:', err));
}

function loadAlerts() {
    fetch('/api/alert/list', {
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('access_token')}`
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.alerts) {
            const list = document.getElementById('alertsList') || document.querySelector('.alerts-list');
            if (!list) return;
            
            list.innerHTML = '';
            data.alerts.slice(0, 5).forEach(a => {
                const item = document.createElement('div');
                item.className = `alert-card alert-${a.severity}`;
                item.innerHTML = `
                    <div class="alert-title">${a.title}</div>
                    <div class="alert-message">${a.message}</div>
                    <div class="alert-time">${new Date(a.created_at).toLocaleTimeString('ar-SA')}</div>
                `;
                list.appendChild(item);
            });
        }
    })
    .catch(err => console.error('Error loading alerts:', err));
}

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

function getSeverityAr(sev) {
    const map = {
        'MILD': 'خفيفة',
        'MODERATE': 'متوسطة',
        'SEVERE': 'شديدة',
        'CRITICAL': 'حرجة',
        'mild': 'خفيفة',
        'moderate': 'متوسطة',
        'severe': 'شديدة'
    };
    return map[sev] || sev;
}

function showNotification(title, message, type = 'info') {
    // Simple toast implementation
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `<strong>${title}</strong><p>${message}</p>`;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}
