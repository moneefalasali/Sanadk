/**
 * SANADK - Seizure Detection System
 * Main Frontend JavaScript - Production Ready
 */

// ============================================================================
// SERVICE WORKER REGISTRATION
// ============================================================================

if ('serviceWorker' in navigator) {
    window.addEventListener('load', async () => {
        try {
            const registration = await navigator.serviceWorker.getRegistration('/sw.js');
            if (registration) {
                await registration.unregister();
                console.log('SW unregistered: /sw.js');
            }
        } catch (registrationError) {
            console.log('SW unregister failed: ', registrationError);
        }
    });
}

// Update notification
function showUpdateNotification() {
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 left-4 right-4 bg-blue-600 text-white p-4 rounded-lg shadow-lg z-50';
    notification.innerHTML = `
        <div class="flex items-center justify-between">
            <div>
                <h4 class="font-semibold">تحديث متاح</h4>
                <p class="text-sm">تم تحديث التطبيق. أعد تحميل الصفحة للحصول على أحدث الميزات.</p>
            </div>
            <button onclick="window.location.reload()" class="bg-white text-blue-600 px-4 py-2 rounded font-medium hover:bg-gray-100 transition">
                إعادة التحميل
            </button>
        </div>
    `;
    document.body.appendChild(notification);

    // Auto-hide after 10 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 10000);
}

// ============================================================================
// NETWORK STATUS MONITORING
// ============================================================================

let networkStatus = navigator.onLine;
window.addEventListener('online', handleNetworkChange);
window.addEventListener('offline', handleNetworkChange);

function handleNetworkChange() {
    const newStatus = navigator.onLine;
    if (newStatus !== networkStatus) {
        networkStatus = newStatus;
        showNetworkStatus();
    }
}

function showNetworkStatus() {
    // Remove existing notification
    const existing = document.querySelector('.network-status');
    if (existing) existing.remove();

    const status = document.createElement('div');
    status.className = `network-status fixed top-20 left-4 right-4 p-3 rounded-lg shadow-lg z-40 ${
        networkStatus ? 'bg-green-600 text-white' : 'bg-red-600 text-white'
    }`;

    status.innerHTML = `
        <div class="flex items-center gap-2">
            <i class="fas ${networkStatus ? 'fa-wifi' : 'fa-wifi-slash'}"></i>
            <span>${networkStatus ? 'متصل بالإنترنت' : 'غير متصل بالإنترنت'}</span>
        </div>
    `;

    document.body.appendChild(status);

    // Auto-hide after 3 seconds
    setTimeout(() => {
        if (status.parentNode) {
            status.remove();
        }
    }, 3000);
}

// ============================================================================
// ERROR HANDLING
// ============================================================================

window.addEventListener('error', (event) => {
    console.error('Global error:', event.error);
    // Don't show error notifications in production unless critical
});

window.addEventListener('unhandledrejection', (event) => {
    console.error('Unhandled promise rejection:', event.reason);
    // Handle unhandled promise rejections
});

// ============================================================================
// PERFORMANCE MONITORING
// ============================================================================

if ('performance' in window && 'PerformanceObserver' in window) {
    try {
        // Monitor long tasks
        const observer = new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                if (entry.duration > 50) { // Tasks longer than 50ms
                    console.warn('Long task detected:', entry);
                }
            }
        });
        observer.observe({ entryTypes: ['longtask'] });
    } catch (e) {
        console.warn('Performance monitoring not supported');
    }
}

// ============================================================================
// SAFE STORAGE ACCESS
// ============================================================================

function safeLocalStorage() {
    try {
        if (typeof Storage === 'undefined') {
            return null;
        }
        // Test storage access
        localStorage.setItem('test', 'test');
        localStorage.removeItem('test');
        return localStorage;
    } catch (e) {
        console.warn('localStorage not available:', e);
        return null;
    }
}

function safeSessionStorage() {
    try {
        if (typeof Storage === 'undefined') {
            return null;
        }
        sessionStorage.setItem('test', 'test');
        sessionStorage.removeItem('test');
        return sessionStorage;
    } catch (e) {
        console.warn('sessionStorage not available:', e);
        return null;
    }
}

// ============================================================================
// SOCKET.IO CONNECTION (Optional)
// ============================================================================

// Check if Socket.IO is available
if (typeof io !== 'undefined') {
    const socket = io({
        reconnection: true,
        reconnectionDelay: 1000,
        reconnectionDelayMax: 5000,
        reconnectionAttempts: 5
    });

    socket.on('connect', () => {
        console.log('Connected to SANADK server');
        showNotification('متصل بنظام SANADK', 'success');
    });

    socket.on('disconnect', () => {
        console.log('Disconnected from server');
        showNotification('قطع الاتصال بالنظام', 'warning');
    });

    socket.on('error', (error) => {
        console.error('Socket error:', error);
        showNotification('خطأ في الاتصال: ' + error.message, 'error');
    });
} else {
    console.warn('Socket.IO not available - real-time features disabled');
}

// ============================================================================
// AUTHENTICATION
// ============================================================================

let currentUser = null;
let authToken = null;

// Check if user is already logged in
document.addEventListener('DOMContentLoaded', () => {
    // Try both 'access_token' and 'authToken' for backward compatibility
    const storage = safeLocalStorage();
    if (storage) {
        const savedToken = storage.getItem('access_token') || storage.getItem('authToken');
        const savedUser = storage.getItem('currentUser');

        if (savedToken && savedUser) {
            authToken = savedToken;
            currentUser = JSON.parse(savedUser);
            updateUIForLoggedInUser();
        }
    }
});

// ============================================================================
// NOTIFICATION SYSTEM
// ============================================================================

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 left-4 right-4 p-4 rounded-lg shadow-lg z-40 ${
        type === 'success' ? 'bg-green-600 text-white' :
        type === 'error' ? 'bg-red-600 text-white' :
        type === 'warning' ? 'bg-yellow-600 text-white' :
        'bg-blue-600 text-white'
    }`;

    notification.innerHTML = `
        <div class="flex items-center gap-2">
            <i class="fas ${
                type === 'success' ? 'fa-check-circle' :
                type === 'error' ? 'fa-exclamation-triangle' :
                type === 'warning' ? 'fa-exclamation-triangle' :
                'fa-info-circle'
            }"></i>
            <span>${message}</span>
        </div>
    `;

    document.body.appendChild(notification);

    // Auto-hide after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// ============================================================================
// UI UPDATE FUNCTIONS
// ============================================================================

function updateUIForLoggedInUser() {
    // Update UI elements for logged in user
    const userElements = document.querySelectorAll('[data-user-name]');
    userElements.forEach(el => {
        if (currentUser && currentUser.name) {
            el.textContent = currentUser.name;
        }
    });

    // Show/hide elements based on authentication
    const authRequired = document.querySelectorAll('[data-auth-required]');
    const authHidden = document.querySelectorAll('[data-auth-hidden]');

    authRequired.forEach(el => el.style.display = currentUser ? 'block' : 'none');
    authHidden.forEach(el => el.style.display = currentUser ? 'none' : 'block');
}

// ============================================================================
// EXPORT UTILITIES
// ============================================================================

window.AppUtils = {
    storage: safeLocalStorage(),
    sessionStorage: safeSessionStorage(),
    isOnline: () => navigator.onLine,
    showNetworkStatus,
    showNotification,
    showUpdateNotification
};