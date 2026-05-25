# دليل تكامل الأجهزة الطبية - SANADK Medical Device Integration Guide

## نظرة عامة

هذا الدليل يوضح كيفية دمج الأجهزة الطبية الحقيقية مع نظام SANADK للكشف والتنبؤ بنوبات الصرع.

## الأجهزة المدعومة

### 1. Polar Heart Rate Monitors (BLE)
- **الاتصال**: Bluetooth Low Energy (BLE)
- **البيانات**: معدل ضربات القلب، فترات RR، HRV
- **المنصات**: Android, iOS, Web (Web Bluetooth API)

### 2. Emotiv EEG Headsets (Cortex API)
- **الاتصال**: WebSocket عبر Cortex API
- **البيانات**: 10 قنوات EEG، كشف الأنماط، تحليل الإشارات
- **المنصات**: Windows, Mac, Linux, Web

### 3. ESP32 IoT Sensors
- **الاتصال**: WiFi (HTTP/WebSocket)
- **البيانات**: معدل ضربات القلب، الأكسجين، درجة الحرارة، ضغط الدم
- **المنصات**: جميع المنصات

---

## البنية المعمارية

```
Frontend (Capacitor)
    ↓
Device Managers (JavaScript)
    ├── PolarBLEManager.js
    ├── EmotivEEGManager.js
    └── ESP32Manager.js
    ↓
API Layer
    ├── /api/devices/polar/data
    ├── /api/devices/emotiv/data
    └── /api/devices/esp32/data
    ↓
Backend Services (Laravel)
    ├── MedicalDeviceService
    ├── PolarBLEService
    ├── EmotivEEGService
    └── SeizureDetector
    ↓
Database
    ├── vital_signs
    ├── eeg_signals
    ├── device_connections
    └── medical_reports
    ↓
WebSocket Broadcasting
    └── Real-time Dashboard
```

---

## التثبيت والإعداد

### 1. تثبيت المكتبات

```bash
# تم بالفعل تثبيتها
npm install @capacitor/core @capacitor/cli
npm install @capacitor/device @capacitor/geolocation
npm install apexcharts chart.js d3
npm install laravel-echo socket.io-client
```

### 2. تشغيل Migrations

```bash
php artisan migrate
```

هذا سينشئ الجداول التالية:
- `eeg_signals` - بيانات EEG
- `device_connections` - حالة الاتصالات
- `real_time_alerts` - التنبيهات الفورية
- `device_sessions` - جلسات الأجهزة
- `medical_reports` - التقارير الطبية

---

## 1. تكامل Polar BLE

### الخادم (Backend)

#### API Endpoint
```
POST /api/devices/polar/data
Authorization: Bearer {token}

Body:
{
    "heart_rate": 75,
    "rr_interval": 0.8,
    "hrv": 45.2,
    "device_id": "polar_123",
    "battery_level": 85,
    "signal_quality": 100
}

Response:
{
    "success": true,
    "analysis": {
        "seizure_detected": false,
        "prediction_score": 0.3,
        "alert_level": "normal"
    }
}
```

#### Service (PolarBLEService.php)
```php
$service = new PolarBLEService();

// Parse BLE data
$parsed = $service->parseHeartRateData($bleData);

// Calculate HRV
$hrv = $service->calculateHRV($rrIntervals);
```

### العميل (Frontend)

#### استخدام PolarBLEManager.js
```javascript
// Initialize
const polarManager = new PolarBLEManager();

// Scan for devices
const scanResult = await polarManager.scanForDevices();

// Connect
const connectResult = await polarManager.connect();

// Listen for updates
polarManager.addEventListener('heartRateUpdate', (data) => {
    console.log('Heart Rate:', data.heartRate);
    console.log('RR Intervals:', data.rrIntervals);
});

// Get current status
const status = polarManager.getStatus();
console.log('Current HR:', status.currentHeartRate);
console.log('HRV:', status.hrv);

// Disconnect
await polarManager.disconnect();
```

---

## 2. تكامل Emotiv EEG

### الخادم (Backend)

#### API Endpoint
```
POST /api/devices/emotiv/data
Authorization: Bearer {token}

Body:
{
    "AF3": 45.2,
    "AF4": 42.1,
    "F3": 38.5,
    "F4": 40.2,
    "FC5": 35.1,
    "FC6": 36.8,
    "P7": 32.4,
    "P8": 33.9,
    "O1": 28.5,
    "O2": 29.1,
    "session_id": "session_123"
}

Response:
{
    "success": true,
    "analysis": {
        "seizure_risk": "low",
        "risk_score": 0.2,
        "abnormal_channels": [],
        "recommendations": []
    }
}
```

#### Service (EmotivEEGService.php)
```php
$service = new EmotivEEGService();

// Initialize connection
$init = $service->initializeConnection();

// Authenticate
$auth = $service->authenticate();

// Create session
$session = $service->createSession($headsetId);

// Process EEG data
$analysis = $service->processRawEEGData($sessionId, $eegData);

// Detect seizure patterns
$patterns = $service->detectSeizurePatterns($channels, $history);

// End session
$service->endSession($sessionId);
```

### العميل (Frontend)

#### استخدام EmotivEEGManager.js
```javascript
// Initialize
const emotivManager = new EmotivEEGManager();
await emotivManager.initialize();

// Connect to Cortex API
await emotivManager.connect();

// Create session
await emotivManager.createSession('headset_123');

// Subscribe to EEG stream
await emotivManager.subscribeToEEG();

// Listen for EEG updates
emotivManager.addEventListener('eegUpdate', (data) => {
    console.log('EEG Channels:', data.channels);
    
    // Analyze for seizures
    const analysis = emotivManager.analyzeForSeizures();
    console.log('Seizure Risk:', analysis.riskLevel);
});

// Get status
const status = emotivManager.getStatus();
console.log('EEG Status:', status);

// Disconnect
await emotivManager.disconnect();
```

---

## 3. تكامل ESP32 IoT Sensors

### إعداد ESP32 (Arduino Code)

```cpp
#include <WiFi.h>
#include <WebSocketsClient.h>
#include <ArduinoJson.h>

// WiFi credentials
const char* ssid = "YOUR_SSID";
const char* password = "YOUR_PASSWORD";

// Server configuration
const char* server = "your-server.com";
const int port = 443;
const char* path = "/api/devices/esp32/data";

WebSocketsClient webSocket;

void setup() {
    Serial.begin(115200);
    
    // Connect to WiFi
    WiFi.begin(ssid, password);
    while (WiFi.status() != WL_CONNECTED) {
        delay(500);
        Serial.print(".");
    }
    Serial.println("WiFi connected");
    
    // Connect to WebSocket
    webSocket.beginSSL(server, port, path);
    webSocket.onEvent(webSocketEvent);
}

void loop() {
    webSocket.loop();
    
    // Read sensors
    int heartRate = readHeartRate();
    int oxygenLevel = readOxygen();
    float temperature = readTemperature();
    
    // Send data
    sendSensorData(heartRate, oxygenLevel, temperature);
    
    delay(1000); // Send every second
}

void sendSensorData(int hr, int o2, float temp) {
    StaticJsonDocument<200> doc;
    doc["heart_rate"] = hr;
    doc["oxygen_level"] = o2;
    doc["temperature"] = temp;
    doc["device_id"] = "esp32_001";
    doc["signal_quality"] = 100;
    
    String json;
    serializeJson(doc, json);
    webSocket.sendTXT(json);
}

void webSocketEvent(WStype_t type, uint8_t * payload, size_t length) {
    switch(type) {
        case WStype_CONNECTED:
            Serial.println("WebSocket connected");
            break;
        case WStype_TEXT:
            Serial.printf("Received: %s\n", payload);
            break;
        case WStype_DISCONNECTED:
            Serial.println("WebSocket disconnected");
            break;
    }
}
```

### API Endpoint
```
POST /api/devices/esp32/data
Authorization: Bearer {token}

Body:
{
    "heart_rate": 72,
    "oxygen_level": 98,
    "temperature": 36.5,
    "bp_systolic": 120,
    "bp_diastolic": 80,
    "device_id": "esp32_001",
    "signal_quality": 100
}
```

---

## عرض البيانات في الوقت الفعلي

### استخدام MedicalWaveformVisualizer.js

```html
<canvas id="ecg-display" width="800" height="300"></canvas>
<canvas id="eeg-display" width="800" height="400"></canvas>

<script>
// Initialize visualizers
const ecgVisualizer = new MedicalWaveformVisualizer('ecg-display', {
    waveColor: '#00ff00',
    backgroundColor: '#0a0a0a',
    gridSize: 10,
    speed: 2,
});

const eegVisualizer = new MedicalWaveformVisualizer('eeg-display', {
    waveColor: '#00ff00',
    backgroundColor: '#0a0a0a',
});

// Start animation
ecgVisualizer.start();
eegVisualizer.start();

// Update with real-time data
polarManager.addEventListener('heartRateUpdate', (data) => {
    ecgVisualizer.addDataPoint(data.heartRate);
});

emotivManager.addEventListener('eegUpdate', (data) => {
    eegVisualizer.drawEEGWaveform(data.channels);
});

// Export as image
const ecgImage = ecgVisualizer.exportAsImage();
</script>
```

---

## نظام التنبيهات الفورية

### إنشاء تنبيه
```php
// في MedicalDeviceService.php
if ($analysis['seizure_detected']) {
    // Create alert
    RealTimeAlert::create([
        'user_id' => $user->id,
        'alert_type' => 'seizure_detected',
        'severity' => 'critical',
        'message' => 'Seizure detected from vital signs',
        'vital_signs' => $vitalSign->toArray(),
    ]);
    
    // Broadcast to doctors
    broadcast(new SeizureDetected($user, $analysis));
    
    // Send notifications
    Notification::send($user->emergencyContacts, new SeizureNotification($user));
}
```

---

## تصدير التقارير الطبية

### إنشاء تقرير PDF
```php
use Barryvdh\DomPDF\Facade\Pdf;

$report = MedicalReport::create([
    'user_id' => $user->id,
    'doctor_id' => $doctor->id,
    'report_type' => 'session_report',
    'summary' => 'Session summary...',
    'vital_signs_data' => $vitalSigns->toArray(),
    'eeg_data' => $eegData,
    'seizure_events' => $seizures->toArray(),
]);

// Generate PDF
$pdf = Pdf::loadView('reports.medical-report', [
    'report' => $report,
    'user' => $user,
]);

$pdf->save(storage_path('reports/' . $report->id . '.pdf'));
```

---

## Capacitor Configuration

### capacitor.config.ts
```typescript
import { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.ghala_tech.sanadk',
  appName: 'SANADK',
  webDir: 'public',
  server: {
    androidScheme: 'https',
    iosScheme: 'https',
    allowNavigation: ['*'],
  },
  plugins: {
    SplashScreen: {
      launchShowDuration: 0,
    },
  },
};

export default config;
```

### بناء التطبيق

```bash
# Android
npx cap add android
npx cap build android

# iOS
npx cap add ios
npx cap build ios

# Web
npm run build
```

---

## معالجة الأخطاء والاتصالات

### Auto-Reconnect Logic
```javascript
// في PolarBLEManager.js
async autoReconnect() {
    if (this.reconnectAttempts < this.maxReconnectAttempts) {
        this.reconnectAttempts++;
        console.log(`Reconnecting... (${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
        
        setTimeout(async () => {
            try {
                await this.connect();
            } catch (error) {
                this.autoReconnect();
            }
        }, this.reconnectDelay);
    }
}
```

---

## الأمان والخصوصية

### التشفير
- استخدام HTTPS/WSS للاتصالات
- تشفير البيانات الحساسة في قاعدة البيانات
- استخدام JWT tokens للمصادقة

### الصلاحيات
- التحقق من الصلاحيات على جميع API endpoints
- السماح للمرضى برؤية بيانتهم فقط
- السماح للأطباء برؤية بيانات مرضاهم فقط

### HIPAA Compliance
- تسجيل جميع الوصولات
- الاحتفاظ بسجل تدقيق
- تشفير البيانات أثناء النقل والتخزين

---

## استكشاف الأخطاء

### Polar BLE Issues
```
❌ Device not found
✅ تأكد من تفعيل Bluetooth
✅ تأكد من أن الجهاز في نطاق الاتصال
✅ جرب إعادة تشغيل الجهاز

❌ Connection timeout
✅ تأكد من أن الجهاز مشحون
✅ جرب الاتصال من جهاز آخر
```

### Emotiv EEG Issues
```
❌ WebSocket connection failed
✅ تحقق من توفر الإنترنت
✅ تحقق من صحة بيانات المصادقة
✅ تأكد من أن الخادم يعمل

❌ No EEG data received
✅ تحقق من وضع الرأس
✅ تأكد من شحن البطارية
✅ جرب إعادة تشغيل الجهاز
```

---

## المراجع والموارد

- [Polar API Documentation](https://developer.polar.com)
- [Emotiv Cortex API](https://cortex-api.emotivcloud.com)
- [Web Bluetooth API](https://developer.mozilla.org/en-US/docs/Web/API/Web_Bluetooth_API)
- [Capacitor Documentation](https://capacitorjs.com)
- [Laravel Echo Documentation](https://laravel.com/docs/echo)

---

**آخر تحديث**: مايو 2026
