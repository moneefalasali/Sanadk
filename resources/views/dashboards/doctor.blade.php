<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('لوحة تحكم الطبيب') }}
        </h2>
    </x-slot>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Logout Button -->
            <div class="mb-6 flex justify-end">
                <form method="POST" action="{{ route('logout') }}" class="inline-block">
                    @csrf
                    <button type="submit" class="flex items-center gap-2 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition duration-200 shadow-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        تسجيل الخروج
                    </button>
                </form>
            </div>
            @if(Auth::user()->role === 'patient')
                @if(session('success'))
                    <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded-lg mb-6">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h3 class="text-xl font-bold mb-3">طلب ارتباط بالطبيب</h3>
                        <p class="text-gray-500 mb-6">اختر طبيباً لتلقي إشعارات النوبات والقرارات الطبية المهمة.</p>
                        <form method="POST" action="{{ route('doctor.store') }}" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700">اختر الطبيب</label>
                                <select name="doctor_id" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm" required>
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

                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h3 class="text-xl font-bold mb-3">الأطباء المرتبطون</h3>
                        @if($linkedDoctors->isEmpty())
                            <p class="text-gray-500">لم يتم ربطك بأي طبيب بعد. أرسل طلب ارتباط لطبيب موثوق.</p>
                        @else
                            <div class="space-y-4">
                                @foreach($linkedDoctors as $doctor)
                                    <div class="border rounded-xl p-4">
                                        <div class="flex justify-between items-center gap-4">
                                            <div>
                                                <p class="font-semibold">{{ $doctor->name }}</p>
                                                <p class="text-sm text-gray-500">{{ $doctor->email }}</p>
                                            </div>
                                            <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs">مرتبط</span>
                                        </div>
                                        <p class="mt-2 text-sm text-gray-500">سيتم إشعار هذا الطبيب عندما تتعرض لنوبة.</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <!-- Quick Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <p class="text-gray-500 text-sm mb-2">إجمالي المرضى</p>
                        <p class="text-3xl font-bold text-blue-600">{{ $patients->count() }}</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <p class="text-gray-500 text-sm mb-2">حالات نشطة</p>
                        <p class="text-3xl font-bold text-red-600">{{ $activePatientCount }}</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <p class="text-gray-500 text-sm mb-2">إجمالي النوبات</p>
                        <p class="text-3xl font-bold text-orange-600">{{ $totalSeizures }}</p>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200">
                        <nav class="flex gap-8 px-6" aria-label="Tabs">
                            <button onclick="showDoctorTab('patients')" class="doctor-tab-button active py-4 px-1 border-b-2 border-blue-500 font-medium text-sm text-blue-600 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                                المرضى
                            </button>
                            <button onclick="showDoctorTab('vitals')" class="doctor-tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                العلامات الحيوية
                            </button>
                            <button onclick="showDoctorTab('seizures')" class="doctor-tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                النوبات
                            </button>
                            <button onclick="showDoctorTab('analysis')" class="doctor-tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                التحليل
                            </button>
                        </nav>
                    </div>

                    <!-- Patients Tab -->
                    <div id="patients-tab" class="doctor-tab-content p-6">
                        <h3 class="text-lg font-bold mb-4">قائمة المرضى</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @forelse($patients as $patient)
                                <div class="border rounded-lg p-4 hover:shadow-md transition">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <h4 class="font-bold text-lg">{{ $patient->name }}</h4>
                                            <p class="text-sm text-gray-500">{{ $patient->email }}</p>
                                        </div>
                                        <span class="px-2 py-1 text-xs rounded-full {{ $patient->seizures()->whereNull('end_time')->exists() ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                            {{ $patient->seizures()->whereNull('end_time')->exists() ? 'نوبة نشطة' : 'مستقر' }}
                                        </span>
                                    </div>
                                    <div class="space-y-2 text-sm">
                                        <p><strong>الهاتف:</strong> {{ $patient->phone }}</p>
                                        <p><strong>العنوان:</strong> {{ $patient->address ?? 'غير محدد' }}</p>
                                        <p><strong>آخر فحص:</strong> {{ $patient->vitalSigns()->latest()->first()?->created_at->diffForHumans() ?? 'لا يوجد' }}</p>
                                    </div>
                                    <div class="mt-4 flex gap-2 flex-wrap">
                                        <button class="flex items-center gap-1 bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600 transition" onclick="viewPatientDetails({{ $patient->id }})">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            عرض التفاصيل
                                        </button>
                                        <button class="flex items-center gap-1 bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600 transition" onclick="addPatientNote({{ $patient->id }})">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            إضافة ملاحظة
                                        </button>
                                        <button class="flex items-center gap-1 bg-yellow-500 text-white px-3 py-1 rounded text-sm hover:bg-yellow-600 transition" onclick="sendNotification({{ $patient->id }})">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h4l4 4v-4h4a2 2 0 002-2z"></path>
                                            </svg>
                                            إرسال إشعار
                                        </button>
                                        @if($patient->phone)
                                        <a href="tel:{{ $patient->phone }}" class="flex items-center gap-1 bg-purple-500 text-white px-3 py-1 rounded text-sm hover:bg-purple-600 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                            </svg>
                                            اتصال
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-500 col-span-full">لا يوجد مرضى مسجلين</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Vitals Tab -->
                    <div id="vitals-tab" class="doctor-tab-content hidden p-6">
                        <h3 class="text-lg font-bold mb-4">العلامات الحيوية الحالية</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">المريض</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">نبض القلب</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">مستوى الأكسجين</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الحرارة</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الوقت</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($patients->flatMap(fn($p) => $p->vitalSigns()->latest()->take(1)->get()) as $vital)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $vital->user->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $vital->heart_rate ?? '--' }} BPM</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $vital->oxygen_level ?? '--' }}%</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $vital->temperature ?? '--' }}°C</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $vital->created_at->diffForHumans() }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">لا توجد بيانات</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Seizures Tab -->
                    <div id="seizures-tab" class="doctor-tab-content hidden p-6">
                        <h3 class="text-lg font-bold mb-4">سجل النوبات</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">المريض</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">التاريخ</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">المدة</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">متنبأ بها</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الملاحظات</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse(\App\Models\Seizure::whereIn('user_id', $patients->pluck('id'))->latest()->get() as $seizure)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $seizure->user->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $seizure->start_time->format('Y-m-d H:i') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $seizure->end_time ? $seizure->end_time->diffInMinutes($seizure->start_time) . ' دقيقة' : 'جارية' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $seizure->is_predicted ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ $seizure->is_predicted ? 'نعم' : 'لا' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ $seizure->notes ?? '--' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">لا توجد بيانات</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Analysis Tab -->
                    <div id="analysis-tab" class="doctor-tab-content hidden p-6">
                        <h3 class="text-lg font-bold mb-4">التحليل والإحصائيات</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <h4 class="font-bold mb-4">معدل النجاح في التنبؤ</h4>
                                <div class="text-4xl font-bold text-blue-600">
                                    {{ $predictionRate ?? 0 }}%
                                </div>
                            </div>
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <h4 class="font-bold mb-4">متوسط مدة النوبة</h4>
                                <div class="text-4xl font-bold text-green-600">
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
                btn.classList.remove('border-blue-500', 'text-blue-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            });

            document.getElementById(tabName + '-tab').classList.remove('hidden');
            event.target.classList.remove('border-transparent', 'text-gray-500');
            event.target.classList.add('border-blue-500', 'text-blue-600');
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
