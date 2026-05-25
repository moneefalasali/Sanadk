/**
 * Emotiv EEG Manager - Real-time brain signal monitoring via Cortex API
 * Supports WebSocket streaming from Emotiv devices
 */

class EmotivEEGManager {
    constructor() {
        this.ws = null;
        this.sessionId = null;
        this.headsetId = null;
        this.isConnected = false;
        this.listeners = [];
        this.eegBuffer = [];
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;

        // Emotiv Cortex API credentials
        this.CLIENT_ID = 'DE83RyH24oCWgBq2HbC0M7ZyP1BPX9l5goUO5jCV';
        this.APP_ID = 'com.ghala_tech.University-Project';
        this.CORTEX_URL = 'wss://api.emotivcloud.com';

        // EEG channels
        this.CHANNELS = ['AF3', 'AF4', 'F3', 'F4', 'FC5', 'FC6', 'P7', 'P8', 'O1', 'O2'];
    }

    /**
     * Initialize WebSocket connection to Emotiv Cortex API
     */
    async initialize() {
        try {
            console.log('Initializing Emotiv EEG connection...');

            // Get initialization data from server
            const response = await fetch('/api/emotiv/init', {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                }
            });

            if (!response.ok) {
                throw new Error('Failed to get initialization data');
            }

            const data = await response.json();
            console.log('Emotiv initialization data received');

            return {
                success: true,
                message: 'Emotiv EEG manager initialized'
            };
        } catch (error) {
            console.error('Initialization error:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    /**
     * Connect to Emotiv Cortex API via WebSocket
     */
    async connect() {
        try {
            console.log('Connecting to Emotiv Cortex API...');

            return new Promise((resolve, reject) => {
                this.ws = new WebSocket(this.CORTEX_URL);

                this.ws.onopen = () => {
                    console.log('WebSocket connected');
                    this.isConnected = true;
                    this.reconnectAttempts = 0;
                    this.notifyListeners('connected', {});
                    resolve({ success: true });
                };

                this.ws.onmessage = (event) => {
                    this.handleMessage(JSON.parse(event.data));
                };

                this.ws.onerror = (error) => {
                    console.error('WebSocket error:', error);
                    this.notifyListeners('error', { error: error.message });
                    reject(error);
                };

                this.ws.onclose = () => {
                    console.log('WebSocket closed');
                    this.isConnected = false;
                    this.notifyListeners('disconnected', {});
                    this.autoReconnect();
                };

                // Timeout after 10 seconds
                setTimeout(() => {
                    if (!this.isConnected) {
                        reject(new Error('Connection timeout'));
                    }
                }, 10000);
            });
        } catch (error) {
            console.error('Connection error:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    /**
     * Handle incoming WebSocket messages
     */
    handleMessage(message) {
        try {
            if (message.eeg) {
                // EEG data received
                this.handleEEGData(message.eeg);
            } else if (message.sessionId) {
                // Session created
                this.sessionId = message.sessionId;
                this.notifyListeners('sessionCreated', { sessionId: this.sessionId });
            } else if (message.error) {
                // Error message
                console.error('Emotiv error:', message.error);
                this.notifyListeners('error', { error: message.error });
            }
        } catch (error) {
            console.error('Error handling message:', error);
        }
    }

    /**
     * Handle incoming EEG data
     */
    handleEEGData(eegData) {
        try {
            // Parse EEG channels
            const channels = {};
            this.CHANNELS.forEach((channel, index) => {
                channels[channel] = eegData[index] || 0;
            });

            // Store in buffer
            this.eegBuffer.push({
                channels: channels,
                timestamp: new Date().toISOString(),
            });

            // Keep only last 500 readings
            if (this.eegBuffer.length > 500) {
                this.eegBuffer.shift();
            }

            // Notify listeners
            this.notifyListeners('eegUpdate', {
                channels: channels,
                timestamp: new Date().toISOString(),
            });

            // Send to server
            this.sendDataToServer(channels);
        } catch (error) {
            console.error('Error handling EEG data:', error);
        }
    }

    /**
     * Create EEG session
     */
    async createSession(headsetId) {
        try {
            this.headsetId = headsetId;

            const message = {
                jsonrpc: '2.0',
                method: 'createSession',
                params: {
                    appId: this.APP_ID,
                    headsetId: headsetId,
                },
                id: 1,
            };

            this.ws.send(JSON.stringify(message));
            console.log('Session creation request sent');

            return { success: true };
        } catch (error) {
            console.error('Session creation error:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    /**
     * Subscribe to EEG stream
     */
    async subscribeToEEG() {
        try {
            if (!this.sessionId) {
                throw new Error('No session created');
            }

            const message = {
                jsonrpc: '2.0',
                method: 'subscribe',
                params: {
                    sessionId: this.sessionId,
                    streams: ['eeg'],
                },
                id: 2,
            };

            this.ws.send(JSON.stringify(message));
            console.log('EEG subscription request sent');

            return { success: true };
        } catch (error) {
            console.error('EEG subscription error:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    /**
     * Send EEG data to server
     */
    async sendDataToServer(channels) {
        try {
            const token = localStorage.getItem('auth_token');
            const response = await fetch('/api/devices/emotiv/data', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`,
                },
                body: JSON.stringify({
                    ...channels,
                    session_id: this.sessionId,
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
     * Analyze EEG for seizure patterns
     */
    analyzeForSeizures() {
        if (this.eegBuffer.length < 10) {
            return null;
        }

        // Get recent readings
        const recentReadings = this.eegBuffer.slice(-10);

        // Calculate average amplitude
        let totalAmplitude = 0;
        recentReadings.forEach(reading => {
            this.CHANNELS.forEach(channel => {
                totalAmplitude += Math.abs(reading.channels[channel]);
            });
        });

        const avgAmplitude = totalAmplitude / (recentReadings.length * this.CHANNELS.length);

        // Check for synchronized activity (spike-wave patterns)
        const correlations = this.calculateChannelCorrelations(recentReadings);
        const maxCorrelation = Math.max(...correlations);

        // Determine risk level
        let riskLevel = 'low';
        let confidence = 0;

        if (avgAmplitude > 100 && maxCorrelation > 0.8) {
            riskLevel = 'high';
            confidence = 0.9;
        } else if (avgAmplitude > 80 || maxCorrelation > 0.7) {
            riskLevel = 'moderate';
            confidence = 0.6;
        }

        return {
            riskLevel: riskLevel,
            confidence: confidence,
            avgAmplitude: avgAmplitude.toFixed(2),
            maxCorrelation: maxCorrelation.toFixed(2),
        };
    }

    /**
     * Calculate correlations between channels
     */
    calculateChannelCorrelations(readings) {
        const correlations = [];

        for (let i = 0; i < this.CHANNELS.length - 1; i++) {
            for (let j = i + 1; j < this.CHANNELS.length; j++) {
                let sum1 = 0, sum2 = 0, sumProduct = 0;

                readings.forEach(reading => {
                    const val1 = reading.channels[this.CHANNELS[i]];
                    const val2 = reading.channels[this.CHANNELS[j]];
                    sum1 += val1;
                    sum2 += val2;
                    sumProduct += val1 * val2;
                });

                const mean1 = sum1 / readings.length;
                const mean2 = sum2 / readings.length;
                const meanProduct = sumProduct / readings.length;

                const correlation = Math.abs((meanProduct - mean1 * mean2) / 
                    (Math.sqrt(mean1 * mean1) * Math.sqrt(mean2 * mean2) + 0.001));

                correlations.push(Math.min(correlation, 1.0));
            }
        }

        return correlations.length > 0 ? correlations : [0];
    }

    /**
     * Get current EEG data
     */
    getCurrentEEGData() {
        if (this.eegBuffer.length === 0) {
            return null;
        }
        return this.eegBuffer[this.eegBuffer.length - 1];
    }

    /**
     * Get EEG history
     */
    getEEGHistory(limit = 100) {
        return this.eegBuffer.slice(-limit);
    }

    /**
     * Disconnect from Emotiv
     */
    async disconnect() {
        try {
            if (this.ws) {
                this.ws.close();
            }

            this.isConnected = false;
            this.sessionId = null;
            this.notifyListeners('disconnected', {});

            console.log('Disconnected from Emotiv');
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
            }, 3000);
        } else {
            console.error('Max reconnection attempts reached');
            this.notifyListeners('connectionFailed', {
                message: 'Could not reconnect after multiple attempts'
            });
        }
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
            sessionId: this.sessionId,
            headsetId: this.headsetId,
            currentEEGData: this.getCurrentEEGData(),
            seizureAnalysis: this.analyzeForSeizures(),
        };
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = EmotivEEGManager;
}
