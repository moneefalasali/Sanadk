<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('لوحة تحكم الطبيب') }}
        </h2>
    </x-slot>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="py-12 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-8 rounded-[28px] border border-slate-700/70 bg-slate-900/90 p-4 shadow-[0_18px_45px_-28px_rgba(15,23,42,0.85)] backdrop-blur sm:p-5">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('doctor.monitor') }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-sky-600 to-cyan-500 text-red px-4 py-2.5 rounded-2xl font-semibold shadow-lg shadow-sky-500/30 transition duration-200 hover:scale-[1.02] hover:shadow-sky-500/40">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h10"></path>
                            </svg>
                            عرض المراقبة الطبية
                        </a>
                        <a href="{{ route('map') }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-emerald-500 to-teal-400 text-red px-4 py-2.5 rounded-2xl font-semibold shadow-lg shadow-emerald-500/30 transition duration-200 hover:scale-[1.02] hover:shadow-emerald-500/40">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.553-.894L9 7m6 13l5.447-2.724A1 1 0 0021 16.382V5.618a1 1 0 00-1.553-.894L15 7m-6 13V7m6-2v13"></path>
                            </svg>
                            عرض الخريطة
                        </a>
                        <a href="{{ route('doctor.reports') }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-amber-500 to-orange-400 text-red px-4 py-2.5 rounded-2xl font-semibold shadow-lg shadow-amber-500/30 transition duration-200 hover:scale-[1.02] hover:shadow-amber-500/40">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6m-6 0h6m-6 0H7m8 0h2"></path>
                            </svg>
                            التقارير
                        </a>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="inline-block">
                        @csrf
                        <button type="submit" class="flex items-center gap-2 bg-gradient-to-r from-rose-500 to-red-500 text-red px-4 py-2.5 rounded-2xl font-semibold shadow-lg shadow-rose-500/30 transition duration-200 hover:scale-[1.02] hover:shadow-rose-500/40">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            تسجيل الخروج
                        </button>
                    </form>
                </div>
            </div>
            @if(Auth::user()->role === 'patient')
                @if(session('success'))
                    <div class="bg-emerald-900/80 border border-emerald-700 text-emerald-100 p-4 rounded-lg mb-6">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-6">
                    <div class="bg-slate-900/90 border border-slate-700 p-6 rounded-2xl shadow-[0_20px_60px_-30px_rgba(15,11,42,0.8)]">
                        <h3 class="text-xl font-bold mb-3 text-slate-50">طلب ارتباط بالطبيب</h3>
                        <p class="text-slate-300 mb-6">اختر طبيباً لتلقي إشعارات النوبات والقرارات الطبية المهمة.</p>
                        <form method="POST" action="{{ route('doctor.store') }}" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-slate-200">اختر الطبيب</label>
                                <select name="doctor_id" class="mt-1 block w-full rounded-xl border-slate-700 bg-slate-800 text-slate-50 shadow-sm" required>
                                    <option value="">اختر الطبيب</option>
                                    @foreach($availableDoctors as $doctor)
                                        <option value="{{ $doctor->id }}">{{ $doctor->name }} - {{ $doctor->email }}</option>
                                    @endforeach
                                </select>
                                @error('doctor_id')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                            </div>
                            <button type="submit" class="btn-modern">طلب الارتباط</button>
                        </form>
                    </div>

                    <div class="bg-slate-900/90 border border-slate-700 p-6 rounded-2xl shadow-[0_20px_60px_-30px_rgba(15,23,42,0.8)]">
                        <h3 class="text-xl font-bold mb-3 text-slate-50">الأطباء المرتبطون</h3>
                        @if($linkedDoctors->isEmpty())
                            <p class="text-slate-300">لم يتم ربطك بأي طبيب بعد. أرسل طلب ارتباط لطبيب موثوق.</p>
                        @else
                            <div class="space-y-4">
                                @foreach($linkedDoctors as $doctor)
                                    <div class="border border-slate-700 rounded-xl p-4 bg-slate-800/80">
                                        <div class="flex justify-between items-center gap-4">
                                            <div>
                                                <p class="font-semibold text-slate-50">{{ $doctor->name }}</p>
                                                <p class="text-sm text-slate-300">{{ $doctor->email }}</p>
                                            </div>
                                            <span class="px-3 py-1 rounded-full bg-sky-900/80 text-sky-100 text-xs">مرتبط</span>
                                        </div>
                                        <p class="mt-2 text-sm text-slate-300">سيتم إشعار هذا الطبيب عندما تتعرض لنوبة.</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-gradient-to-br from-sky-500 to-cyan-400 text-blue p-6 rounded-2xl shadow-lg shadow-sky-500/25">
                        <p class="text-sky-50 text-sm mb-2">إجمالي المرضى</p>
                        <p class="text-3xl font-bold">{{ $patients->count() }}</p>
                    </div>
                    <div class="bg-gradient-to-br from-rose-500 to-orange-400 text-blue p-6 rounded-2xl shadow-lg shadow-rose-500/25">
                        <p class="text-rose-50 text-sm mb-2">حالات نشطة</p>
                        <p class="text-3xl font-bold">{{ $activePatientCount }}</p>
                    </div>
                    <div class="bg-gradient-to-br from-amber-500 to-yellow-400 text-blue p-6 rounded-2xl shadow-lg shadow-amber-500/25">
                        <p class="text-amber-50 text-sm mb-2">إجمالي النوبات</p>
                        <p class="text-3xl font-bold">{{ $totalSeizures }}</p>
                    </div>
                </div>

                <div class="bg-slate-900/90 overflow-hidden shadow-[0_20px_60px_-30px_rgba(15,23,42,0.9)] sm:rounded-[24px] border border-slate-700">
                    <div class="border-b border-slate-700 bg-slate-800/90">
                        <nav class="flex flex-wrap gap-2 px-4 sm:px-6" aria-label="Tabs">
                            <button onclick="showDoctorTab('patients')" class="doctor-tab-button active py-4 px-4 rounded-t-xl font-semibold text-sm flex items-center gap-2 bg-sky-900/80 text-sky-100 border-b-2 border-sky-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                                المرضى
                            </button>
                            <button onclick="showDoctorTab('vitals')" class="doctor-tab-button py-4 px-4 rounded-t-xl font-semibold text-sm flex items-center gap-2 text-slate-100 hover:text-emerald-200 hover:bg-emerald-900/40">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                العلامات الحيوية
                            </button>
                            <button onclick="showDoctorTab('seizures')" class="doctor-tab-button py-4 px-4 rounded-t-xl font-semibold text-sm flex items-center gap-2 text-slate-100 hover:text-rose-200 hover:bg-rose-900/40">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                النوبات
                            </button>
                            <button onclick="showDoctorTab('analysis')" class="doctor-tab-button py-4 px-4 rounded-t-xl font-semibold text-sm flex items-center gap-2 text-slate-100 hover:text-violet-200 hover:bg-violet-900/40">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                التحليل
                            </button>
                        </nav>
                    </div>

                    <!-- Patients Tab -->
                    <div id="patients-tab" class="doctor-tab-content p-6">
                        <h3 class="text-lg font-bold mb-4 text-slate-50">قائمة المرضى</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @forelse($patients as $patient)
                                <div class="border border-slate-700 rounded-2xl p-5 bg-slate-800/90 shadow-sm hover:shadow-md transition">
                                    <div class="flex justify-between items-start gap-3 mb-3">
                                        <div>
                                            <h4 class="font-bold text-lg text-slate-50">{{ $patient->name }}</h4>
                                            <p class="text-sm text-slate-300">{{ $patient->email }}</p>
                                        </div>
                                        <span class="px-2.5 py-1 text-[11px] font-bold rounded-full {{ $patient->seizures()->whereNull('end_time')->exists() ? 'bg-rose-950 text-rose-100' : 'bg-emerald-950 text-emerald-100' }}">
                                            {{ $patient->seizures()->whereNull('end_time')->exists() ? 'نوبة نشطة' : 'مستقر' }}
                                        </span>
                                    </div>
                                    <div class="space-y-2 text-sm text-slate-200">
                                        <p><strong>الهاتف:</strong> {{ $patient->phone ?? 'غير محدد' }}</p>
                                        <p><strong>العنوان:</strong> {{ $patient->address ?? 'غير محدد' }}</p>
                                        <p><strong>آخر فحص:</strong> {{ $patient->vitalSigns()->latest()->first()?->created_at->diffForHumans() ?? 'لا يوجد' }}</p>
                                    </div>
                                    <div class="mt-4 flex gap-2 flex-wrap">
                                        <a href="{{ route('doctor.monitor', ['patient' => $patient->id]) }}" class="inline-flex items-center gap-1 bg-gradient-to-r from-sky-600 to-cyan-500 text-white px-3 py-1.5 rounded-xl text-sm font-semibold shadow-md shadow-sky-500/25 hover:scale-[1.02] transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                            </svg>
                                            المراقبة
                                        </a>
                                        <button class="inline-flex items-center gap-1 bg-gradient-to-r from-indigo-500 to-blue-500 text-white px-3 py-1.5 rounded-xl text-sm font-semibold shadow-md shadow-indigo-500/25 hover:scale-[1.02] transition" onclick="viewPatientDetails({{ $patient->id }})">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            عرض التفاصيل
                                        </button>
                                        <button class="inline-flex items-center gap-1 bg-gradient-to-r from-emerald-500 to-teal-500 text-white px-3 py-1.5 rounded-xl text-sm font-semibold shadow-md shadow-emerald-500/25 hover:scale-[1.02] transition" onclick="addPatientNote({{ $patient->id }})">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            إضافة ملاحظة
                                        </button>
                                        <button class="inline-flex items-center gap-1 bg-gradient-to-r from-amber-500 to-orange-400 text-white px-3 py-1.5 rounded-xl text-sm font-semibold shadow-md shadow-amber-500/25 hover:scale-[1.02] transition" onclick="sendNotification({{ $patient->id }})">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h4l4 4v-4h4a2 2 0 002-2z"></path>
                                            </svg>
                                            إرسال إشعار
                                        </button>
                                        @if($patient->phone)
                                        <a href="tel:{{ $patient->phone }}" class="inline-flex items-center gap-1 bg-gradient-to-r from-fuchsia-500 to-purple-500 text-white px-3 py-1.5 rounded-xl text-sm font-semibold shadow-md shadow-fuchsia-500/25 hover:scale-[1.02] transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                            </svg>
                                            اتصال
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-slate-300 col-span-full">لا يوجد مرضى مسجلين</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Vitals Tab -->
                    <div id="vitals-tab" class="doctor-tab-content hidden p-6">
                        <h3 class="text-lg font-bold mb-4 text-slate-50">العلامات الحيوية الحالية</h3>
                        <div class="overflow-x-auto rounded-2xl border border-slate-700">
                            <table class="min-w-full divide-y divide-slate-700">
                                <thead class="bg-slate-800/90">
                                    <tr>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-200 uppercase">المريض</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-200 uppercase">نبض القلب</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-200 uppercase">مستوى الأكسجين</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-200 uppercase">الحرارة</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-200 uppercase">الوقت</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-slate-900 divide-y divide-slate-700">
                                    @forelse($patients->flatMap(fn($p) => $p->vitalSigns()->latest()->take(1)->get()) as $vital)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-50">{{ $vital->user->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-200">{{ $vital->heart_rate ?? '--' }} BPM</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-200">{{ $vital->oxygen_level ?? '--' }}%</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-200">{{ $vital->temperature ?? '--' }}°C</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-200">{{ $vital->created_at->diffForHumans() }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-6 py-4 text-center text-slate-300">لا توجد بيانات</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Seizures Tab -->
                    <div id="seizures-tab" class="doctor-tab-content hidden p-6">
                        <h3 class="text-lg font-bold mb-4 text-slate-50">سجل النوبات</h3>
                        <div class="overflow-x-auto rounded-2xl border border-slate-700">
                            <table class="min-w-full divide-y divide-slate-700">
                                <thead class="bg-slate-800/90">
                                    <tr>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-200 uppercase">المريض</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-200 uppercase">التاريخ</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-200 uppercase">المدة</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-200 uppercase">متنبأ بها</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-200 uppercase">الملاحظات</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-slate-900 divide-y divide-slate-700">
                                    @forelse(\App\Models\Seizure::whereIn('user_id', $patients->pluck('id'))->latest()->get() as $seizure)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-50">{{ $seizure->user->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-200">{{ $seizure->start_time->format('Y-m-d H:i') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-200">{{ $seizure->end_time ? $seizure->end_time->diffInMinutes($seizure->start_time) . ' دقيقة' : 'جارية' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $seizure->is_predicted ? 'bg-emerald-950 text-emerald-100' : 'bg-rose-950 text-rose-100' }}">
                                                    {{ $seizure->is_predicted ? 'نعم' : 'لا' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-slate-200">{{ $seizure->notes ?? '--' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-6 py-4 text-center text-slate-300">لا توجد بيانات</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Analysis Tab -->
                    <div id="analysis-tab" class="doctor-tab-content hidden p-6">
                        <h3 class="text-lg font-bold mb-4 text-slate-50">التحليل والإحصائيات</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-slate-800/90 border border-slate-700 p-6 rounded-2xl">
                                <h4 class="font-bold mb-4 text-slate-50">معدل النجاح في التنبؤ</h4>
                                <div class="text-4xl font-bold text-sky-200">
                                    {{ $predictionRate ?? 0 }}%
                                </div>
                            </div>
                            <div class="bg-slate-800/90 border border-slate-700 p-6 rounded-2xl">
                                <h4 class="font-bold mb-4 text-slate-50">متوسط مدة النوبة</h4>
                                <div class="text-4xl font-bold text-emerald-200">
                                    {{ round($avgSeizureDuration ?? 0, 1) }} دقيقة
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        function showDoctorTab(tabName) {
            document.querySelectorAll('.doctor-tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });
            document.querySelectorAll('.doctor-tab-button').forEach(btn => {
                btn.classList.remove('bg-sky-900/80', 'text-sky-100', 'border-sky-400');
                btn.classList.add('text-slate-100');
            });

            document.getElementById(tabName + '-tab').classList.remove('hidden');
            const activeButton = event.currentTarget;
            activeButton.classList.remove('text-slate-100');
            activeButton.classList.add('bg-sky-900/80', 'text-sky-100', 'border-sky-400');
        }

        function viewPatientDetails(patientId) {
            // يمكن تحسين هذا لفتح modal أو صفحة تفاصيل
            window.location.href = '/patient/' + patientId + '/details';
        }

        function addPatientNote(patientId) {
            const note = prompt('أدخل الملاحظة الطبية:');
            if (note && note.trim()) {
                // إرسال الملاحظة عبر AJAX أو form
                fetch('/doctor/patient/' + patientId + '/note', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ note: note.trim() })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('تم إضافة الملاحظة بنجاح');
                    } else {
                        alert('حدث خطأ في إضافة الملاحظة');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('حدث خطأ في الاتصال');
                });
            }
        }

        function sendNotification(patientId) {
            const message = prompt('أدخل رسالة الإشعار:');
            if (message && message.trim()) {
                fetch('/doctor/patient/' + patientId + '/notify', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ message: message.trim() })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('تم إرسال الإشعار بنجاح');
                    } else {
                        alert('حدث خطأ في إرسال الإشعار');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('حدث خطأ في الاتصال');
                });
            }
        }
    </script>
</x-app-layout>
