// Simple mock device-manager that emits Socket.IO events without native BLE
const io = require('socket.io-client');

const SANADK_SERVER_URL = process.env.SANADK_SERVER_URL || 'http://localhost:6000';

console.log('Starting SANADK device-manager mock, connecting to', SANADK_SERVER_URL);
const socket = io(SANADK_SERVER_URL, { reconnectionDelay: 2000, transports: ['websocket', 'polling'] });

socket.on('connect', () => {
  console.log('Mock connected to socket server, id=', socket.id);
  socket.emit('device_connected', { device_id: 'mock-polar-1', device_name: 'Polar H9 (mock)' });
});

socket.on('disconnect', () => {
  console.log('Mock disconnected from socket server');
});

let counter = 0;
const emitInterval = setInterval(() => {
  counter += 1;
  const hr = 60 + Math.floor(Math.random() * 60);
  const payload = {
    device_id: 'mock-polar-1',
    device_name: 'Polar H9 (mock)',
    heart_rate: hr,
    timestamp: new Date().toISOString(),
    seq: counter
  };
  socket.emit('device_data_update', payload);
  // Also emit a generic stream event name used by the bridge
  socket.emit('stream_hrv_data', payload);
  console.log('Mock emitted:', payload);
}, 3000);

process.on('SIGINT', () => {
  console.log('Mock shutting down');
  clearInterval(emitInterval);
  try { socket.emit('device_disconnected', { device_id: 'mock-polar-1' }); } catch (e) {}
  try { socket.close(); } catch (e) {}
  process.exit(0);
});

console.log('Mock device-manager started');
