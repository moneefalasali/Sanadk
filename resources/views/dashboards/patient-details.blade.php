<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                تفاصيل المريض: {{ $patient->name }}
            </h2>
            <a href="{{ url()->previous() }}" class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white text-slate-700 px-4 py-2 text-sm shadow-sm hover:bg-slate-50 transition">
                <i class="fas fa-arrow-left"></i>
                العودة
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Patient Info Card -->
            <div class="bg-white rounded-[28px] border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center">
                                <i class="fas fa-user text-2xl text-blue-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">{{ $patient->name }}</h3>
                                <p class="text-sm text-slate-500">{{ $patient->email }}</p>
                                <p class="text-sm text-slate-500">{{ $patient->phone ?? 'رقم غير محدد' }}</p>
                            </div>
                        </div>
                        <div class="text-left">
                            <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold
                                {{ $activeSeizure ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                                <span class="w-2.5 h-2.5 rounded-full {{ $activeSeizure ? 'bg-red-600' : 'bg-emerald-600' }}"></span>
                                {{ $activeSeizure ? 'نوبة نشطة' : 'مستقر' }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600">{{ $seizureStats['total'] }}</div>
                            <div class="text-sm text-slate-500">إجمالي النوبات</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-orange-600">{{ $seizureStats['this_month'] }}</div>
                            <div class="text-sm text-slate-500">هذا الشهر</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600">{{ number_format($seizureStats['average_duration'], 1) }}</div>
                            <div class="text-sm text-slate-500">متوسط المدة (دقيقة)</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vital Signs -->
            <div class="bg-white rounded-[28px] border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-200">
                    <h3 class="text-base font-semibold text-slate-900">العلامات الحيوية</h3>
                    <p class="text-sm text-slate-500">آخر القياسات والمتوسطات</p>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-red-600">{{ $vitalsStats['latest_heart_rate'] ?? '--' }}</div>
                            <div class="text-sm text-slate-500">معدل النبض (آخر)</div>
                            <div class="text-xs text-slate-400 mt-1">المتوسط: {{ number_format($vitalsStats['avg_heart_rate'], 1) }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-blue-600">{{ $vitalsStats['latest_oxygen'] ?? '--' }}%</div>
                            <div class="text-sm text-slate-500">مستوى الأكسجين (آخر)</div>
                            <div class="text-xs text-slate-400 mt-1">المتوسط: {{ number_format($vitalsStats['avg_oxygen'], 1) }}%</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-green-600">{{ $vitalsStats['latest_temperature'] ?? '--' }}°C</div>
                            <div class="text-sm text-slate-500">درجة الحرارة (آخر)</div>
                            <div class="text-xs text-slate-400 mt-1">المتوسط: {{ number_format($vitalsStats['avg_temperature'], 1) }}°C</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Seizures -->
            <div class="bg-white rounded-[28px] border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-200">
                    <h3 class="text-base font-semibold text-slate-900">النوبات الأخيرة</h3>
                    <p class="text-sm text-slate-500">سجل النوبات الأخيرة والتفاصيل</p>
                </div>
                <div class="divide-y divide-slate-200">
                    @forelse($recentSeizures as $seizure)
                        <div class="px-6 py-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-slate-900">
                                        {{ $seizure->start_time->format('Y/m/d H:i') }}
                                        @if($seizure->end_time)
                                            - {{ $seizure->end_time->format('H:i') }}
                                        @endif
                                    </div>
                                    <div class="text-sm text-slate-500">
                                        @if($seizure->end_time)
                                            مدة: {{ $seizure->start_time->diffInMinutes($seizure->end_time) }} دقيقة
                                        @else
                                            نوبة نشطة
                                        @endif
                                        @if($seizure->is_predicted)
                                            <span class="text-orange-600">(متنبأ بها)</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="text-left">
                                @if($seizure->latitude && $seizure->longitude)
                                    <div class="text-xs text-slate-400">
                                        📍 {{ number_format($seizure->latitude, 4) }}, {{ number_format($seizure->longitude, 4) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-slate-500">
                            لا توجد نوبات مسجلة
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Recent Vital Signs -->
            <div class="bg-white rounded-[28px] border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-200">
                    <h3 class="text-base font-semibold text-slate-900">العلامات الحيوية الأخيرة</h3>
                    <p class="text-sm text-slate-500">تتبع القياسات الأخيرة</p>
                </div>
                <div class="divide-y divide-slate-200">
                    @forelse($recentVitals as $vital)
                        <div class="px-6 py-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                    <i class="fas fa-heartbeat text-blue-600"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-slate-900">
                                        {{ $vital->created_at->format('Y/m/d H:i') }}
                                    </div>
                                    <div class="text-sm text-slate-500">
                                        نبض: {{ $vital->heart_rate }} | أكسجين: {{ $vital->oxygen_level }}% | حرارة: {{ $vital->temperature }}°C
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-slate-500">
                            لا توجد قياسات حيوية مسجلة
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Daily Entries -->
            @if($recentEntries->count() > 0)
            <div class="bg-white rounded-[28px] border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-200">
                    <h3 class="text-base font-semibold text-slate-900">التقارير اليومية</h3>
                    <p class="text-sm text-slate-500">الإدخالات اليومية الأخيرة</p>
                </div>
                <div class="divide-y divide-slate-200">
                    @foreach($recentEntries as $entry)
                        <div class="px-6 py-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="font-medium text-slate-900">
                                    {{ $entry->created_at->format('Y/m/d') }}
                                </div>
                                <div class="text-sm text-slate-500">
                                    {{ $entry->created_at->format('H:i') }}
                                </div>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <span class="text-slate-500">النوم:</span>
                                    <span class="font-medium">{{ $entry->sleep_quality }}/5</span>
                                </div>
                                <div>
                                    <span class="text-slate-500">التوتر:</span>
                                    <span class="font-medium">{{ $entry->stress_level }}/5</span>
                                </div>
                                <div>
                                    <span class="text-slate-500">النشاط:</span>
                                    <span class="font-medium">{{ $entry->activity_level }}/5</span>
                                </div>
                                <div>
                                    <span class="text-slate-500">الأدوية:</span>
                                    <span class="font-medium">{{ $entry->medication_taken ? 'نعم' : 'لا' }}</span>
                                </div>
                            </div>
                            @if($entry->notes)
                                <div class="mt-2 text-sm text-slate-600 bg-slate-50 p-2 rounded">
                                    {{ $entry->notes }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
    </div>
</x-app-layout>