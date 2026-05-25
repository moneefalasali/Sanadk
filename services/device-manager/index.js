require('dotenv').config();
const noble = require('@abandonware/noble');
const io = require('socket.io-client');
const fetch = require('node-fetch');

const SANADK_SERVER_URL = process.env.SANADK_SERVER_URL || 'http://localhost:6000';
// Default to the hosted Laravel Cloud REST endpoint (can be overridden in .env)
const SANADK_REST_ENDPOINT = process.env.SANADK_REST_ENDPOINT || 'https://sanadk-main-2rievw.laravel.cloud/api/devices';
const SANADK_API_TOKEN = process.env.SANADK_API_TOKEN || '';
const BLE_NAME_FILTER = process.env.BLE_NAME_FILTER || 'Polar';
const DEVICE_DEFAULT_NAME = process.env.DEVICE_DEFAULT_NAME || 'polar-h9';

const socket = io(SANADK_SERVER_URL, { path: (process.env.SOCKET_PATH || '/socket.io') });

console.log('SANADK Device Manager starting with:');
console.log('  SANADK_SERVER_URL =', SANADK_SERVER_URL);
console.log('  SANADK_REST_ENDPOINT =', SANADK_REST_ENDPOINT);
console.log('  SANADK_API_TOKEN =', SANADK_API_TOKEN ? '*** set ***' : 'not set');

socket.on('connect', () => {
  console.log('Connected to SANADK socket server:', SANADK_SERVER_URL);
});
socket.on('disconnect', () => {
  console.log('Disconnected from SANADK socket server');
});

// Heart Rate Service/Characteristic UUIDs
const HR_SERVICE = '180d';
const HR_MEASUREMENT_CHAR = '2a37';

function parseHeartRate(data) {
  // BLE Heart Rate Measurement characteristic parsing
  // https://www.bluetooth.com/specifications/specs/heart-rate-service-1-0/
  if (!data || data.length === 0) return null;
  const flags = data.readUInt8(0);
  const hrFormatUInt16 = flags & 0x01;
  let offset = 1;
  let heartRate = 0;
  if (hrFormatUInt16) {
    heartRate = data.readUInt16LE(offset);
    offset += 2;
  } else {
    heartRate = data.readUInt8(offset);
    offset += 1;
  }
  return { heartRate };
}

async function postRest(deviceId, payload) {
  try {
    const url = `${SANADK_REST_ENDPOINT}/${encodeURIComponent(deviceId)}/data`;
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        ...(SANADK_API_TOKEN ? { 'Authorization': `Bearer ${SANADK_API_TOKEN}` } : {})
      },
      body: JSON.stringify(payload)
    });
    if (!res.ok) {
      const txt = await res.text();
      console.warn('REST post failed', res.status, txt);
    }
  } catch (err) {
    console.error('Error posting REST data:', err);
  }
}

function emitSocket(event, data) {
  try {
    socket.emit(event, data);
  } catch (err) {
    console.error('Socket emit error:', err);
  }
}

noble.on('stateChange', async (state) => {
  console.log('Noble state:', state);
  if (state === 'poweredOn') {
    console.log('Starting BLE scan...');
    try {
      await noble.startScanningAsync([HR_SERVICE], false);
    } catch (err) {
      console.error('startScanningAsync error:', err);
    }
  } else {
    noble.stopScanning();
  }
});

noble.on('discover', async (peripheral) => {
  try {
    const name = peripheral.advertisement && (peripheral.advertisement.localName || peripheral.advertisement.serviceData?.[0]?.uuid) || 'unknown';
    if (BLE_NAME_FILTER && !String(name).includes(BLE_NAME_FILTER)) {
      // skip non-matching devices
      return;
    }

    console.log('Discovered device:', name, peripheral.id);
    // Stop scanning to avoid connecting multiple devices simultaneously
    noble.stopScanning();

    peripheral.on('disconnect', () => {
      console.log('Peripheral disconnected:', peripheral.id);
      emitSocket('device_disconnected', { device_id: peripheral.id });
      // resume scanning
      setTimeout(() => {
        noble.startScanningAsync([HR_SERVICE], false).catch(err => console.error(err));
      }, 2000);
    });

    await peripheral.connectAsync();
    console.log('Connected to peripheral', peripheral.id);
    emitSocket('device_connected', { device_id: peripheral.id, device_name: name || DEVICE_DEFAULT_NAME });

    const { characteristics } = await peripheral.discoverSomeServicesAndCharacteristicsAsync([HR_SERVICE], [HR_MEASUREMENT_CHAR]);
    const hrChar = characteristics.find(c => c.uuid === HR_MEASUREMENT_CHAR);
    if (!hrChar) {
      console.warn('Heart Rate Measurement characteristic not found');
      await peripheral.disconnectAsync();
      return;
    }

    // Subscribe to notifications
    await hrChar.subscribeAsync();
    hrChar.on('data', (data, isNotification) => {
      const parsed = parseHeartRate(data);
      if (parsed) {
        const payload = {
          device_id: peripheral.id,
          device_name: name || DEVICE_DEFAULT_NAME,
          heart_rate: parsed.heartRate,
          timestamp: new Date().toISOString()
        };
        console.log('HR payload:', payload);
        // Emit to socket.io server
        emitSocket('stream_hrv_data', payload);
        emitSocket('device_data_update', payload);
        // Also post to REST endpoint
        postRest(peripheral.id, payload);
      }
    });

  } catch (err) {
    console.error('Error in discover handler:', err);
    try { noble.startScanningAsync([HR_SERVICE], false); } catch (e) {}
  }
});

process.on('SIGINT', async () => {
  console.log('Shutting down...');
  try { noble.stopScanning(); } catch (e) {}
  try { socket.close(); } catch (e) {}
  process.exit(0);
});

console.log('Sanadk Device Manager started');
