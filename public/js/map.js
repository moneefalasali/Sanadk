// map.js - Map functionality for SANADK
// Production-ready map implementation with error handling

class SanadakMap {
    constructor(options = {}) {
        this.map = null;
        this.currentLayer = null;
        this.userLocation = null;
        this.hospitals = [];
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
                'https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png',
                'https://tiles.wmflabs.org/bw-mapnik/{z}/{x}/{y}.png'
            ],
            satellite: [
                'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
                'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}'
            ],
            terrain: [
                'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
                'https://server.arcgisonline.com/ArcGIS/rest/services/World_Terrain_Base/MapServer/tile/{z}/{y}/{x}',
                'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'
            ]
        };
    }

    async init() {
        try {
            await this.loadLeaflet();
            await this.loadRoutingMachine();
            this.createMap();
            await this.getUserLocation();
            await this.loadHospitals();
            this.setupEventListeners();
            this.setupMapControls();
        } catch (error) {
            console.error('Failed to initialize map:', error);
            this.showError('فشل في تحميل الخريطة. حاول تحديث الصفحة.');
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
            attributionControl: true
        });

        this.setMapType('street');
    }

    setMapType(type) {
        if (!this.map) return;

        // Remove current layer
        if (this.currentLayer) {
            this.map.removeLayer(this.currentLayer);
        }

        const providers = this.tileProviders[type] || this.tileProviders.street;
        let currentProviderIndex = 0;

        const tryNextProvider = () => {
            if (currentProviderIndex >= providers.length) {
                console.error('All tile providers failed for type:', type);
                this.showError('فشل في تحميل الخريطة. تحقق من اتصالك بالإنترنت.');
                return;
            }

            const providerUrl = providers[currentProviderIndex];
            console.log(`Trying tile provider ${currentProviderIndex + 1}/${providers.length}:`, providerUrl);

            this.currentLayer = L.tileLayer(providerUrl, {
                attribution: this.getAttribution(type),
                maxZoom: this.getMaxZoom(type),
                errorTileUrl: '/img/error-tile.png',
                crossOrigin: 'anonymous'
            });

            this.currentLayer.on('tileerror', (e) => {
                console.warn('Tile failed to load:', e);
                setTimeout(() => {
                    currentProviderIndex++;
                    tryNextProvider();
                }, 1000);
            });

            this.currentLayer.addTo(this.map);
        };

        tryNextProvider();
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
            // Try API first
            const response = await fetch('/api/hospitals/nearby', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                const payload = await response.json();
                this.hospitals = Array.isArray(payload) ? payload : payload.hospitals || [];
                if (!Array.isArray(this.hospitals) || this.hospitals.length === 0) {
                    this.hospitals = this.getFallbackHospitals();
                }
            } else {
                // Fallback to static data
                this.hospitals = this.getFallbackHospitals();
            }
        } catch (error) {
            console.warn('Failed to load hospitals from API:', error);
            this.hospitals = this.getFallbackHospitals();
        }

        this.displayHospitals();
        this.addHospitalMarkers();
        this.drawNavigationToNearest();
    }

    getFallbackHospitals() {
        return [
            {
                name: 'مستشفى الملك فيصل التخصصي',
                lat: 24.7133,
                lng: 46.6840,
                distance: '2.5 كم',
                eta: '8 دقائق',
                address: 'الرياض، المملكة العربية السعودية'
            },
            {
                name: 'مستشفى الحرس الوطني',
                lat: 24.7040,
                lng: 46.6908,
                distance: '3.8 كم',
                eta: '12 دقيقة',
                address: 'الرياض، المملكة العربية السعودية'
            },
            {
                name: 'مدينة الملك عبدالعزيز الطبية',
                lat: 24.6969,
                lng: 46.7500,
                distance: '5.2 كم',
                eta: '15 دقيقة',
                address: 'الرياض، المملكة العربية السعودية'
            }
        ];
    }

    displayHospitals() {
        const container = document.querySelector('.max-w-5xl');
        if (!container) return;

        // Update hospital count and nearest hospital info
        this.updateHospitalInfo();
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

    addHospitalMarkers() {
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

        // Try to use routing machine if available
        if (window.L.Routing && window.L.Routing.control) {
            this.navigationArrow = L.Routing.control({
                waypoints: [
                    L.latLng(userLat, userLng),
                    L.latLng(hospitalLat, hospitalLng)
                ],
                routeWhileDragging: false,
                createMarker: (i, waypoint, n) => {
                    if (i === 0) {
                        return L.marker(waypoint.latLng, {
                            icon: L.divIcon({
                                className: 'routing-marker start-marker',
                                html: '<div class="bg-blue-500 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold">أ</div>',
                                iconSize: [32, 32],
                                iconAnchor: [16, 16]
                            })
                        });
                    } else if (i === n-1) {
                        return L.marker(waypoint.latLng, {
                            icon: L.divIcon({
                                className: 'routing-marker end-marker',
                                html: '<div class="bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm">🏥</div>',
                                iconSize: [32, 32],
                                iconAnchor: [16, 16]
                            })
                        });
                    }
                    return null;
                },
                lineOptions: {
                    styles: [{ color: '#ef4444', weight: 6, opacity: 0.8 }]
                },
                show: false,
                addWaypoints: false
            }).addTo(this.map);
        } else {
            // Fallback: simple line with markers
            console.log('Using fallback routing (no routing machine)');

            const routePoints = [[userLat, userLng], [hospitalLat, hospitalLng]];
            this.navigationArrow = L.polyline(routePoints, {
                color: '#ef4444',
                weight: 6,
                opacity: 0.8
            }).addTo(this.map);

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
    }

    setupEventListeners() {
        // Map type switchers
        document.getElementById('map-street')?.addEventListener('click', () => this.switchMapType('street'));
        document.getElementById('map-satellite')?.addEventListener('click', () => this.switchMapType('satellite'));
        document.getElementById('map-terrain')?.addEventListener('click', () => this.switchMapType('terrain'));

        // Navigation button
        document.getElementById('navigateNearest')?.addEventListener('click', () => {
            if (this.hospitals.length > 0) {
                const nearestHospital = this.hospitals[0];
                this.map.setView([nearestHospital.lat, nearestHospital.lng], 16);
                const url = `https://www.google.com/maps/dir/${this.userLocation.lat},${this.userLocation.lng}/${nearestHospital.lat},${nearestHospital.lng}`;
                window.open(url, '_blank');
            }
        });
    }

    switchMapType(type) {
        this.setMapType(type);

        // Update button states
        document.querySelectorAll('[id^="map-"]').forEach(btn => {
            btn.classList.remove('active', 'bg-white', 'text-slate-700');
            btn.classList.add('text-slate-600');
        });
        document.getElementById(`map-${type}`).classList.add('active', 'bg-white', 'text-slate-700');

        // Re-add navigation arrow
        if (this.navigationArrow && this.hospitals.length > 0) {
            const nearestHospital = this.hospitals[0];
            this.drawNavigationArrow(
                this.userLocation.lat,
                this.userLocation.lng,
                nearestHospital.lat,
                nearestHospital.lng
            );
        }
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