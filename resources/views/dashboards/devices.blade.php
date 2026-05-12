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
                <button id="analyzeBtn" class="btn-modern-secondary px-5 py-3">تحليل البيانات</button>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded-3xl mb-6">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-white p-5 rounded-3xl shadow-sm">
                <p class="text-sm text-gray-500 mb-2">الأجهزة المتصلة</p>
                <p class="text-3xl font-bold text-blue-600">{{ $connectedCount }}</p>
            </div>
            <div class="bg-white p-5 rounded-3xl shadow-sm">
                <p class="text-sm text-gray-500 mb-2">متوسط البطارية</p>
                <p class="text-3xl font-bold text-emerald-600">{{ round($batteryAverage) }}%</p>
            </div>
            <div class="bg-white p-5 rounded-3xl shadow-sm">
                <p class="text-sm text-gray-500 mb-2">أنواع الأجهزة</p>
                <p class="text-3xl font-bold text-orange-600">{{ $deviceTypes->count() }}</p>
            </div>
        </div>

        <div class="bg-white p-5 rounded-3xl shadow-sm mb-6">
            @php
                $devicesRiskScore = is_numeric($riskScore) ? (int) round($riskScore) : 0;
                $devicesRiskLabel = $devicesRiskScore >= 70 ? 'خطر عالي' : ($devicesRiskScore >= 40 ? 'خطر متوسط' : 'خطر منخفض');
            @endphp
            <p class="text-sm text-gray-500 mb-2">مؤشر الخطر الحالي</p>
            <div class="circular-progress mx-auto mb-3" style="background: conic-gradient(var(--primary) {{ $devicesRiskScore }}%, #EDF2F7 0deg); width: 140px; height: 140px;">
                <div class="progress-value">{{ $devicesRiskScore }}</div>
            </div>
            <p class="text-center text-sm text-gray-600">{{ $devicesRiskLabel }}</p>
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
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 hover:shadow-md transition">
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
                                    <span class="text-xs font-semibold uppercase {{ $device->is_connected ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ $device->is_connected ? 'متصل' : 'غير متصل' }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div class="space-y-2">
                                        <p class="text-xs text-gray-400">مستوى البطارية</p>
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 h-2 rounded-full bg-gray-100 overflow-hidden">
                                                <div class="h-full {{ $device->battery_level > 30 ? 'bg-emerald-500' : 'bg-rose-500' }}" style="width: {{ $device->battery_level ?? 0 }}%"></div>
                                            </div>
                                            <span class="text-xs font-semibold">{{ $device->battery_level ?? 0 }}%</span>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <p class="text-xs text-gray-400">إشارة حية</p>
                                        <div class="flex items-center gap-2">
                                            <span class="h-2 w-2 rounded-full {{ $device->is_connected ? 'bg-emerald-500 animate-pulse' : 'bg-gray-300' }}"></span>
                                            <span class="text-xs text-gray-600">{{ $device->is_connected ? 'نشطة' : 'منقطعة' }}</span>
                                        </div>
                                    </div>
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
</x-app-layout>
