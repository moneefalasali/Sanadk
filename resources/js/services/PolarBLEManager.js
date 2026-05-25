/**
 * Polar BLE Manager - Real-time Heart Rate and HRV monitoring
 * Supports Android, iOS, and Web (with Web Bluetooth API)
 */

class PolarBLEManager {
    constructor() {
        this.device = null;
        this.server = null;
        this.service = null;
        this.characteristic = null;
        this.isConnected = false;
        this.deviceId = null;
        this.listeners = [];
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 3000;

        // BLE UUIDs for Polar devices
        this.BLE_UUIDS = {
            HEART_RATE_SERVICE: '180d',
            HEART_RATE_CHARACTERISTIC: '2a37',
            BATTERY_SERVICE: '180f',
            BATTERY_CHARACTERISTIC: '2a19',
        };

        this.heartRateData = [];
        this.rrIntervals = [];
    }

    /**
     * Initialize BLE and scan for Polar devices
     */
    async scanForDevices() {
        try {
            if (!navigator.bluetooth) {
                throw new Error('Web Bluetooth API not supported');
            }

            console.log('Scanning for Polar devices...');

            this.device = await navigator.bluetooth.requestDevice({
                filters: [
                    { services: [this.BLE_UUIDS.HEART_RATE_SERVICE] },
                    { namePrefix: 'Polar' }
                ],
                optionalServices: [this.BLE_UUIDS.BATTERY_SERVICE]
            });

            this.deviceId = this.device.id;
            console.log('Device found:', this.device.name);

            return {
                success: true,
                device: {
                    id: this.device.id,
                    name: this.device.name,
                }
            };
        } catch (error) {
            console.error('Device scan error:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    /**
     * Connect to Polar device
     */
    async connect() {
        try {
            if (!this.device) {
                throw new Error('No device selected');
            }

            console.log('Connecting to device:', this.device.name);

            // Connect to GATT server
            this.server = await this.device.gatt.connect();
            console.log('Connected to GATT server');

            // Get Heart Rate service
            this.service = await this.server.getPrimaryService(this.BLE_UUIDS.HEART_RATE_SERVICE);
            console.log('Heart Rate service found');

            // Get Heart Rate characteristic
            this.characteristic = await this.service.getCharacteristic(this.BLE_UUIDS.HEART_RATE_CHARACTERISTIC);
            console.log('Heart Rate characteristic found');

            // Start listening for notifications
            await this.characteristic.startNotifications();
            this.characteristic.addEventListener('characteristicvaluechanged', 
                (event) => this.handleHeartRateData(event));

            this.isConnected = true;
            this.reconnectAttempts = 0;

            // Get battery level
            await this.getBatteryLevel();

            // Notify listeners
            this.notifyListeners('connected', {
                deviceId: this.deviceId,
                deviceName: this.device.name
            });

            console.log('Connected successfully');
            return { success: true };
        } catch (error) {
            console.error('Connection error:', error);
            this.isConnected = false;
            return {
                success: false,
                error: error.message
            };
        }
    }

    /**
     * Handle incoming heart rate data
     */
    handleHeartRateData(event) {
        try {
            const data = event.target.value;
            const parsed = this.parseHeartRateData(data);

            if (parsed) {
                this.heartRateData.push({
                    heartRate: parsed.heartRate,
                    timestamp: new Date().toISOString(),
                });

                if (parsed.rrIntervals && parsed.rrIntervals.length > 0) {
                    this.rrIntervals.push(...parsed.rrIntervals);
                }

                // Keep only last 100 readings
                if (this.heartRateData.length > 100) {
                    this.heartRateData.shift();
                }

                // Notify listeners
                this.notifyListeners('heartRateUpdate', parsed);

                // Send to server
                this.sendDataToServer(parsed);
            }
        } catch (error) {
            console.error('Error handling heart rate data:', error);
        }
    }

    /**
     * Parse BLE heart rate data
     */
    parseHeartRateData(data) {
        try {
            const flags = data.getUint8(0);
            let offset = 1;

            // Check if heart rate is 8-bit or 16-bit
            const hrFormat = (flags & 0x01) ? 16 : 8;
            let heartRate;

            if (hrFormat === 16) {
                heartRate = data.getUint16(offset, true);
                offset += 2;
            } else {
                heartRate = data.getUint8(offset);
                offset += 1;
            }

            // Parse RR intervals if available
            let rrIntervals = [];
            if (flags & 0x10) {
                while (offset < data.byteLength) {
                    const rrInterval = data.getUint16(offset, true);
                    rrIntervals.push(rrInterval / 1024); // Convert to seconds
                    offset += 2;
                }
            }

            return {
                heartRate: heartRate,
                rrIntervals: rrIntervals,
                timestamp: new Date().toISOString(),
            };
        } catch (error) {
            console.error('Error parsing heart rate data:', error);
            return null;
        }
    }

    /**
     * Calculate HRV from RR intervals
     */
    calculateHRV() {
        if (this.rrIntervals.length < 2) {
            return null;
        }

        // Calculate SDNN (Standard Deviation of NN intervals)
        const mean = this.rrIntervals.reduce((a, b) => a + b) / this.rrIntervals.length;
        const squareDiffs = this.rrIntervals.map(x => Math.pow(x - mean, 2));
        const variance = squareDiffs.reduce((a, b) => a + b) / squareDiffs.length;
        const sdnn = Math.sqrt(variance);

        // Calculate RMSSD (Root Mean Square of Successive Differences)
        const successiveDiffs = [];
        for (let i = 1; i < this.rrIntervals.length; i++) {
            successiveDiffs.push(Math.abs(this.rrIntervals[i] - this.rrIntervals[i - 1]));
        }
        const meanSquare = successiveDiffs
            .map(x => x * x)
            .reduce((a, b) => a + b) / successiveDiffs.length;
        const rmssd = Math.sqrt(meanSquare);

        return {
            sdnn: sdnn.toFixed(2),
            rmssd: rmssd.toFixed(2),
            meanRR: mean.toFixed(2),
        };
    }

    /**
     * Get battery level
     */
    async getBatteryLevel() {
        try {
            const batteryService = await this.server.getPrimaryService(this.BLE_UUIDS.BATTERY_SERVICE);
            const batteryChar = await batteryService.getCharacteristic(this.BLE_UUIDS.BATTERY_CHARACTERISTIC);
            const batteryValue = await batteryChar.readValue();
            const batteryLevel = batteryValue.getUint8(0);

            this.notifyListeners('batteryUpdate', {
                batteryLevel: batteryLevel,
                timestamp: new Date().toISOString(),
            });

            return batteryLevel;
        } catch (error) {
            console.warn('Could not read battery level:', error);
            return null;
        }
    }

    /**
     * Send data to server
     */
    async sendDataToServer(data) {
        try {
            const token = localStorage.getItem('auth_token');
            const response = await fetch('/api/devices/polar/data', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`,
                },
                body: JSON.stringify({
                    heart_rate: data.heartRate,
                    rr_interval: data.rrIntervals[0] || null,
                    hrv: this.calculateHRV(),
                    device_id: this.deviceId,
                    battery_level: this.batteryLevel || null,
                    signal_quality: 100,
                })
            });

            if (!response.ok) {
                throw new Error(`Server error: ${response.status}`);
            }

            const result = await response.json();
            this.notifyListeners('serverResponse', result);
        } catch (error) {
            console.error('Error sending data to server:', error);
        }
    }

    /**
     * Disconnect from device
     */
    async disconnect() {
        try {
            if (this.characteristic) {
                await this.characteristic.stopNotifications();
            }

            if (this.device && this.device.gatt) {
                this.device.gatt.disconnect();
            }

            this.isConnected = false;
            this.notifyListeners('disconnected', {
                deviceId: this.deviceId
            });

            console.log('Disconnected from device');
            return { success: true };
        } catch (error) {
            console.error('Disconnect error:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    /**
     * Auto-reconnect logic
     */
    async autoReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            console.log(`Attempting to reconnect... (${this.reconnectAttempts}/${this.maxReconnectAttempts})`);

            setTimeout(async () => {
                try {
                    await this.connect();
                } catch (error) {
                    console.error('Reconnection failed:', error);
                    this.autoReconnect();
                }
            }, this.reconnectDelay);
        } else {
            console.error('Max reconnection attempts reached');
            this.notifyListeners('connectionFailed', {
                message: 'Could not reconnect after multiple attempts'
            });
        }
    }

    /**
     * Get current heart rate
     */
    getCurrentHeartRate() {
        if (this.heartRateData.length === 0) {
            return null;
        }
        return this.heartRateData[this.heartRateData.length - 1].heartRate;
    }

    /**
     * Get heart rate history
     */
    getHeartRateHistory(limit = 50) {
        return this.heartRateData.slice(-limit);
    }

    /**
     * Add event listener
     */
    addEventListener(event, callback) {
        this.listeners.push({ event, callback });
    }

    /**
     * Remove event listener
     */
    removeEventListener(event, callback) {
        this.listeners = this.listeners.filter(l => 
            !(l.event === event && l.callback === callback)
        );
    }

    /**
     * Notify all listeners
     */
    notifyListeners(event, data) {
        this.listeners.forEach(listener => {
            if (listener.event === event) {
                listener.callback(data);
            }
        });
    }

    /**
     * Get connection status
     */
    getStatus() {
        return {
            isConnected: this.isConnected,
            deviceId: this.deviceId,
            deviceName: this.device?.name || null,
            currentHeartRate: this.getCurrentHeartRate(),
            hrv: this.calculateHRV(),
        };
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PolarBLEManager;
}
