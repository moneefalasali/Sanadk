import axios from 'axios';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
window.Pusher = Pusher;

// Prefer Reverb Vite vars, fallback to legacy Pusher vars
const REVERB_KEY = import.meta.env.VITE_REVERB_APP_KEY || import.meta.env.VITE_PUSHER_APP_KEY;
const REVERB_HOST = import.meta.env.VITE_REVERB_HOST || import.meta.env.VITE_PUSHER_HOST || null;
const REVERB_PORT = import.meta.env.VITE_REVERB_PORT || import.meta.env.VITE_PUSHER_PORT || null;
const REVERB_SCHEME = import.meta.env.VITE_REVERB_SCHEME || import.meta.env.VITE_PUSHER_SCHEME || 'http';
const REVERB_USE_TLS = (import.meta.env.VITE_REVERB_USE_TLS === 'true') || (REVERB_SCHEME === 'https');

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: REVERB_KEY,
    // Configure ws host/ports for self-hosted laravel-websockets (Reverb)
    wsHost: REVERB_HOST,
    wsPort: REVERB_PORT ? Number(REVERB_PORT) : (REVERB_USE_TLS ? 443 : 6001),
    wssPort: REVERB_PORT ? Number(REVERB_PORT) : (REVERB_USE_TLS ? 443 : 6001),
    forceTLS: REVERB_USE_TLS,
    enabledTransports: ['ws', 'wss'],
    // Disable stats to avoid contacting Pusher servers
    disableStats: true,
    // Ensure auth endpoint uses Laravel's broadcasting auth
    authEndpoint: '/broadcasting/auth',
});

/**
 * Alternative: Using Socket.IO for real-time communication
 */

// import io from 'socket.io-client';
// 
// window.io = io;
// 
// window.Echo = {
//     private: (channel) => {
//         const socket = io(window.location.origin, {
//             auth: {
//                 token: localStorage.getItem('auth_token'),
//             }
//         });
//         
//         return {
//             listen: (event, callback) => {
//                 socket.on(`${channel}.${event}`, callback);
//             }
//         };
//     }
// };

/**
 * Medical Device Real-time Listeners
 */

// Listen for vital sign updates
if (window.Echo) {
    const userId = document.querySelector('meta[name="user-id"]')?.content;
    
    if (userId) {
        // Subscribe to private channel
        window.Echo.private(`user.${userId}`)
            .listen('VitalSignUpdated', (data) => {
                console.log('Vital Sign Updated:', data);
                
                // Dispatch custom event for components to listen
                window.dispatchEvent(new CustomEvent('vital-sign-updated', {
                    detail: data
                }));
                
                // Update UI
                updateVitalSignsDisplay(data);
            })
            .listen('EEGDataUpdated', (data) => {
                console.log('EEG Data Updated:', data);
                
                window.dispatchEvent(new CustomEvent('eeg-data-updated', {
                    detail: data
                }));
                
                updateEEGDisplay(data);
            })
            .listen('DeviceDisconnected', (data) => {
                console.log('Device Disconnected:', data);
                
                window.dispatchEvent(new CustomEvent('device-disconnected', {
                    detail: data
                }));
                
                showDeviceDisconnectionAlert(data);
            });
        
        // Subscribe to doctor channel
        window.Echo.private(`doctor.patient.${userId}`)
            .listen('VitalSignUpdated', (data) => {
                console.log('Patient Vital Sign Updated:', data);
                updatePatientVitalSignsDisplay(data);
            });
    }
}

/**
 * UI Update Functions
 */

function updateVitalSignsDisplay(data) {
    const vitalSignsElement = document.getElementById('vital-signs-display');
    if (vitalSignsElement) {
        const vitalSign = data.vital_sign;
        vitalSignsElement.innerHTML = `
            <div class="vital-sign-card">
                <div class="vital-sign-item">
                    <span class="label">Heart Rate:</span>
                    <span class="value">${vitalSign.heart_rate} BPM</span>
                </div>
                <div class="vital-sign-item">
                    <span class="label">Oxygen:</span>
                    <span class="value">${vitalSign.oxygen_level}%</span>
                </div>
                <div class="vital-sign-item">
                    <span class="label">Temperature:</span>
                    <span class="value">${vitalSign.temperature}°C</span>
                </div>
                <div class="alert-level" data-level="${data.analysis.alert_level}">
                    ${data.analysis.alert_level.toUpperCase()}
                </div>
            </div>
        `;
    }
}

function updateEEGDisplay(data) {
    const eegElement = document.getElementById('eeg-display');
    if (eegElement) {
        // Update EEG visualization
        const channels = data.eeg_data;
        eegElement.innerHTML = `
            <div class="eeg-channels">
                ${Object.entries(channels).map(([channel, value]) => `
                    <div class="channel">
                        <span class="channel-name">${channel}</span>
                        <span class="channel-value">${value.toFixed(2)}</span>
                    </div>
                `).join('')}
            </div>
        `;
    }
}

function updatePatientVitalSignsDisplay(data) {
    const patientElement = document.getElementById('patient-vital-signs');
    if (patientElement) {
        // Update patient vital signs in doctor's dashboard
        updateVitalSignsDisplay(data);
    }
}

function showDeviceDisconnectionAlert(data) {
    const alertElement = document.createElement('div');
    alertElement.className = 'alert alert-warning';
    alertElement.innerHTML = `
        <strong>Device Disconnected!</strong><br>
        ${data.device_type} (${data.device_id}) has been disconnected.
    `;
    
    const container = document.querySelector('.alerts-container') || document.body;
    container.prepend(alertElement);
    
    // Auto-remove after 5 seconds
    setTimeout(() => alertElement.remove(), 5000);
}

export default window;
