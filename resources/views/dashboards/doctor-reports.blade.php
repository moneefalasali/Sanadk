<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('تقارير الطبيب') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-8 rounded-[28px] border border-slate-700/70 bg-slate-900/90 p-6 shadow-[0_18px_45px_-28px_rgba(15,23,42,0.85)] backdrop-blur">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-sm uppercase tracking-[0.2em] text-sky-200">تقارير الطبيب</p>
                        <h1 class="mt-2 text-2xl font-bold text-slate-50">موجز المرضى والتحليل</h1>
                        <p class="mt-2 text-sm text-slate-300">عرض إحصاءات الرعاية الخاصة بالمرضى المرتبطين بك.</p>
                    </div>
                    <a href="{{ route('doctor') }}" class="inline-flex items-center gap-2 rounded-2xl bg-slate-800 px-4 py-2.5 font-semibold text-slate-100 border border-slate-700">
                        <i class="fas fa-arrow-right"></i>
                        العودة للوحة الطبيب
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
                <div class="rounded-2xl border border-slate-700 bg-slate-900/90 p-5">
                    <p class="text-sm text-slate-300">إجمالي المرضى</p>
                    <p class="mt-3 text-3xl font-bold text-slate-50">{{ $totalPatients }}</p>
                </div>
                <div class="rounded-2xl border border-slate-700 bg-slate-900/90 p-5">
                    <p class="text-sm text-slate-300">المرضى النشطين</p>
                    <p class="mt-3 text-3xl font-bold text-amber-300">{{ $activePatients }}</p>
                </div>
                <div class="rounded-2xl border border-slate-700 bg-slate-900/90 p-5">
                    <p class="text-sm text-slate-300">إجمالي النوبات</p>
                    <p class="mt-3 text-3xl font-bold text-rose-300">{{ $totalSeizures }}</p>
                </div>
                <div class="rounded-2xl border border-slate-700 bg-slate-900/90 p-5">
                    <p class="text-sm text-slate-300">معدل النوبات المتوقعة</p>
                    <p class="mt-3 text-3xl font-bold text-emerald-300">{{ $predictionRate }}%</p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-8">
                <div class="rounded-2xl border border-slate-700 bg-slate-900/90 p-5">
                    <p class="text-sm text-slate-300">متوسط مدة النوبات</p>
                    <p class="mt-3 text-2xl font-bold text-slate-50">{{ round($averageDuration ?? 0, 1) }} دقيقة</p>
                </div>
                <div class="rounded-2xl border border-slate-700 bg-slate-900/90 p-5">
                    <p class="text-sm text-slate-300">متوسط نبض القلب</p>
                    <p class="mt-3 text-2xl font-bold text-slate-50">{{ $averageHeartRate }} BPM</p>
                </div>
                <div class="rounded-2xl border border-slate-700 bg-slate-900/90 p-5">
                    <p class="text-sm text-slate-300">متوسط الأكسجين</p>
                    <p class="mt-3 text-2xl font-bold text-slate-50">{{ $averageOxygen }}%</p>
                </div>
            </div>

            <div class="rounded-[28px] border border-slate-700 bg-slate-900/90 overflow-hidden">
                <div class="border-b border-slate-700 px-6 py-4">
                    <h2 class="text-lg font-bold text-slate-50">تفاصيل المرضى</h2>
                    <p class="text-sm text-slate-300">آخر قراءة طبية لكل مريض ضمن رعايتك.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-700">
                        <thead class="bg-slate-800/90">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-200">المريض</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-200">الحالة</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-200">آخر نبض</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-200">آخر أكسجين</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-200">آخر نوبة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800 bg-slate-900">
                            @forelse($patients as $patient)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-semibold text-slate-50">{{ $patient->name }}</div>
                                        <div class="text-sm text-slate-300">{{ $patient->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($patient->active_seizure)
                                            <span class="inline-flex rounded-full bg-rose-950 px-3 py-1 text-xs font-semibold text-rose-100">نوبة نشطة</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-emerald-950 px-3 py-1 text-xs font-semibold text-emerald-100">مستقر</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-100">
                                        {{ $patient->latest_vitals?->heart_rate ?? '--' }} BPM
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-100">
                                        {{ $patient->latest_vitals?->oxygen_level ?? '--' }}%
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-100">
                                        @if($patient->latest_seizure)
                                            {{ $patient->latest_seizure->start_time->diffForHumans() }}
                                        @else
                                            لا توجد نوبات مسجلة
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-slate-300">لا توجد مرضى مرتبطين بالوصول إلى هذا التقرير حالياً.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
