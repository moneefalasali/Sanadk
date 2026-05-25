// Web Bluetooth helper for SANADK - connects to Heart Rate devices (Polar H9) and forwards HR to server
(function() {
    async function connectAndStream(deviceId) {
        if (!navigator.bluetooth) {
            alert('متصفحك لا يدعم Web Bluetooth. استخدم Chrome على Android أو Desktop.');
            return;
        }

        try {
            const device = await navigator.bluetooth.requestDevice({
                filters: [{ services: ['heart_rate'] }],
                optionalServices: ['battery_service']
            });

            const server = await device.gatt.connect();
            const service = await server.getPrimaryService('heart_rate');
            const characteristic = await service.getCharacteristic('heart_rate_measurement');

            await characteristic.startNotifications();
            characteristic.addEventListener('characteristicvaluechanged', (event) => {
                const data = event.target.value;
                const parsed = parseHeartRate(data);
                if (parsed) {
                    const payload = {
                        heart_rate: parsed.heartRate,
                        timestamp: new Date().toISOString()
                    };
                    // send to server via authenticated session
                    sendToServer(deviceId, payload).catch(err => console.error('Send error', err));
                }
            });

            alert('تم الاتصال بالجهاز عبر Bluetooth. ستصل بيانات النبض تلقائياً.');
        } catch (err) {
            console.error('Bluetooth error:', err);
            alert('فشل الاتصال بجهاز البلوتوث: ' + (err.message || err));
        }
    }

    function parseHeartRate(value) {
        // DataView
        if (!value) return null;
        try {
            const flags = value.getUint8(0);
            const hrFormatUInt16 = flags & 0x01;
            let offset = 1;
            let heartRate = 0;
            if (hrFormatUInt16) {
                heartRate = value.getUint16(offset, /*littleEndian=*/true);
                offset += 2;
            } else {
                heartRate = value.getUint8(offset);
                offset += 1;
            }
            return { heartRate };
        } catch (e) {
            console.error('Parse HR error', e);
            return null;
        }
    }

    async function sendToServer(deviceId, payload) {
        const url = `/devices/${deviceId}/bluetooth-data`;
        const token = window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        });
        if (!res.ok) {
            const txt = await res.text();
            console.warn('Server responded with', res.status, txt);
        }
        return res;
    }

    // Attach handlers
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.web-bluetooth-connect').forEach(btn => {
            btn.addEventListener('click', function() {
                const deviceId = this.dataset.deviceId;
                connectAndStream(deviceId);
            });
        });
    });

})();
