// PWA Install Banner and Service Worker Management
// ================================================

let deferredPrompt;
let installPromptShown = false;

// Listen for the beforeinstallprompt event
window.addEventListener('beforeinstallprompt', (e) => {
    // Prevent the mini-infobar from appearing on mobile
    e.preventDefault();
    // Stash the event for later use
    deferredPrompt = e;
    // Update UI to show install button
    showInstallPrompt();
});

// Show install prompt banner
function showInstallPrompt() {
    if (installPromptShown) return;
    
    // Create install banner
    const banner = document.createElement('div');
    banner.id = 'pwa-install-banner';
    banner.className = 'pwa-install-banner';
    banner.innerHTML = `
        <div class="pwa-banner-content">
            <div class="pwa-banner-icon">
                <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                    <path d="M16 2L2 10V22C2 26.4183 8.82 30 16 30C23.18 30 30 26.4183 30 22V10L16 2Z" stroke="#6FA8DC" stroke-width="2" fill="none"/>
                    <path d="M16 10V20M11 15H21" stroke="#6FA8DC" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="pwa-banner-text">
                <h3>تثبيت SANADK</h3>
                <p>ثبّت التطبيق على جهازك للوصول السريع والاستخدام بدون إنترنت</p>
            </div>
            <div class="pwa-banner-buttons">
                <button id="pwa-install-btn" class="pwa-btn pwa-btn-primary">تثبيت الآن</button>
                <button id="pwa-dismiss-btn" class="pwa-btn pwa-btn-secondary">لاحقاً</button>
            </div>
        </div>
    `;
    
    // Add banner to page
    document.body.insertBefore(banner, document.body.firstChild);
    installPromptShown = true;
    
    // Add event listeners
    document.getElementById('pwa-install-btn').addEventListener('click', installApp);
    document.getElementById('pwa-dismiss-btn').addEventListener('click', dismissInstallPrompt);
}

// Install app
async function installApp() {
    if (!deferredPrompt) {
        return;
    }
    
    // Show the install prompt
    deferredPrompt.prompt();
    
    // Wait for the user to respond to the prompt
    const { outcome } = await deferredPrompt.userChoice;
    
    if (outcome === 'accepted') {
        console.log('✅ تم تثبيت SANADK بنجاح');
        dismissInstallPrompt();
        showNotification('تم تثبيت SANADK بنجاح! 🎉', 'success');
    } else {
        console.log('❌ تم رفض التثبيت');
    }
    
    // Clear the deferredPrompt
    deferredPrompt = null;
}

// Dismiss install prompt
function dismissInstallPrompt() {
    const banner = document.getElementById('pwa-install-banner');
    if (banner) {
        banner.style.animation = 'slideUp 0.3s ease-out forwards';
        setTimeout(() => banner.remove(), 300);
    }
}

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `pwa-notification pwa-notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: ${type === 'success' ? '#4CAF50' : '#2196F3'};
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        animation: slideIn 0.3s ease-out;
        direction: rtl;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out forwards';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Register Service Worker (disabled for real-time mode)
function registerServiceWorker() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistration('/static/js/service-worker.js')
            .then(registration => {
                if (registration) {
                    return registration.unregister().then(() => {
                        console.log('✅ Service Worker unregistered:', registration);
                    });
                }
            })
            .catch(error => {
                console.error('❌ SW unregister failed:', error);
            });
    }
}

// Listen for app installed event
window.addEventListener('appinstalled', () => {
    console.log('✅ تم تثبيت التطبيق بنجاح');
    deferredPrompt = null;
    dismissInstallPrompt();
});

// Handle display mode changes
window.addEventListener('orientationchange', () => {
    console.log('📱 تم تغيير اتجاه الشاشة:', window.innerWidth, 'x', window.innerHeight);
});

// Initialize PWA
document.addEventListener('DOMContentLoaded', () => {
    registerServiceWorker();
    
    // Check if app is running in standalone mode
    if (window.navigator.standalone === true) {
        console.log('✅ التطبيق يعمل في وضع Standalone');
        document.body.classList.add('pwa-standalone');
    }
});

// Animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateY(100px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    @keyframes slideUp {
        from {
            transform: translateY(0);
            opacity: 1;
        }
        to {
            transform: translateY(-100px);
            opacity: 0;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100px);
            opacity: 0;
        }
    }
    
    .pwa-install-banner {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background: linear-gradient(135deg, #6FA8DC 0%, #5A92C4 100%);
        color: white;
        padding: 16px;
        z-index: 9998;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideIn 0.3s ease-out;
        direction: rtl;
    }
    
    .pwa-banner-content {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }
    
    .pwa-banner-icon {
        flex-shrink: 0;
    }
    
    .pwa-banner-text {
        flex: 1;
        min-width: 200px;
    }
    
    .pwa-banner-text h3 {
        margin: 0 0 4px 0;
        font-size: 16px;
        font-weight: 600;
    }
    
    .pwa-banner-text p {
        margin: 0;
        font-size: 14px;
        opacity: 0.95;
    }
    
    .pwa-banner-buttons {
        display: flex;
        gap: 8px;
        flex-shrink: 0;
    }
    
    .pwa-btn {
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    
    .pwa-btn-primary {
        background: white;
        color: #6FA8DC;
    }
    
    .pwa-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    .pwa-btn-secondary {
        background: rgba(255,255,255,0.2);
        color: white;
        border: 1px solid rgba(255,255,255,0.3);
    }
    
    .pwa-btn-secondary:hover {
        background: rgba(255,255,255,0.3);
    }
    
    @media (max-width: 640px) {
        .pwa-install-banner {
            padding: 12px;
        }
        
        .pwa-banner-content {
            gap: 12px;
        }
        
        .pwa-banner-text h3 {
            font-size: 14px;
        }
        
        .pwa-banner-text p {
            font-size: 12px;
        }
        
        .pwa-btn {
            padding: 6px 12px;
            font-size: 12px;
        }
    }
`;
document.head.appendChild(style);
