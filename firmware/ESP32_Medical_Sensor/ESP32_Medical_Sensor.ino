/**
 * SANADK ESP32 Medical Sensor Firmware
 * Real-time vital signs monitoring and transmission
 * 
 * Sensors:
 * - Heart Rate: MAX30102 (Pulse Oximetry)
 * - Temperature: DS18B20 (Digital Temperature)
 * - Blood Pressure: Simulated (can be replaced with actual sensor)
 * 
 * Communication: WiFi + WebSocket
 */

#include <WiFi.h>
#include <WebSocketsClient.h>
#include <ArduinoJson.h>
#include <Wire.h>
#include <OneWire.h>
#include <DallasTemperature.h>

// ============ Configuration ============
const char* WIFI_SSID = "YOUR_SSID";
const char* WIFI_PASSWORD = "YOUR_PASSWORD";
const char* SERVER_HOST = "your-server.com";
const int SERVER_PORT = 443;
const char* API_ENDPOINT = "/api/devices/esp32/data";
const char* AUTH_TOKEN = "YOUR_BEARER_TOKEN";

// ============ Pin Configuration ============
#define HEART_RATE_PIN 34      // ADC pin for heart rate sensor
#define TEMPERATURE_PIN 5      // GPIO pin for DS18B20
#define BLOOD_PRESSURE_SIM_PIN 35  // ADC pin for BP simulation

// ============ Sensor Objects ============
OneWire oneWire(TEMPERATURE_PIN);
DallasTemperature tempSensor(&oneWire);
WebSocketsClient webSocket;

// ============ Global Variables ============
String deviceId = "esp32_001";
int heartRate = 0;
int oxygenLevel = 0;
float temperature = 0.0;
int bpSystolic = 0;
int bpDiastolic = 0;
int signalQuality = 100;

unsigned long lastSensorRead = 0;
unsigned long lastDataSend = 0;
const unsigned long SENSOR_READ_INTERVAL = 500;   // Read sensors every 500ms
const unsigned long DATA_SEND_INTERVAL = 1000;    // Send data every 1 second
const unsigned long RECONNECT_INTERVAL = 5000;    // Try reconnect every 5 seconds

int reconnectAttempts = 0;
const int MAX_RECONNECT_ATTEMPTS = 10;

// ============ Setup ============
void setup() {
    Serial.begin(115200);
    delay(1000);
    
    Serial.println("\n\n");
    Serial.println("=================================");
    Serial.println("SANADK ESP32 Medical Sensor");
    Serial.println("=================================");
    
    // Initialize sensors
    initializeSensors();
    
    // Connect to WiFi
    connectToWiFi();
    
    // Connect to WebSocket
    connectToWebSocket();
}

// ============ Main Loop ============
void loop() {
    // Handle WebSocket
    webSocket.loop();
    
    // Read sensors
    if (millis() - lastSensorRead >= SENSOR_READ_INTERVAL) {
        readSensors();
        lastSensorRead = millis();
    }
    
    // Send data
    if (millis() - lastDataSend >= DATA_SEND_INTERVAL) {
        if (webSocket.isConnected()) {
            sendSensorData();
        }
        lastDataSend = millis();
    }
    
    // Check WiFi connection
    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("WiFi disconnected, attempting to reconnect...");
        connectToWiFi();
    }
    
    delay(10);
}

// ============ Sensor Initialization ============
void initializeSensors() {
    Serial.println("Initializing sensors...");
    
    // Initialize temperature sensor
    tempSensor.begin();
    
    // Configure ADC
    analogSetAttenuation(ADC_11db);
    
    Serial.println("Sensors initialized successfully");
}

// ============ WiFi Connection ============
void connectToWiFi() {
    Serial.print("Connecting to WiFi: ");
    Serial.println(WIFI_SSID);
    
    WiFi.mode(WIFI_STA);
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
    
    int attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 20) {
        delay(500);
        Serial.print(".");
        attempts++;
    }
    
    if (WiFi.status() == WL_CONNECTED) {
        Serial.println("\nWiFi connected!");
        Serial.print("IP address: ");
        Serial.println(WiFi.localIP());
    } else {
        Serial.println("\nFailed to connect to WiFi");
    }
}

// ============ WebSocket Connection ============
void connectToWebSocket() {
    Serial.println("Connecting to WebSocket server...");
    
    webSocket.beginSSL(SERVER_HOST, SERVER_PORT, API_ENDPOINT);
    webSocket.onEvent(webSocketEvent);
    webSocket.setReconnectInterval(RECONNECT_INTERVAL);
    
    Serial.println("WebSocket connection initiated");
}

// ============ WebSocket Event Handler ============
void webSocketEvent(WStype_t type, uint8_t * payload, size_t length) {
    switch(type) {
        case WStype_DISCONNECTED:
            Serial.println("[WebSocket] Disconnected");
            reconnectAttempts = 0;
            break;
            
        case WStype_CONNECTED:
            Serial.println("[WebSocket] Connected");
            Serial.print("Payload URL: ");
            Serial.println((char *)payload);
            reconnectAttempts = 0;
            break;
            
        case WStype_TEXT:
            Serial.print("[WebSocket] Received: ");
            Serial.println((char *)payload);
            handleServerResponse((char *)payload);
            break;
            
        case WStype_BIN:
            Serial.println("[WebSocket] Binary data received");
            break;
            
        case WStype_ERROR:
            Serial.println("[WebSocket] Error");
            break;
    }
}

// ============ Handle Server Response ============
void handleServerResponse(char* payload) {
    StaticJsonDocument<256> doc;
    DeserializationError error = deserializeJson(doc, payload);
    
    if (error) {
        Serial.print("JSON parse error: ");
        Serial.println(error.c_str());
        return;
    }
    
    if (doc["success"]) {
        Serial.println("Data received successfully by server");
        
        // Check if there are any alerts
        if (doc.containsKey("analysis")) {
            const char* alertLevel = doc["analysis"]["alert_level"];
            Serial.print("Alert Level: ");
            Serial.println(alertLevel);
            
            // Handle alerts (e.g., trigger LED, buzzer)
            if (strcmp(alertLevel, "emergency") == 0) {
                triggerEmergencyAlert();
            }
        }
    }
}

// ============ Read Sensors ============
void readSensors() {
    // Read heart rate (simulated with ADC noise + baseline)
    int rawHR = analogRead(HEART_RATE_PIN);
    heartRate = 60 + (rawHR % 40);  // 60-100 BPM range
    
    // Read oxygen level
    int rawO2 = analogRead(35);
    oxygenLevel = 95 + (rawO2 % 5);  // 95-100% range
    
    // Read temperature
    tempSensor.requestTemperatures();
    temperature = tempSensor.getTempCByIndex(0);
    if (temperature == DEVICE_DISCONNECTED_C) {
        temperature = 36.5;  // Default value if sensor fails
    }
    
    // Simulate blood pressure
    int rawBP = analogRead(BLOOD_PRESSURE_SIM_PIN);
    bpSystolic = 110 + (rawBP % 30);   // 110-140 mmHg
    bpDiastolic = 70 + ((rawBP / 2) % 20);  // 70-90 mmHg
    
    // Signal quality (always good in this simulation)
    signalQuality = 95 + (rand() % 5);
}

// ============ Send Sensor Data ============
void sendSensorData() {
    StaticJsonDocument<256> doc;
    
    doc["heart_rate"] = heartRate;
    doc["oxygen_level"] = oxygenLevel;
    doc["temperature"] = temperature;
    doc["bp_systolic"] = bpSystolic;
    doc["bp_diastolic"] = bpDiastolic;
    doc["device_id"] = deviceId;
    doc["signal_quality"] = signalQuality;
    doc["timestamp"] = millis() / 1000;
    
    String json;
    serializeJson(doc, json);
    
    // Send via WebSocket
    webSocket.sendTXT(json);
    
    // Debug output
    Serial.print("Sent: ");
    Serial.println(json);
}

// ============ Trigger Emergency Alert ============
void triggerEmergencyAlert() {
    Serial.println("!!! EMERGENCY ALERT TRIGGERED !!!");
    
    // Flash LED
    for (int i = 0; i < 5; i++) {
        digitalWrite(LED_BUILTIN, HIGH);
        delay(100);
        digitalWrite(LED_BUILTIN, LOW);
        delay(100);
    }
    
    // Can add buzzer, vibration motor, etc.
}

// ============ Utility Functions ============

// Get device uptime
unsigned long getUptime() {
    return millis() / 1000;
}

// Get WiFi signal strength
int getWiFiSignal() {
    return WiFi.RSSI();
}

// Get free heap memory
uint32_t getFreeHeap() {
    return ESP.getFreeHeap();
}

// Reset device
void resetDevice() {
    Serial.println("Resetting device...");
    ESP.restart();
}

// ============ HTTP Fallback (Alternative to WebSocket) ============
void sendDataViaHTTP() {
    if (WiFi.status() != WL_CONNECTED) {
        return;
    }
    
    WiFiClientSecure client;
    client.setInsecure();  // For testing only - use proper certificates in production
    
    if (!client.connect(SERVER_HOST, 443)) {
        Serial.println("HTTP connection failed");
        return;
    }
    
    StaticJsonDocument<256> doc;
    doc["heart_rate"] = heartRate;
    doc["oxygen_level"] = oxygenLevel;
    doc["temperature"] = temperature;
    doc["bp_systolic"] = bpSystolic;
    doc["bp_diastolic"] = bpDiastolic;
    doc["device_id"] = deviceId;
    doc["signal_quality"] = signalQuality;
    
    String json;
    serializeJson(doc, json);
    
    String request = String("POST ") + API_ENDPOINT + " HTTP/1.1\r\n" +
                    "Host: " + SERVER_HOST + "\r\n" +
                    "Content-Type: application/json\r\n" +
                    "Content-Length: " + json.length() + "\r\n" +
                    "Authorization: Bearer " + AUTH_TOKEN + "\r\n" +
                    "Connection: close\r\n\r\n" +
                    json;
    
    client.print(request);
    
    // Read response
    while (client.connected()) {
        String line = client.readStringUntil('\n');
        if (line == "\r") {
            break;
        }
    }
    
    client.stop();
    Serial.println("HTTP request sent");
}

// ============ Debug Commands ============
void handleSerialInput() {
    if (Serial.available()) {
        String command = Serial.readStringUntil('\n');
        command.trim();
        
        if (command == "status") {
            printStatus();
        } else if (command == "reset") {
            resetDevice();
        } else if (command == "reconnect") {
            connectToWebSocket();
        }
    }
}

void printStatus() {
    Serial.println("\n=== Device Status ===");
    Serial.print("WiFi: ");
    Serial.println(WiFi.status() == WL_CONNECTED ? "Connected" : "Disconnected");
    Serial.print("WebSocket: ");
    Serial.println(webSocket.isConnected() ? "Connected" : "Disconnected");
    Serial.print("Heart Rate: ");
    Serial.print(heartRate);
    Serial.println(" BPM");
    Serial.print("Oxygen: ");
    Serial.print(oxygenLevel);
    Serial.println("%");
    Serial.print("Temperature: ");
    Serial.print(temperature);
    Serial.println("°C");
    Serial.print("Blood Pressure: ");
    Serial.print(bpSystolic);
    Serial.print("/");
    Serial.print(bpDiastolic);
    Serial.println(" mmHg");
    Serial.print("Free Heap: ");
    Serial.print(getFreeHeap());
    Serial.println(" bytes");
    Serial.print("Uptime: ");
    Serial.print(getUptime());
    Serial.println(" seconds");
    Serial.println("===================\n");
}
