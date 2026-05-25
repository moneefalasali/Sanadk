<x-app-layout>
    <link rel="stylesheet" href="{{ asset('css/modern-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fontawesome.css') }}">

    @include('components.header-nav', ['title' => 'الأجهزة المتصلة'])

    <div class="p-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div class="flex items-center gap-4">
                <a href="{{ route('dashboard') }}" class="text-gray-600"><i class="fas fa-chevron-right"></i></a>
                <div>
                    <h2 class="text-xl font-bold">الأجهزة المتصلة</h2>
                    <p class="text-sm text-gray-500">راقب حالة الأجهزة الطبية الحية، واستخدم وضع المحاكاة عندما لا يتوفر جهاز فعلي.</p>
                </div>
            </div>
            <div class="flex gap-3 flex-wrap">
                <form action="{{ route('devices.simulate') }}" method="POST" class="inline-block">
                    @csrf
                    <button type="submit" class="btn-modern px-5 py-3">تشغيل وضع المحاكاة</button>
                </form>
                <button id="stopSimulationBtn" type="button" class="btn-modern-secondary px-5 py-3">إيقاف وضع المحاكاة</button>
                <a href="{{ route('devices.polar.connect') }}" class="btn-modern px-5 py-3 bg-sky-600 hover:bg-sky-700 text-white">ربط حساب Polar</a>
                <button id="analyzeBtn" class="btn-modern-secondary px-5 py-3">تحليل البيانات</button>
            </div>
            @if(auth()->user()->polar_owner_id)
                <div class="mt-3 text-sm text-green-700">تم ربط حساب Polar بنجاح. سيتم مزامنة بيانات جهازك عبر الخدمة السحابية.</div>
            @endif
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded-3xl mb-6">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-white p-5 rounded-3xl shadow-sm">
                <p class="text-sm text-gray-500 mb-2">الأجهزة المتصلة</p>
                <p id="connectedCountValue" class="text-3xl font-bold text-blue-600">{{ $connectedCount }}</p>
            </div>
            <div class="bg-white p-5 rounded-3xl shadow-sm">
                <p class="text-sm text-gray-500 mb-2">متوسط البطارية</p>
                <p id="batteryAverageValue" class="text-3xl font-bold text-emerald-600">{{ round($batteryAverage) }}%</p>
            </div>
            <div class="bg-white p-5 rounded-3xl shadow-sm">
                <p class="text-sm text-gray-500 mb-2">أنواع الأجهزة</p>
                <p id="deviceTypesValue" class="text-3xl font-bold text-orange-600">{{ $deviceTypes->count() }}</p>
            </div>
        </div>

        <div class="bg-white p-5 rounded-3xl shadow-sm mb-6">
            @php
                $devicesRiskScore = is_numeric($riskScore) ? (int) round($riskScore) : 0;
                $devicesRiskLabel = $devicesRiskScore >= 70 ? 'خطر عالي' : ($devicesRiskScore >= 40 ? 'خطر متوسط' : 'خطر منخفض');
            @endphp
            <p class="text-sm text-gray-500 mb-2">مؤشر الخطر الحالي</p>
            <div id="devicesRiskProgress" class="circular-progress mx-auto mb-3" style="background: conic-gradient(var(--primary) {{ $devicesRiskScore }}%, #EDF2F7 0deg); width: 140px; height: 140px;">
                <div id="devicesRiskValue" class="progress-value">{{ $devicesRiskScore }}</div>
            </div>
            <p id="devicesRiskLabel" class="text-center text-sm text-gray-600">{{ $devicesRiskLabel }}</p>
        </div>

        <div class="bg-white p-5 rounded-3xl shadow-sm mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <p class="text-sm text-gray-500 mb-2">حالة البث المباشر</p>
                    <p id="liveRealtimeStatusText" class="font-bold text-lg">جاري الاتصال...</p>
                </div>
                <span id="liveRealtimeBadge" class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">جاري الاتصال...</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-4">
                <div class="bg-gray-50 p-4 rounded-2xl">
                    <p class="text-xs text-gray-500">حالة EEG</p>
                    <p id="liveEegStatus" class="font-bold text-sm mt-1">غير معروف</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-2xl">
                    <p class="text-xs text-gray-500">آخر تحديث</p>
                    <p id="liveLastUpdated" class="font-bold text-sm mt-1">--</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-2xl">
                    <p class="text-xs text-gray-500">مؤشر الخطر</p>
                    <p id="liveRiskScore" class="font-bold text-sm mt-1">{{ $devicesRiskScore }}</p>
                </div>
            </div>
        </div>

        <!-- Analysis Results -->
        <div id="analysisResults" class="hidden bg-white p-6 rounded-3xl shadow-sm border border-gray-100 mb-6">
            <h3 class="font-bold text-xl mb-4">نتائج التحليل الذكي</h3>
            <div id="analysisContent">
                <!-- Analysis results will be loaded here -->
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="space-y-4">
                @forelse($devices as $device)
                    @php
                        $deviceIconClass = match($device->type) {
                            'eeg' => 'fa-wave-square',
                            'ecg' => 'fa-heartbeat',
                            'emg' => 'fa-bolt',
                            default => 'fa-microchip',
                        };
                    @endphp
                    <div class="device-card bg-white p-6 rounded-3xl shadow-sm border border-gray-100 hover:shadow-md transition"
                         data-device-id="{{ $device->id }}"
                         data-device-name="{{ $device->name }}"
                         data-device-type="{{ $device->type }}"
                         data-device-status="{{ $device->is_connected ? 'connected' : 'disconnected' }}"
                         data-device-battery="{{ $device->battery_level ?? 0 }}">
                        <div class="flex flex-col sm:flex-row items-center gap-5">
                            <div class="w-24 h-24 bg-gray-50 rounded-3xl flex items-center justify-center p-3 text-blue-600">
                                <i class="fas {{ $deviceIconClass }} fa-3x"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between gap-3 mb-3">
                                    <div>
                                        <h4 class="font-bold text-lg">{{ $device->name }}</h4>
                                        <p class="text-sm text-gray-500">{{ $device->type_label }}</p>
                                    </div>
                                    <span id="deviceStatus-{{ $device->id }}" class="text-xs font-semibold uppercase {{ $device->is_connected ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ $device->is_connected ? 'متصل' : 'غير متصل' }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div class="space-y-2">
                                        <p class="text-xs text-gray-400">مستوى البطارية</p>
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 h-2 rounded-full bg-gray-100 overflow-hidden">
                                                <div id="deviceBatteryBar-{{ $device->id }}" class="h-full {{ $device->battery_level > 30 ? 'bg-emerald-500' : 'bg-rose-500' }}" style="width: {{ $device->battery_level ?? 0 }}%"></div>
                                            </div>
                                            <span id="deviceBatteryText-{{ $device->id }}" class="text-xs font-semibold">{{ $device->battery_level ?? 0 }}%</span>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <p class="text-xs text-gray-400">إشارة حية</p>
                                        <div class="flex items-center gap-2">
                                            <span id="deviceSignalDot-{{ $device->id }}" class="h-2 w-2 rounded-full {{ $device->is_connected ? 'bg-emerald-500 animate-pulse' : 'bg-gray-300' }}"></span>
                                            <span id="deviceSignalText-{{ $device->id }}" class="text-xs text-gray-600">{{ $device->is_connected ? 'نشطة' : 'منقطعة' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 text-xs text-gray-500">
                                    آخر تحديث: <span id="deviceLastUpdated-{{ $device->id }}">{{ $device->updated_at->diffForHumans() }}</span>
                                </div>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <button class="btn-modern-secondary toggle-simulation" data-device-id="{{ $device->id }}" data-current-mode="{{ $device->simulation_mode ? 'true' : 'false' }}">
                                        <i class="fas fa-toggle-{{ $device->simulation_mode ? 'on' : 'off' }}"></i>
                                        {{ $device->simulation_mode ? 'إيقاف المحاكاة' : 'تشغيل المحاكاة' }}
                                    </button>
                                    <button class="btn-modern-secondary view-data" data-device-id="{{ $device->id }}">
                                        <i class="fas fa-chart-line"></i>
                                        عرض البيانات
                                    </button>
                                    <button class="btn-modern-secondary web-bluetooth-connect" data-device-id="{{ $device->id }}">اتصال عبر Chrome</button>
                                    <button class="btn-modern-secondary">تفاصيل الاتصال</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-white p-8 rounded-3xl shadow-sm text-center">
                        <i class="fas fa-signal text-5xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500 mb-3">لا توجد أجهزة متاحة للمراقبة.</p>
                        <p class="text-gray-400">اضغط على زر "تشغيل وضع المحاكاة" لإنشاء أجهزة افتراضية أو أضف جهازًا جديدًا.</p>
                    </div>
                @endforelse
            </div>

            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                <h3 class="font-bold text-xl mb-4">أضف جهاز مراقبة</h3>
                <p class="text-sm text-gray-500 mb-6">اجعل النظام يتصل بجهاز EEG أو ECG أو EMG ليبدأ تحليل الإشارات الحية.</p>
                <form action="{{ route('devices.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">اسم الجهاز</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="mt-1 block w-full rounded-2xl border-gray-300 shadow-sm px-4 py-3" placeholder="مثلاً Emotiv EPOC X" required>
                        @error('name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">نوع الجهاز</label>
                        <select name="type" class="mt-1 block w-full rounded-2xl border-gray-300 shadow-sm px-4 py-3" required>
                            <option value="">اختر نوع الجهاز</option>
                            <option value="eeg" {{ old('type') === 'eeg' ? 'selected' : '' }}>EEG - Emotiv</option>
                            <option value="ecg" {{ old('type') === 'ecg' ? 'selected' : '' }}>ECG - Polar</option>
                            <option value="emg" {{ old('type') === 'emg' ? 'selected' : '' }}>EMG - ESP32</option>
                        </select>
                        @error('type')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="btn-modern w-full">إضافة الجهاز</button>
                </form>
                <div class="mt-6 text-xs text-gray-400">
                    يعمل هذا القسم كواجهة لإدارة الأجهزة المتصلة بالنظام الذكي. عند إضافة جهاز، يمكن للنظام البدء بتتبع حالة البطارية والوضعية.
                </div>
            </div>
        </div>

        <div class="mt-8 text-sm text-gray-500">
            <p class="font-semibold mb-2">ملاحظات نظام SANADK الذكي</p>
            <ul class="list-disc list-inside space-y-1">
                <li>الأجهزة المتصلة ترسل إشارات حية يتم تحليلها بواسطة خوارزميات الذكاء الاصطناعي.</li>
                <li>وضع المحاكاة ينشئ أجهزة وهمية لتمكين تجربة التطبيق دون أجهزة فعلية.</li>
                <li>تأكد من وجود اتصال مستمر لتفعيل تنبيهات الطوارئ والتنبؤات في الوقت الحقيقي.</li>
            </ul>
        </div>
    </div>

    <!-- Device Data Modal -->
    <div id="deviceDataModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-xl font-bold" id="modalTitle">بيانات الجهاز</h3>
                        <button id="closeModal" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>
                <div class="p-6" id="modalContent">
                    <!-- Device data will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    @include('components.bottom-nav', ['active' => ''])

    <script>
        document.getElementById('analyzeBtn').addEventListener('click', function() {
            const btn = this;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري التحليل...';
            btn.disabled = true;

            fetch('{{ route("devices.analyze") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                displayAnalysisResults(data);
                document.getElementById('analysisResults').classList.remove('hidden');
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء التحليل. تأكد من وجود أجهزة متصلة.');
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        });

        document.getElementById('stopSimulationBtn').addEventListener('click', async function() {
            try {
                const response = await fetch('{{ route('devices.stop-simulation') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Error stopping simulation:', error);
                alert('فشل في إيقاف المحاكاة. حاول مرة أخرى.');
            }
        });

        function displayAnalysisResults(data) {
            const content = document.getElementById('analysisContent');

            if (data.error) {
                content.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-exclamation-triangle text-4xl text-yellow-500 mb-4"></i>
                        <p class="text-gray-600">${data.error}</p>
                    </div>
                `;
                return;
            }

            let html = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-gradient-to-r from-blue-50 to-blue-100 p-4 rounded-2xl">
                        <h4 class="font-bold text-lg mb-2">النشاط الحالي</h4>
                        <p class="text-2xl font-bold text-blue-600">${getActivityText(data.activity)}</p>
                    </div>
                    <div class="bg-gradient-to-r ${getRiskColor(data.risk_level)} p-4 rounded-2xl">
                        <h4 class="font-bold text-lg mb-2">مستوى الخطر</h4>
                        <p class="text-2xl font-bold ${getRiskTextColor(data.risk_level)}">${getRiskText(data.risk_level)}</p>
                    </div>
                </div>

                <div class="mb-6">
                    <h4 class="font-bold text-lg mb-3">التوصيات</h4>
                    <div class="space-y-2">
            `;

            data.recommendations.forEach(rec => {
                html += `<div class="bg-yellow-50 border border-yellow-200 p-3 rounded-xl"><i class="fas fa-lightbulb text-yellow-600 mr-2"></i>${rec}</div>`;
            });

            html += `
                    </div>
                </div>
            `;

            if (data.nearest_hospitals) {
                html += `
                    <div class="mb-6">
                        <h4 class="font-bold text-lg mb-3">أقرب المستشفيات</h4>
                        <div class="space-y-3">
                `;

                data.nearest_hospitals.forEach(hospital => {
                    html += `
                        <div class="bg-white border border-gray-200 p-4 rounded-xl">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h5 class="font-bold text-lg">${hospital.name}</h5>
                                    <p class="text-gray-600 text-sm">${hospital.address}</p>
                                </div>
                                <div class="text-left">
                                    <p class="text-sm text-gray-500">المسافة: ${hospital.distance}</p>
                                    <p class="text-sm text-gray-500">الوقت: ${hospital.eta}</p>
                                </div>
                            </div>
                        </div>
                    `;
                });

                html += `
                        </div>
                    </div>
                `;
            }

            content.innerHTML = html;
        }

        function getActivityText(activity) {
            const activities = {
                'walking': 'المشي',
                'running': 'الجري',
                'resting': 'الراحة',
                'sleeping': 'النوم',
                'driving': 'القيادة',
                'working': 'العمل'
            };
            return activities[activity] || activity;
        }

        function getRiskText(risk) {
            const risks = {
                'low': 'منخفض',
                'medium': 'متوسط',
                'high': 'عالي'
            };
            return risks[risk] || risk;
        }

        function getRiskColor(risk) {
            const colors = {
                'low': 'from-green-50 to-green-100',
                'medium': 'from-yellow-50 to-yellow-100',
                'high': 'from-red-50 to-red-100'
            };
            return colors[risk] || 'from-gray-50 to-gray-100';
        }

        function getRiskTextColor(risk) {
            const colors = {
                'low': 'text-green-600',
                'medium': 'text-yellow-600',
                'high': 'text-red-600'
            };
            return colors[risk] || 'text-gray-600';
        }

        // Handle simulation toggle
        document.querySelectorAll('.toggle-simulation').forEach(btn => {
            btn.addEventListener('click', function() {
                const deviceId = this.dataset.deviceId;
                const currentMode = this.dataset.currentMode === 'true';
                const newMode = !currentMode;

                fetch('{{ route("devices.toggle-simulation") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        device_id: deviceId,
                        simulation_mode: newMode
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.dataset.currentMode = data.simulation_mode ? 'true' : 'false';
                        this.innerHTML = `<i class="fas fa-toggle-${data.simulation_mode ? 'on' : 'off'}"></i> ${data.simulation_mode ? 'إيقاف المحاكاة' : 'تشغيل المحاكاة'}`;
                        this.classList.toggle('bg-blue-600', data.simulation_mode);
                        this.classList.toggle('text-white', data.simulation_mode);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('حدث خطأ في تغيير وضع المحاكاة.');
                });
            });
        });

        // Handle view data
        document.querySelectorAll('.view-data').forEach(btn => {
            btn.addEventListener('click', function() {
                const deviceId = this.dataset.deviceId;
                showDeviceData(deviceId);
            });
        });

        // Modal management
        document.getElementById('closeModal').addEventListener('click', function() {
            document.getElementById('deviceDataModal').classList.add('hidden');
        });

        document.getElementById('deviceDataModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });

        function showDeviceData(deviceId) {
            fetch(`{{ url('/devices') }}/${deviceId}/data`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayDeviceData(data);
                    document.getElementById('deviceDataModal').classList.remove('hidden');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ في تحميل بيانات الجهاز.');
            });
        }

        function displayDeviceData(data) {
            const device = data.device;
            const deviceData = data.data;

            let html = `
                <div class="mb-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center text-blue-600">
                            ${getDeviceIconHTML(device.type)}
                        </div>
                        <div>
                            <h4 class="font-bold text-lg">${device.name}</h4>
                            <p class="text-gray-600">${device.type.toUpperCase()}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="bg-gray-50 p-3 rounded-xl">
                            <p class="text-sm text-gray-500">حالة البطارية</p>
                            <p class="font-bold">${device.battery_level}%</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-xl">
                            <p class="text-sm text-gray-500">آخر تحديث</p>
                            <p class="font-bold">${device.last_updated}</p>
                        </div>
                    </div>
                </div>
            `;

            if (deviceData) {
                html += '<div class="space-y-4">';
                html += '<h5 class="font-bold text-lg">البيانات الحالية</h5>';

                Object.keys(deviceData).forEach(key => {
                    const value = deviceData[key];
                    const label = getDataLabel(key, device.type);

                    html += `
                        <div class="bg-blue-50 border border-blue-200 p-4 rounded-xl">
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-blue-800">${label}</span>
                                <span class="font-bold text-blue-900">${formatDataValue(key, value)}</span>
                            </div>
                        </div>
                    `;
                });

                html += '</div>';
            } else {
                html += `
                    <div class="text-center py-8">
                        <i class="fas fa-database text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">لا توجد بيانات متاحة لهذا الجهاز</p>
                    </div>
                `;
            }

            document.getElementById('modalContent').innerHTML = html;
        }

        function getDeviceIconHTML(type) {
            const icons = {
                'eeg': 'fa-wave-square',
                'ecg': 'fa-heartbeat',
                'emg': 'fa-bolt'
            };
            const iconClass = icons[type] || 'fa-microchip';
            return `<i class="fas ${iconClass} fa-2x text-blue-600"></i>`;
        }

        function getDataLabel(key, deviceType) {
            const labels = {
                'eeg': {
                    'alpha': 'موجات ألفا (Alpha)',
                    'beta': 'موجات بيتا (Beta)',
                    'theta': 'موجات ثيتا (Theta)',
                    'delta': 'موجات دلتا (Delta)',
                    'activity_level': 'مستوى النشاط'
                },
                'ecg': {
                    'heart_rate': 'معدل النبض',
                    'blood_pressure_systolic': 'ضغط الدم الانقباضي',
                    'blood_pressure_diastolic': 'ضغط الدم الانبساطي',
                    'oxygen_saturation': 'تشبع الأكسجين'
                },
                'emg': {
                    'tension': 'توتر العضلات',
                    'muscle_activity': 'نشاط العضلات',
                    'nerve_signals': 'إشارات الأعصاب'
                }
            };

            return labels[deviceType]?.[key] || key;
        }

        function formatDataValue(key, value) {
            if (typeof value === 'number') {
                if (key.includes('rate') || key.includes('tension') || key.includes('activity') || key.includes('signals')) {
                    return value + (key.includes('rate') ? ' نبضة/دقيقة' : ' %');
                }
                if (key.includes('pressure')) {
                    return value + ' mmHg';
                }
                if (key.includes('saturation')) {
                    return value + ' %';
                }
            }
            return value;
        }
    </script>

    @php
        $reverbHost = config('broadcasting.connections.reverb.host', request()->getHost());
        $reverbPort = (int) config('broadcasting.connections.reverb.port', 6001);
        $reverbScheme = config('broadcasting.connections.reverb.scheme', request()->getScheme());
        $reverbUseTls = config('broadcasting.connections.reverb.useTLS', $reverbScheme === 'https') ? 'true' : 'false';
        $reverbKey = config('broadcasting.connections.reverb.key');
    @endphp

    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.3/dist/echo.iife.js"></script>
    <script>
        if (typeof window.Echo === 'undefined' && typeof Echo !== 'undefined') {
            window.Echo = new Echo({
                broadcaster: 'pusher',
                key: '{{ $reverbKey }}',
                wsHost: '{{ $reverbHost }}',
                wsPort: {{ $reverbPort }},
                wssPort: {{ $reverbPort }},
                forceTLS: {{ $reverbUseTls }},
                enabledTransports: ['ws', 'wss'],
                disableStats: true,
                authEndpoint: '/broadcasting/auth',
            });
        }
    </script>

    <script>
        const devicesPageCacheKey = 'sanadak-devices-live-cache';
        const patientId = {{ auth()->id() }};
        const deviceDataEndpoints = @json($devices->mapWithKeys(fn($device) => [$device->id => route('devices.data', $device->id)]));

        function setRealtimeBadge(status, label) {
            const badge = document.getElementById('liveRealtimeBadge');
            const statusText = document.getElementById('liveRealtimeStatusText');

            if (badge) {
                badge.textContent = label;
                badge.className = 'px-3 py-1 rounded-full text-xs font-semibold';
                if (status === 'connected') {
                    badge.classList.add('bg-emerald-100', 'text-emerald-700');
                } else if (status === 'warning') {
                    badge.classList.add('bg-amber-100', 'text-amber-700');
                } else {
                    badge.classList.add('bg-rose-100', 'text-rose-700');
                }
            }

            if (statusText) {
                statusText.textContent = label;
            }
        }

        function formatLiveTimestamp(value) {
            if (!value) return '--';
            if (typeof value === 'string' && /ago|قبل|منذ/.test(value)) return value;
            const parsed = new Date(value);
            if (Number.isNaN(parsed.getTime())) return value;
            return parsed.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }

        function updateSummaryCards() {
            const cards = Array.from(document.querySelectorAll('.device-card'));
            const connectedCount = cards.filter(card => card.dataset.deviceStatus === 'connected').length;
            const batteryAverage = cards.length ? cards.reduce((sum, card) => sum + Number(card.dataset.deviceBattery || 0), 0) / cards.length : 0;
            const connectedCountElement = document.getElementById('connectedCountValue');
            const batteryAverageElement = document.getElementById('batteryAverageValue');

            if (connectedCountElement) connectedCountElement.textContent = String(connectedCount);
            if (batteryAverageElement) batteryAverageElement.textContent = `${Math.round(batteryAverage)}%`;
        }

        function updateRiskCard(score) {
            const safeScore = Math.max(0, Math.min(100, Number(score) || 0));
            const progress = document.getElementById('devicesRiskProgress');
            const value = document.getElementById('devicesRiskValue');
            const label = document.getElementById('devicesRiskLabel');
            const liveRisk = document.getElementById('liveRiskScore');

            if (progress) progress.style.background = `conic-gradient(var(--primary) ${safeScore}%, #EDF2F7 0deg)`;
            if (value) value.textContent = safeScore;
            if (label) {
                label.textContent = safeScore >= 70 ? 'خطر عالي' : (safeScore >= 40 ? 'خطر متوسط' : 'خطر منخفض');
            }
            if (liveRisk) liveRisk.textContent = safeScore;
        }

        function updateDeviceCard(deviceId, updates = {}) {
            const card = document.querySelector(`.device-card[data-device-id="${deviceId}"]`);
            if (!card) return;

            const status = updates.status || card.dataset.deviceStatus || 'disconnected';
            const batteryLevel = updates.battery_level !== undefined ? Number(updates.battery_level) : Number(card.dataset.deviceBattery || 0);
            const badge = document.getElementById(`deviceStatus-${deviceId}`);
            const batteryBar = document.getElementById(`deviceBatteryBar-${deviceId}`);
            const batteryText = document.getElementById(`deviceBatteryText-${deviceId}`);
            const signalDot = document.getElementById(`deviceSignalDot-${deviceId}`);
            const signalText = document.getElementById(`deviceSignalText-${deviceId}`);
            const lastUpdated = document.getElementById(`deviceLastUpdated-${deviceId}`);

            card.dataset.deviceStatus = status;
            card.dataset.deviceBattery = String(batteryLevel);

            if (badge) {
                badge.textContent = status === 'connected' ? 'متصل' : 'غير متصل';
                badge.className = 'text-xs font-semibold uppercase';
                if (status === 'connected') {
                    badge.classList.add('text-emerald-600');
                } else {
                    badge.classList.add('text-rose-600');
                }
            }

            if (batteryBar) {
                batteryBar.style.width = `${Math.max(0, Math.min(100, batteryLevel))}%`;
                batteryBar.className = 'h-full';
                batteryBar.classList.add(batteryLevel > 30 ? 'bg-emerald-500' : 'bg-rose-500');
            }

            if (batteryText) {
                batteryText.textContent = `${Math.max(0, Math.min(100, batteryLevel))}%`;
            }

            if (signalDot) {
                signalDot.className = 'h-2 w-2 rounded-full';
                if (status === 'connected') {
                    signalDot.classList.add('bg-emerald-500', 'animate-pulse');
                } else {
                    signalDot.classList.add('bg-gray-300');
                }
            }

            if (signalText) {
                signalText.textContent = status === 'connected' ? 'نشطة' : 'منقطعة';
            }

            if (lastUpdated && updates.last_updated) {
                lastUpdated.textContent = formatLiveTimestamp(updates.last_updated);
            }

            updateSummaryCards();
        }

        function normalizeDeviceType(type) {
            const normalized = String(type || '').toLowerCase().trim();
            if (!normalized) return '';
            if (['polar', 'ecg', 'heart', 'heart_rate'].includes(normalized)) return 'ecg';
            if (['emotiv', 'eeg', 'brain', 'eeg_device'].includes(normalized)) return 'eeg';
            if (['esp32', 'emg', 'muscle'].includes(normalized)) return 'emg';
            return normalized;
        }

        function normalizeDeviceKey(value) {
            return String(value || '').toLowerCase().replace(/[^a-z0-9]+/g, '');
        }

        function resolveDeviceStatus(value) {
            const normalized = String(value || '').toLowerCase();
            if (['connected', 'online', 'active', 'ok', 'stable'].includes(normalized)) return 'connected';
            if (['disconnected', 'offline', 'inactive', 'error', 'warning'].includes(normalized)) return 'disconnected';
            return 'connected';
        }

        function buildEventDeviceCandidates(payload) {
            const eventData = payload?.data || payload || {};
            const vital = eventData.vital_sign || {};
            const devices = Array.isArray(eventData.devices) ? eventData.devices : [];
            const payloadDevice = eventData.device || {};
            const candidates = [];

            const explicitDevice = {
                device_id: vital.device_id ?? payloadDevice.device_id ?? eventData.device_id ?? null,
                device_type: normalizeDeviceType(vital.device_type ?? payloadDevice.device_type ?? eventData.device_type ?? ''),
                device_name: vital.device_name ?? payloadDevice.device_name ?? eventData.device_name ?? eventData.name ?? null,
                battery_level: vital.battery_level ?? payloadDevice.battery_level ?? eventData.battery_level ?? null,
                status: eventData.connection_status ?? payloadDevice.status ?? eventData.status ?? 'connected',
            };

            if (explicitDevice.device_id !== null || explicitDevice.device_type || explicitDevice.device_name) {
                candidates.push(explicitDevice);
            }

            devices.forEach(device => {
                candidates.push({
                    device_id: device.id ?? null,
                    device_type: normalizeDeviceType(device.type ?? ''),
                    device_name: device.name ?? null,
                    battery_level: device.battery_level ?? null,
                    status: device.status ?? 'connected',
                });
            });

            return candidates;
        }

        function findDeviceCardMatches(payload) {
            const cards = Array.from(document.querySelectorAll('.device-card'));
            if (!cards.length) return [];

            const candidates = buildEventDeviceCandidates(payload);
            const matches = [];

            cards.forEach(card => {
                const cardId = String(card.dataset.deviceId || '');
                const cardType = normalizeDeviceType(card.dataset.deviceType || '');
                const cardName = normalizeDeviceKey(card.dataset.deviceName || '');

                const matchedCandidate = candidates.find(candidate => {
                    if (candidate.device_id !== null && String(candidate.device_id) === cardId) {
                        return true;
                    }

                    const candidateType = normalizeDeviceType(candidate.device_type || '');
                    const candidateName = normalizeDeviceKey(candidate.device_name || '');

                    if (candidateType && cardType && candidateType === cardType) {
                        return true;
                    }

                    if (candidateName && cardName && (candidateName.includes(cardName) || cardName.includes(candidateName))) {
                        return true;
                    }

                    return false;
                });

                if (matchedCandidate) {
                    matches.push({ card, candidate: matchedCandidate });
                }
            });

            return matches;
        }

        function normalizeRealtimeData(payload) {
            const eventData = payload?.data || payload || {};
            const analysis = eventData.analysis || {};
            const riskScore = analysis.risk_score !== undefined ? Number(analysis.risk_score) : null;

            return {
                last_updated: eventData.timestamp || new Date().toISOString(),
                eeg_status: eventData.eeg_status || (eventData.vital_sign ? 'محدث' : 'غير معروف'),
                connection_status: eventData.connection_status || 'connected',
                risk_score: riskScore,
            };
        }

        function syncDeviceStatusFromEvent(payload, lastUpdated) {
            const matches = findDeviceCardMatches(payload);
            if (!matches.length) return;

            matches.forEach(({ card, candidate }) => {
                updateDeviceCard(card.dataset.deviceId, {
                    status: resolveDeviceStatus(candidate.status),
                    battery_level: candidate.battery_level ?? undefined,
                    last_updated: lastUpdated,
                });
            });
        }

        function applyRealtimeData(payload) {
            const normalized = normalizeRealtimeData(payload);

            if (normalized.eeg_status) {
                const eegStatus = document.getElementById('liveEegStatus');
                if (eegStatus) eegStatus.textContent = normalized.eeg_status;
            }

            if (normalized.last_updated) {
                const lastUpdated = document.getElementById('liveLastUpdated');
                if (lastUpdated) lastUpdated.textContent = formatLiveTimestamp(normalized.last_updated);
            }

            if (normalized.risk_score !== null) {
                updateRiskCard(Math.round(normalized.risk_score * 100));
            }

            setRealtimeBadge(normalized.connection_status === 'connected' ? 'connected' : 'warning', normalized.connection_status === 'connected' ? 'متصل' : 'إعادة الاتصال...');
            syncDeviceStatusFromEvent(payload, normalized.last_updated);
        }

        async function fetchDeviceSummaries() {
            const cards = Array.from(document.querySelectorAll('.device-card'));
            if (!cards.length) return;

            const snapshots = await Promise.all(cards.map(async (card) => {
                const deviceId = card.dataset.deviceId;
                const endpoint = deviceDataEndpoints[deviceId];

                if (!endpoint) return null;

                try {
                    const response = await fetch(endpoint, { cache: 'no-store' });
                    const data = await response.json();
                    return data?.success ? data.device : null;
                } catch (error) {
                    return null;
                }
            }));

            snapshots.forEach((snapshot) => {
                if (!snapshot) return;
                updateDeviceCard(snapshot.id, {
                    status: snapshot.status,
                    battery_level: snapshot.battery_level,
                    last_updated: snapshot.last_updated,
                });
            });

            updateSummaryCards();
        }

        async function fetchLiveDeviceData() {
            try {
                const response = await fetch('{{ route("devices.live-data") }}', { cache: 'no-store' });
                const data = await response.json();

                if (!data?.success) {
                    setRealtimeBadge('warning', 'إعادة الاتصال...');
                    return;
                }

                if (data.brain_activity) {
                    const eegStatus = document.getElementById('liveEegStatus');
                    if (eegStatus) eegStatus.textContent = data.brain_activity;
                }

                if (data.last_updated) {
                    const liveLastUpdated = document.getElementById('liveLastUpdated');
                    if (liveLastUpdated) liveLastUpdated.textContent = formatLiveTimestamp(data.last_updated);
                }

                setRealtimeBadge('connected', 'متصل');
            } catch (error) {
                setRealtimeBadge('warning', 'إعادة الاتصال...');
            }
        }

        function subscribeRealtime() {
            if (!window.Echo) {
                setRealtimeBadge('warning', 'إعادة الاتصال...');
                setTimeout(subscribeRealtime, 1500);
                return;
            }

            try {
                const channel = window.Echo.private(`patient.${patientId}`);
                channel.listen('MedicalDataUpdated', (event) => {
                    setRealtimeBadge('connected', 'متصل');
                    applyRealtimeData(event);
                });

                const connector = window.Echo.connector;
                if (connector && connector.socket && connector.socket.on) {
                    connector.socket.on('connect', () => setRealtimeBadge('connected', 'متصل'));
                    connector.socket.on('disconnect', () => setRealtimeBadge('warning', 'إعادة الاتصال...'));
                }
            } catch (error) {
                setRealtimeBadge('warning', 'إعادة الاتصال...');
                setTimeout(subscribeRealtime, 1500);
            }
        }

        async function initializeRealtime() {
            updateSummaryCards();
            await fetchDeviceSummaries();
            await fetchLiveDeviceData();
            subscribeRealtime();
        }

        initializeRealtime();
    </script>

    <script>window.csrfToken = '{{ csrf_token() }}';</script>
    <script src="{{ asset('js/web-bluetooth.js') }}"></script>
</x-app-layout>
