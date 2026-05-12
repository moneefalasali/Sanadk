// map.js - Map functionality for SANADK
// Production-ready map implementation with error handling

class SanadakMap {
    constructor(options = {}) {
        this.map = null;
        this.currentLayer = null;
        this.userLocation = null;
        this.hospitals = [];
        this.hospitalMarkers = [];
        this.navigationArrow = null;
        this.patientMarkers = new Map();

        this.options = {
            defaultCenter: [24.7136, 46.6753], // Riyadh, Saudi Arabia
            defaultZoom: 13,
            ...options
        };

        this.tileProviders = {
            street: [
                'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png',
                'https://maps.wikimedia.org/osm-intl/{z}/{x}/{y}.png'
            ],
            satellite: [
                'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
                'https://services.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'
            ],
            terrain: [
                'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
                'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png',
                'https://maps.wikimedia.org/osm-intl/{z}/{x}/{y}.png'
            ]
        };  
    }

    async init() {
        try {
            await this.loadLeaflet();
            await this.loadRoutingMachine();
            this.createMap();
            await this.setUserLocation();
            await this.loadHospitals();
            this.setupEventListeners();
            this.setupMapControls();
        } catch (error) {
            console.error('Failed to initialize map:', error);
            this.showError('فشل في تحميل الخريطة. حاول تحديث الصفحة.');
        }
    }

    async getUserLocation() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocation not supported'));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    this.map.setView([this.userLocation.lat, this.userLocation.lng], 15);
                    resolve(this.userLocation);
                },
                (error) => {
                    console.warn('Geolocation failed:', error);
                    reject(error);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 300000
                }
            );
        });
    }

    async setUserLocation() {
        try {
            await this.getUserLocation();
        } catch (error) {
            this.userLocation = {
                lat: this.options.defaultCenter[0],
                lng: this.options.defaultCenter[1]
            };
            this.map.setView([this.userLocation.lat, this.userLocation.lng], this.options.defaultZoom);
            this.showLocationPermissionMessage();
        }
    }

    async loadLeaflet() {
        if (window.L) return;

        const sources = [
            '/js/leaflet.js'
        ];

        for (const src of sources) {
            try {
                await this.loadScript(src);
                if (window.L) return;
            } catch (error) {
                console.warn(`Failed to load Leaflet from ${src}:`, error);
            }
        }

        throw new Error('Failed to load Leaflet library');
    }

    async loadRoutingMachine() {
        if (window.L?.Routing) return;

        const sources = [
            '/js/leaflet-routing-machine.js'
        ];

        for (const src of sources) {
            try {
                await this.loadScript(src);
                if (window.L?.Routing) return;
            } catch (error) {
                console.warn(`Failed to load routing machine from ${src}:`, error);
            }
        }

        console.warn('Routing machine is not available');
    }

    loadScript(src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    createMap() {
        const mapElement = document.getElementById('map');
        if (!mapElement) {
            throw new Error('Map element not found');
        }

        this.map = L.map(mapElement, {
            center: this.options.defaultCenter,
            zoom: this.options.defaultZoom,
            zoomControl: true,
            attributionControl: true,
            preferCanvas: true
        });

        this.setMapType('street');
        this.map.whenReady(() => {
            this.map.invalidateSize(true);
        });
    }

    setMapType(type) {
        if (!this.map) return;

        if (this.currentLayer) {
            this.map.removeLayer(this.currentLayer);
        }

        const style = type || 'street';
        const palettes = {
            street: { bg: '#f8fafc', grid: '#d1d5db', line: '#9ca3af', text: '#475569' },
            satellite: { bg: '#111827', grid: '#1f2937', line: '#4b5563', text: '#f8fafc' },
            terrain: { bg: '#eef2e9', grid: '#c7d2be', line: '#7c8b6a', text: '#334155' }
        };
        const palette = palettes[style] || palettes.street;

        this.currentLayer = L.gridLayer({
            attribution: this.getAttribution(style),
            maxZoom: this.getMaxZoom(style),
            tileSize: 256,
            noWrap: false,
            createTile: (coords, done) => {
                const tile = document.createElement('canvas');
                tile.width = tile.height = 256;
                const ctx = tile.getContext('2d');

                ctx.fillStyle = palette.bg;
                ctx.fillRect(0, 0, 256, 256);

                ctx.strokeStyle = palette.grid;
                ctx.lineWidth = 1;
                for (let i = 0; i <= 256; i += 64) {
                    ctx.beginPath();
                    ctx.moveTo(i, 0);
                    ctx.lineTo(i, 256);
                    ctx.stroke();
                    ctx.beginPath();
                    ctx.moveTo(0, i);
                    ctx.lineTo(256, i);
                    ctx.stroke();
                }

                ctx.strokeStyle = palette.line;
                ctx.lineWidth = 3;
                ctx.beginPath();
                ctx.moveTo(24, 220);
                ctx.bezierCurveTo(80, 180, 140, 240, 216, 180);
                ctx.stroke();

                ctx.fillStyle = palette.text;
                ctx.font = '11px Arial, sans-serif';
                ctx.fillText(`${style.toUpperCase()} ${coords.z}/${coords.x}/${coords.y}`, 12, 16);

                done(null, tile);
                return tile;
            }
        }).addTo(this.map);
    }

    switchMapType(type) {
        this.setMapType(type);
    }

    getAttribution(type) {
        const attributions = {
            street: '© OpenStreetMap contributors',
            satellite: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
            terrain: 'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)'
        };
        return attributions[type] || attributions.street;
    }

    getMaxZoom(type) {
        const maxZooms = {
            street: 19,
            satellite: 19,
            terrain: 17
        };
        return maxZooms[type] || 19;
    }

    async getUserLocation() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocation not supported'));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    this.map.setView([this.userLocation.lat, this.userLocation.lng], 15);
                    resolve(this.userLocation);
                },
                (error) => {
                    console.warn('Geolocation failed:', error);
                    this.showLocationPermissionMessage();
                    reject(error);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 300000
                }
            );
        });
    }

    async loadHospitals() {
        try {
            if (!this.userLocation) {
                console.log('No user location available, using default location for hospitals');
                // Use default Riyadh location if geolocation failed
                this.userLocation = {
                    lat: 24.7136,
                    lng: 46.6753
                };
            }

            // Try API first with user's location
            const response = await fetch(`/api/hospitals/nearby?latitude=${this.userLocation.lat}&longitude=${this.userLocation.lng}`, {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                const payload = await response.json();
                this.hospitals = Array.isArray(payload.hospitals) ? payload.hospitals : [];
                console.log(`Loaded ${this.hospitals.length} hospitals from API (source: ${payload.source || 'unknown'})`);

                if (!Array.isArray(this.hospitals) || this.hospitals.length === 0) {
                    console.warn('No hospitals found nearby - dynamic search only, no static fallback');
                    this.hospitals = [];
                }
            } else {
                console.warn('API request failed - dynamic data only');
                this.hospitals = [];
            }
        } catch (error) {
            console.warn('Failed to load hospitals from API:', error);
            this.hospitals = [];
        }

        this.displayHospitals();
        this.addHospitalMarkers();
        this.drawNavigationToNearest();
    }

    updateHospitalInfo() {
        const nearestHospital = this.hospitals[0];
        if (nearestHospital) {
            const nameElement = document.getElementById('nearestHospitalName');
            const descElement = document.getElementById('navigationDescription');

            if (nameElement) nameElement.textContent = nearestHospital.name;
            if (descElement) descElement.textContent = 'نظام الملاحة مُفعّل لتوجيهك إلى أسرع طريق.';
        }
    }

    displayHospitals() {
        const container = document.querySelector('#hospitalsList');
        const countElement = document.querySelector('#hospitalCount');

        if (!container) return;

        // Update hospital count
        if (countElement) {
            countElement.textContent = `${this.hospitals.length} مواقع`;
        }

        // Clear existing hospitals
        container.innerHTML = '';

        if (this.hospitals.length === 0) {
            container.innerHTML = '<p class="text-sm text-slate-500 p-4">لا توجد مستشفيات متاحة في المنطقة</p>';
            return;
        }

        // Display hospitals
        this.hospitals.forEach((hospital, index) => {
            const hospitalElement = document.createElement('div');
            hospitalElement.className = 'p-4 rounded-3xl border border-slate-200 bg-white hover:bg-slate-50 transition cursor-pointer';
            hospitalElement.onclick = () => this.navigateToHospital(hospital);

            hospitalElement.innerHTML = `
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <h4 class="font-semibold text-slate-900 truncate">${hospital.name}</h4>
                            ${hospital.source === 'osm' ? '<span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">OSM</span>' : ''}
                        </div>
                        <p class="text-sm text-slate-600 mb-1">${hospital.type || 'مستشفى'}</p>
                        <div class="flex items-center gap-4 text-xs text-slate-500">
                            <span class="flex items-center gap-1">
                                <i class="fas fa-route"></i>
                                ${typeof hospital.distance === 'number' ? hospital.distance.toFixed(1) + ' كم' : hospital.distance}
                            </span>
                            <span class="flex items-center gap-1">
                                <i class="fas fa-clock"></i>
                                ${hospital.eta}
                            </span>
                        </div>
                        ${hospital.phone ? `<p class="text-xs text-slate-500 mt-1"><i class="fas fa-phone"></i> ${hospital.phone}</p>` : ''}
                    </div>
                    <div class="flex flex-col gap-1">
                        <button onclick="event.stopPropagation(); window.sanadakMap.navigateToHospital(${JSON.stringify(hospital).replace(/"/g, '&quot;')})"
                                class="inline-flex items-center gap-1 rounded-lg bg-blue-600 text-white px-3 py-1 text-xs hover:bg-blue-700 transition">
                            <i class="fas fa-directions"></i>
                            توجيه
                        </button>
                        ${hospital.phone ? `<button onclick="event.stopPropagation(); window.open('tel:${hospital.phone}')"
                                class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white text-slate-700 px-3 py-1 text-xs hover:bg-slate-50 transition">
                            <i class="fas fa-phone"></i>
                            اتصال
                        </button>` : ''}
                    </div>
                </div>
            `;

            container.appendChild(hospitalElement);
        });
    }

    navigateToHospital(hospital) {
        if (!this.userLocation) {
            this.showError('موقعك غير محدد. يرجى تفعيل تحديد الموقع.');
            return;
        }

        // Center map on hospital
        this.map.setView([hospital.lat, hospital.lng], 16);

        // Draw navigation route
        this.drawNavigationArrow(
            this.userLocation.lat,
            this.userLocation.lng,
            hospital.lat,
            hospital.lng
        );

        // Open Google Maps for navigation
        const url = `https://www.google.com/maps/dir/${this.userLocation.lat},${this.userLocation.lng}/${hospital.lat},${hospital.lng}`;
        window.open(url, '_blank');
    }

    addHospitalMarkers() {
        // Remove previous markers
        this.hospitalMarkers.forEach(marker => {
            this.map.removeLayer(marker);
        });
        this.hospitalMarkers = [];

        this.hospitals.forEach(hospital => {
            const icon = L.divIcon({
                className: 'hospital-marker',
                html: '<div class="bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold">🏥</div>',
                iconSize: [32, 32],
                iconAnchor: [16, 16]
            });

            const marker = L.marker([hospital.lat, hospital.lng], { icon }).addTo(this.map);
            marker.bindPopup(`
                <strong>${hospital.name}</strong><br>
                ${hospital.distance} · ${hospital.eta}<br>
                <small>${hospital.address}</small>
            `);
            this.hospitalMarkers.push(marker);
        });
    }

    drawNavigationToNearest() {
        if (!this.userLocation || !this.hospitals.length) return;

        const nearestHospital = this.hospitals[0];
        this.drawNavigationArrow(
            this.userLocation.lat,
            this.userLocation.lng,
            nearestHospital.lat,
            nearestHospital.lng
        );
    }

    drawNavigationArrow(userLat, userLng, hospitalLat, hospitalLng) {
        if (!this.map) return;

        // Remove existing route
        if (this.navigationArrow) {
            if (this.navigationArrow.routingControl) {
                this.map.removeControl(this.navigationArrow.routingControl);
            }
            if (this.navigationArrow.arrowMarker) {
                this.map.removeLayer(this.navigationArrow.arrowMarker);
            }
            if (this.navigationArrow.polyline) {
                this.map.removeLayer(this.navigationArrow.polyline);
            }
        }

        // Use a direct fallback route with Leaflet only, without external OSRM requests.
        this.drawFallbackRoute(userLat, userLng, hospitalLat, hospitalLng);
    }

    drawFallbackRoute(userLat, userLng, hospitalLat, hospitalLng) {
        if (!this.map) return;

        if (this.navigationArrow && this.navigationArrow.routingControl) {
            this.map.removeControl(this.navigationArrow.routingControl);
        }
        if (this.navigationArrow && this.navigationArrow.polyline) {
            this.map.removeLayer(this.navigationArrow.polyline);
        }

        const routePoints = [[userLat, userLng], [hospitalLat, hospitalLng]];
        this.navigationArrow = {
            polyline: L.polyline(routePoints, {
                color: '#ef4444',
                weight: 6,
                opacity: 0.8
            }).addTo(this.map)
        };

        // Add markers
        const startIcon = L.divIcon({
            className: 'routing-marker start-marker',
            html: '<div class="bg-blue-500 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold">أ</div>',
            iconSize: [32, 32],
            iconAnchor: [16, 16]
        });
        const endIcon = L.divIcon({
            className: 'routing-marker end-marker',
            html: '<div class="bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm">🏥</div>',
            iconSize: [32, 32],
            iconAnchor: [16, 16]
        });

        L.marker([userLat, userLng], { icon: startIcon }).addTo(this.map);
        L.marker([hospitalLat, hospitalLng], { icon: endIcon }).addTo(this.map);
    }

    setupEventListeners() {
        // Map type switchers
        document.getElementById('map-street')?.addEventListener('click', () => this.switchMapType('street'));
        document.getElementById('map-satellite')?.addEventListener('click', () => this.switchMapType('satellite'));
        document.getElementById('map-terrain')?.addEventListener('click', () => this.switchMapType('terrain'));

        // Navigation button for nearest hospital
        document.getElementById('navigateNearest')?.addEventListener('click', () => {
            if (this.hospitals.length > 0) {
                this.navigateToHospital(this.hospitals[0]);
            } else {
                this.showError('لا توجد مستشفيات متاحة للتوجيه');
            }
        });

        // AI-powered hospital search
        document.getElementById('searchHospitalsAI')?.addEventListener('click', () => {
            this.searchHospitalsWithAI();
        });
    }

    async searchHospitalsWithAI() {
        if (!this.userLocation) {
            this.showError('يجب تحديد موقعك أولاً للبحث عن المستشفيات');
            return;
        }

        try {
            this.showLoading('جاري البحث عن المستشفيات بالذكاء الاصطناعي...');

            const response = await fetch('/api/search-hospitals-ai', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    latitude: this.userLocation.lat,
                    longitude: this.userLocation.lng,
                    location: 'الرياض، المملكة العربية السعودية'
                })
            });

            if (response.ok) {
                const payload = await response.json();
                if (payload.success && payload.hospitals) {
                    this.hospitals = payload.hospitals;
                    this.displayHospitals();
                    this.addHospitalMarkers();
                    this.drawNavigationToNearest();
                    this.showSuccess('تم العثور على المستشفيات باستخدام الذكاء الاصطناعي');
                } else {
                    this.showError('فشل في البحث بالذكاء الاصطناعي');
                }
            } else {
                this.showError('فشل في الاتصال بخدمة البحث');
            }
        } catch (error) {
            console.error('AI Hospital Search Error:', error);
            this.showError('حدث خطأ أثناء البحث');
        } finally {
            this.hideLoading();
        }
    }

    showLoading(message) {
        const loading = document.createElement('div');
        loading.id = 'loading-overlay';
        loading.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        loading.innerHTML = `
            <div class="bg-white rounded-3xl p-6 flex items-center gap-4">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="text-slate-700">${message}</p>
            </div>
        `;
        document.body.appendChild(loading);
    }

    hideLoading() {
        const loading = document.getElementById('loading-overlay');
        if (loading) {
            loading.remove();
        }
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-3xl shadow-lg z-50 ${
            type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' :
            type === 'error' ? 'bg-red-100 text-red-800 border border-red-200' :
            'bg-blue-100 text-blue-800 border border-blue-200'
        }`;
        notification.innerHTML = `
            <div class="flex items-center gap-2">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
                <p>${message}</p>
            </div>
        `;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    setupMapControls() {
        // Add patient markers simulation
        this.addPatientMarkers();
    }

    addPatientMarkers() {
        // Simulate patient locations
        const patients = [
            { id: 1, name: 'أحمد محمد', phone: '+966501234567', address: 'الرياض' },
            { id: 2, name: 'فاطمة علي', phone: '+966507654321', address: 'جدة' }
        ];

        patients.forEach(patient => {
            const lat = (this.userLocation?.lat || 24.7136) + (Math.random() - 0.5) * 0.1;
            const lng = (this.userLocation?.lng || 46.6753) + (Math.random() - 0.5) * 0.1;
            const hasActiveSeizure = Math.random() > 0.7;

            const marker = L.circleMarker([lat, lng], {
                radius: 9,
                fillColor: hasActiveSeizure ? '#ef4444' : '#10b981',
                color: '#ffffff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.9
            }).addTo(this.map);

            marker.bindPopup(`<strong>${patient.name}</strong><br>${patient.phone}<br>${patient.address}`);
            this.patientMarkers.set(patient.id, { marker, lat, lng });
        });

        // Update patient locations periodically
        setInterval(() => {
            this.patientMarkers.forEach(patientEntry => {
                const newLat = patientEntry.lat + (Math.random() - 0.5) * 0.0006;
                const newLng = patientEntry.lng + (Math.random() - 0.5) * 0.0006;
                patientEntry.lat = newLat;
                patientEntry.lng = newLng;
                patientEntry.marker.setLatLng([newLat, newLng]);
            });
        }, 6000);
    }

    showLocationPermissionMessage() {
        const message = document.createElement('div');
        message.className = 'p-4 rounded-3xl bg-yellow-50 text-yellow-700 border border-yellow-200 mt-4';
        message.innerHTML = `
            <div class="flex items-center gap-2">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>تفعيل الموقع مطلوب</strong>
                    <p class="text-sm mt-1">يرجى تفعيل إذن تحديد الموقع في المتصفح لرؤية المستشفيات القريبة وموقعك الحالي على الخريطة.</p>
                </div>
            </div>
        `;
        document.querySelector('.max-w-5xl')?.prepend(message);
    }

    showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'absolute inset-0 bg-gray-100 flex items-center justify-center z-10';
        errorDiv.innerHTML = `
            <div class="text-center p-4">
                <i class="fas fa-exclamation-triangle text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">خطأ في تحميل الخريطة</h3>
                <p class="text-gray-600">${message}</p>
                <button onclick="window.location.reload()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    إعادة المحاولة
                </button>
            </div>
        `;
        document.getElementById('map').appendChild(errorDiv);
    }

    getAuthToken() {
        const storage = window.AppUtils?.storage;
        return storage ? storage.getItem('access_token') || storage.getItem('authToken') : null;
    }
}

// Initialize map when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.sanadakMap = new SanadakMap();
    window.sanadakMap.init().catch(console.error);
});

// Export for global access
window.SanadakMap = SanadakMap;