<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('تتبع الموقع الجغرافي') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="relative mb-6">
                <div id="map" class="w-full h-96 rounded-[28px] border border-slate-200 shadow-lg overflow-hidden"></div>

                <div class="absolute inset-x-4 top-4 bg-white/95 backdrop-blur-md rounded-3xl border border-slate-200 shadow-sm p-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <p class="text-xs text-slate-500">الموقع الحالي</p>
                            <h2 id="nearestHospitalName" class="text-lg font-semibold text-slate-900">أقرب مستشفى</h2>
                            <p id="navigationDescription" class="text-sm text-slate-500">نظام الملاحة مُفعّل لتوجيهك إلى أسرع طريق.</p>
                        </div>
                        <div class="flex flex-col gap-3">
                            <!-- Map Type Selector -->
                            <div class="inline-flex gap-1 rounded-2xl overflow-hidden shadow-sm">
                                <button id="map-street" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 text-black px-3 py-2 text-xs font-semibold transition hover:bg-blue-700">
                                    <i class="fas fa-map"></i>
                                    خريطة
                                </button>
                                <button id="map-satellite" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 text-black px-3 py-2 text-xs font-semibold transition hover:bg-slate-800">
                                    <i class="fas fa-satellite"></i>
                                    أقمار صناعية
                                </button>
                                <button id="map-terrain" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 text-black px-3 py-2 text-xs font-semibold transition hover:bg-emerald-700">
                                    <i class="fas fa-mountain"></i>
                                    تضاريس
                                </button>
                            </div>
                            <!-- Action Buttons -->
                            <div class="inline-flex gap-2">
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 text-black px-4 py-2 text-sm shadow-sm hover:bg-slate-700 transition">
                                    <i class="fas fa-arrow-left"></i>
                                    لوحة التحكم
                                </a>
                                <button id="navigateNearest" class="inline-flex items-center gap-2 rounded-2xl bg-blue-600 text-black px-4 py-2 text-sm shadow-sm hover:bg-blue-700 transition">
                                    <i class="fas fa-route"></i>
                                    التوجيه لأقرب مستشفى
                                </button>
                                <button id="searchHospitalsAI" class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 text-black px-4 py-2 text-sm shadow-sm hover:bg-emerald-700 transition">
                                    <i class="fas fa-brain"></i>
                                    بحث ذكي بالذكاء الاصطناعي
                                </button>
                                <button id="contactButton" class="inline-flex items-center gap-2 rounded-2xl bg-cyan-600 text-black px-4 py-2 text-sm shadow-sm hover:bg-cyan-700 transition">
                                    <i class="fas fa-phone-alt"></i>
                                    اتصال
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-4">
                <div class="bg-white rounded-[28px] border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">أقرب المستشفيات</h3>
                            <p class="text-sm text-slate-500">اختر المستشفى لبدء الملاحة أو الاتصال</p>
                        </div>
                        <span id="hospitalCount" class="inline-flex items-center rounded-full bg-blue-100 text-blue-700 px-3 py-1 text-xs font-semibold">0 مواقع</span>
                    </div>
                    <div id="hospitalsList" class="grid gap-3 p-4 md:grid-cols-2">
                        <!-- Hospitals will be loaded dynamically -->
                    </div>
                </div>

                <div class="bg-white rounded-[28px] border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-200">
                        <h3 class="text-base font-semibold text-slate-900">مرضى متتابعة</h3>
                        <p class="text-sm text-slate-500">اضغط لعرض موقع المريض على الخريطة</p>
                    </div>
                    <div class="grid gap-3 p-4">
                        @forelse($patients as $patient)
                            @php
                                $seizures = collect($patient['seizures'] ?? []);
                                $hasActiveSeizure = $seizures->whereNull('end_time')->isNotEmpty();
                            @endphp
                            <button type="button" onclick="focusPatient({{ $patient['id'] }})" class="w-full text-right rounded-3xl border border-slate-200 p-4 bg-slate-50 hover:bg-slate-100 transition text-slate-800">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="space-y-1">
                                        <h4 class="font-semibold">{{ $patient['name'] }}</h4>
                                        <p class="text-sm text-slate-500">{{ $patient['phone'] ?? 'بدون رقم' }}</p>
                                    </div>
                                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold {{ $hasActiveSeizure ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                                        <span class="w-2.5 h-2.5 rounded-full {{ $hasActiveSeizure ? 'bg-red-600' : 'bg-emerald-600' }}"></span>
                                        {{ $hasActiveSeizure ? 'نوبة نشطة' : 'مستقر' }}
                                    </span>
                                </div>
                            </button>
                        @empty
                            <p class="text-sm text-slate-500">لا يوجد مرضى للعرض</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="{{ asset('css/leaflet.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/leaflet-routing-machine.css') }}" />

    <!-- Custom JavaScript -->
    <script src="{{ asset('js/leaflet.js') }}"></script>
    <script src="{{ asset('js/leaflet-routing-machine.js') }}"></script>
    <script src="{{ asset('js/main.js') }}"></script>
    <script src="{{ asset('js/map.js') }}"></script>

    <script>
        // Focus on patient function for the patient list
        function focusPatient(patientId) {
            if (window.sanadakMap && window.sanadakMap.patientMarkers.has(patientId)) {
                const patientEntry = window.sanadakMap.patientMarkers.get(patientId);
                window.sanadakMap.map.setView([patientEntry.lat, patientEntry.lng], 16);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const contactButton = document.getElementById('contactButton');
            if (!contactButton) return;

            contactButton.addEventListener('click', () => {
                window.dispatchEvent(new CustomEvent('contactButtonClicked', {
                    detail: { source: 'mapPage', timestamp: Date.now() }
                }));

                if (window.sanadakMap?.showNotification) {
                    window.sanadakMap.showNotification('تم تفعيل حدث الاتصال', 'success');
                } else {
                    alert('تم الضغط على زر الاتصال');
                }
            });
        });
    </script>

    <style>
        .arrow-marker {
            pointer-events: none;
        }
        .arrow-marker div {
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
    </style>
</x-app-layout>
