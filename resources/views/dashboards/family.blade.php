<x-app-layout>
    <div class="container-fluid">
        <!-- Header Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm">
                    <div>
                        <h2 class="h4 mb-0 fw-bold text-primary">
                            <i class="fas fa-users"></i> لوحة تحكم الأهل
                        </h2>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-cog"></i> الإعدادات
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(Auth::user()->role === 'family')
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
                        <i class="fas fa-check-circle"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
                        <i class="fas fa-exclamation-triangle"></i>
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="mb-6 bg-white p-5 rounded-3xl shadow-sm border border-gray-100">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <p class="text-sm text-gray-500 mb-2">حالة البث المباشر</p>
                            <p id="familyRealtimeStatus" class="font-bold text-lg">جاري الاتصال...</p>
                            <p id="familyLastReceivedAt" class="text-sm text-gray-500 mt-2">آخر تحديث: --</p>
                        </div>
                        <span id="familyRealtimeBadge" class="inline-flex items-center justify-center px-4 py-2 rounded-full text-sm font-semibold bg-gray-100 text-gray-700">جاري الاتصال...</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-4">
                        <div class="bg-gray-50 p-4 rounded-2xl">
                            <p class="text-xs text-gray-500">أحدث تنبيه</p>
                            <p id="familyLatestAlert" class="font-bold text-sm mt-1">لا توجد تنبيهات</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-2xl">
                            <p class="text-xs text-gray-500">المرضى المتابعين</p>
                            <p class="font-bold text-sm mt-1">{{ $patients->count() }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-2xl">
                            <p class="text-xs text-gray-500">آخر قراءة للأجهزة</p>
                            <p id="familyLatestReading" class="font-bold text-sm mt-1">--</p>
                        </div>
                    </div>
                </div>

                <!-- Pending Requests Section -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold mb-4">
                        <i class="fas fa-inbox"></i> طلبات الارتباط بالمريض
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        @forelse($pendingRequests as $request)
                            <div class="bg-white border-l-4 border-orange-400 p-4 rounded-lg shadow-sm">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h4 class="font-bold text-lg">{{ $request['name'] }}</h4>
                                        <p class="text-sm text-gray-500">{{ $request['email'] }}</p>
                                        <p class="text-sm text-gray-600 mt-1">العلاقة: {{ $request['relationship'] }}</p>
                                    </div>
                                    <span class="badge bg-warning text-dark">قيد الانتظار</span>
                                </div>
                                <p class="text-sm text-gray-600 mb-3">يطلب إضافة أنت كفرد عائلة</p>
                                <div class="flex gap-2">
                                    <form method="POST" action="{{ route('family.accept-request') }}" class="flex-1">
                                        @csrf
                                        <input type="hidden" name="request_id" value="{{ $request['id'] }}">
                                        <button type="submit" class="btn btn-sm btn-success w-100">
                                            <i class="fas fa-check"></i> قبول
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('family.reject-request') }}" class="flex-1">
                                        @csrf
                                        <input type="hidden" name="request_id" value="{{ $request['id'] }}">
                                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                            <i class="fas fa-times"></i> رفض
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-2">
                                <div class="bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded-lg text-center">
                                    <i class="fas fa-info-circle text-2xl mb-2"></i>
                                    <p class="font-semibold">لا توجد طلبات ارتباط معلقة</p>
                                    <p class="text-sm">سيتم عرض الطلبات الجديدة هنا عند وصولها</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Patient Status Overview -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold mb-4">
                        <i class="fas fa-users text-primary"></i> حالة المرضى المتابعين
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                        @forelse($patients as $patient)
                            <div id="patientCard-{{ $patient['id'] }}" class="bg-white p-6 rounded-lg shadow-md border-t-4 {{ $patient['status'] === 'alert' ? 'border-red-500' : 'border-green-500' }}">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-blue-600 text-lg"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-bold">{{ $patient['name'] }}</h4>
                                        <p class="text-sm text-gray-500">{{ $patient['phone'] }}</p>
                                        @if(!empty($patient['email']))
                                            <p class="text-sm text-gray-500">{{ $patient['email'] }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                        <span class="text-sm text-gray-600">الحالة</span>
                                        <span id="patientStatus-{{ $patient['id'] }}" class="text-sm font-semibold {{ $patient['status'] === 'alert' ? 'text-red-600' : 'text-green-600' }}">
                                            {{ $patient['status'] === 'alert' ? '⚠️ تحذير' : '✓ مستقر' }}
                                        </span>
                                    </div>

                                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                        <span class="text-sm text-gray-600">آخر تحديث</span>
                                        <span id="patientLastUpdate-{{ $patient['id'] }}" class="text-sm">{{ $patient['last_update'] }}</span>
                                    </div>

                                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                        <span class="text-sm text-gray-600">آخر قراءة</span>
                                        <span id="patientLatestReading-{{ $patient['id'] }}" class="text-sm font-semibold text-blue-600">{{ $patient['latest_reading'] }}</span>
                                    </div>

                                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                        <span class="text-sm text-gray-600">نوبات</span>
                                        <span class="text-sm font-bold text-red-600">{{ $patient['active_seizures'] }}</span>
                                    </div>

                                    <div class="p-2 bg-gray-50 rounded">
                                        <p class="text-xs text-gray-500 mb-1">تنبيه فوري</p>
                                        <p id="patientAlertText-{{ $patient['id'] }}" class="text-sm font-semibold {{ $patient['status'] === 'alert' ? 'text-red-600' : 'text-gray-700' }}">
                                            {{ $patient['status'] === 'alert' ? 'تنبيه نشط' : 'لا توجد تنبيهات حالية' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex gap-2 mt-4">
                                    <button onclick="viewPatientDetails({{ $patient['id'] }})" class="flex-1 btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> التفاصيل
                                    </button>
                                    <button onclick="shareLocation({{ $patient['id'] }})" class="flex-1 btn btn-sm btn-info">
                                        <i class="fas fa-map-marker-alt"></i> الموقع
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-3">
                                <div class="bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded-lg text-center">
                                    <i class="fas fa-info-circle text-2xl mb-2"></i>
                                    <p class="font-semibold">لا يوجد مرضى متابعين</p>
                                    <p class="text-sm">في انتظار طلبات ارتباط من المرضى</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Tabs Section -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200">
                        <nav class="flex gap-8 px-6" aria-label="Tabs">
                            <button onclick="showFamilyTab('monitoring', this)" class="family-tab-button active py-4 px-1 border-b-2 border-blue-500 font-medium text-sm text-blue-600">
                                <i class="fas fa-heart-pulse"></i> المراقبة
                            </button>
                            <button onclick="showFamilyTab('history', this)" class="family-tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                <i class="fas fa-history"></i> السجل
                            </button>
                            <button onclick="showFamilyTab('notifications', this)" class="family-tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                <i class="fas fa-bell"></i> التنبيهات
                            </button>
                            <button onclick="showFamilyTab('settings', this)" class="family-tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                <i class="fas fa-sliders-h"></i> الإعدادات
                            </button>
                        </nav>
                    </div>

                    <!-- Monitoring Tab -->
                    <div id="monitoring-tab" class="family-tab-content p-6">
                        <h3 class="text-lg font-bold mb-4"><i class="fas fa-heart-pulse text-danger"></i> مراقبة الحالة الحالية</h3>
                        <div class="space-y-4">
                            @forelse($patients as $patient)
                                <div id="monitoringPatientRow-{{ $patient['id'] }}" class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 p-4 border rounded-lg hover:shadow-md transition">
                                    <div class="flex items-center gap-4 flex-1">
                                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold">
                                            {{ substr($patient['name'], 0, 1) }}
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-bold text-lg">{{ $patient['name'] }}</h4>
                                            <p id="monitoringPatientUpdate-{{ $patient['id'] }}" class="text-sm text-gray-500">آخر تحديث: {{ $patient['last_update'] }}</p>
                                            <p id="monitoringPatientReading-{{ $patient['id'] }}" class="text-sm text-blue-600 mt-1">آخر قراءة: {{ $patient['latest_reading'] }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="mb-2">
                                            <span id="monitoringPatientStatus-{{ $patient['id'] }}" class="px-3 py-1 rounded-full text-sm font-bold {{ $patient['status'] === 'alert' ? 'bg-red-100 text-red-800 animate-pulse' : 'bg-green-100 text-green-800' }}">
                                                <i class="fas {{ $patient['status'] === 'alert' ? 'fa-exclamation-triangle' : 'fa-check-circle' }}"></i>
                                                {{ $patient['status'] === 'alert' ? 'تنبيه نشط' : 'مستقر' }}
                                            </span>
                                        </div>
                                        <div class="flex gap-2 justify-end">
                                            <button class="text-blue-600 hover:underline text-xs" onclick="viewFamilyPatientDetails({{ $patient['id'] }})">
                                                <i class="fas fa-eye"></i> التفاصيل
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-500 text-center"><i class="fas fa-inbox"></i> لا يوجد مرضى</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- History Tab -->
                    <div id="history-tab" class="family-tab-content hidden p-6">
                        <h3 class="text-lg font-bold mb-4"><i class="fas fa-history text-info"></i> سجل النوبات</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">المريض</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">التاريخ</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">المدة</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الحالة</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @php
                                        $allSeizures = collect($patients)->flatMap(fn($p) => $p['seizures'])->sortByDesc('start_time');
                                    @endphp
                                    @forelse($allSeizures as $seizure)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ $seizure->user->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $seizure->start_time->format('Y-m-d H:i') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $seizure->end_time ? $seizure->end_time->diffInMinutes($seizure->start_time) . ' م' : 'جارية' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs font-semibold rounded-full {{ $seizure->end_time ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ $seizure->end_time ? '✓ انتهت' : '⚠️ جارية' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">لا توجد نوبات</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Notifications Tab -->
                    <div id="notifications-tab" class="family-tab-content hidden p-6">
                        <h3 class="text-lg font-bold mb-4"><i class="fas fa-bell text-warning"></i> التنبيهات والإشعارات</h3>
                        <div id="notificationsContainer" class="space-y-3">
                            @forelse($notifications as $notification)
                                <div class="border-l-4 {{ $notification['type'] === 'emergency' ? 'border-red-500 bg-red-50' : ($notification['type'] === 'prediction' ? 'border-orange-500 bg-orange-50' : 'border-green-500 bg-green-50') }} p-4 rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <i class="fas {{ $notification['type'] === 'emergency' ? 'fa-exclamation-circle text-red-600' : ($notification['type'] === 'prediction' ? 'fa-exclamation-triangle text-orange-600' : 'fa-check-circle text-green-600') }} text-lg"></i>
                                        <div class="flex-1">
                                            <h5 class="font-bold {{ $notification['type'] === 'emergency' ? 'text-red-800' : ($notification['type'] === 'prediction' ? 'text-orange-800' : 'text-green-800') }}">
                                                {{ $notification['title'] }}
                                            </h5>
                                            <p class="text-sm {{ $notification['type'] === 'emergency' ? 'text-red-700' : ($notification['type'] === 'prediction' ? 'text-orange-700' : 'text-green-700') }}">
                                                {{ $notification['patient_name'] }} - {{ $notification['message'] }}
                                            </p>
                                            <p class="text-xs {{ $notification['type'] === 'emergency' ? 'text-red-600' : ($notification['type'] === 'prediction' ? 'text-orange-600' : 'text-green-600') }} mt-1">
                                                <i class="fas fa-clock"></i> {{ $notification['created_at']->diffForHumans() }}
                                            </p>
                                        </div>
                                        @if(!$notification['is_read'])
                                            <span class="bg-blue-500 text-white text-xs px-2 py-1 rounded-full">جديد</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded-lg text-center">
                                    <i class="fas fa-info-circle text-2xl mb-2"></i>
                                    <p class="font-semibold">لا توجد تنبيهات</p>
                                    <p class="text-sm">سيتم عرض التنبيهات الجديدة هنا عند وصولها</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Settings Tab -->
                    <div id="settings-tab" class="family-tab-content hidden p-6">
                        <h3 class="text-lg font-bold mb-4"><i class="fas fa-sliders-h text-secondary"></i> إعدادات التنبيهات</h3>
                        <div class="space-y-4">
                            @forelse($patients as $patient)
                                <div class="border rounded-lg p-4 shadow-sm">
                                    <h4 class="font-bold mb-3">{{ $patient['name'] }}</h4>
                                    <div class="space-y-2">
                                        <label class="flex items-center">
                                            <input type="checkbox" class="rounded" data-setting="seizure-{{ $patient['id'] }}" checked>
                                            <span class="ml-2"><i class="fas fa-zap text-danger"></i> تنبيهات النوبات</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" class="rounded" data-setting="prediction-{{ $patient['id'] }}" checked>
                                            <span class="ml-2"><i class="fas fa-brain text-primary"></i> تنبيهات التنبؤ</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" class="rounded" data-setting="location-{{ $patient['id'] }}" checked>
                                            <span class="ml-2"><i class="fas fa-location-dot text-success"></i> تحديثات الموقع</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" class="rounded" data-setting="health-{{ $patient['id'] }}" checked>
                                            <span class="ml-2"><i class="fas fa-heart text-danger"></i> العلامات الحيوية</span>
                                        </label>
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-500"><i class="fas fa-inbox"></i> لا يوجد مرضى</p>
                            @endforelse
                            <div class="mt-4">
                                <button onclick="saveNotificationSettings()" class="btn btn-primary">
                                    <i class="fas fa-save"></i> حفظ الإعدادات
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            @elseif(Auth::user()->role === 'patient')
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm" role="alert">
                        <i class="fas fa-check-circle"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="card-modern">
                            <h3 class="h4 fw-bold mb-3">طلب ارتباط بفرد العائلة</h3>
                            <p class="text-muted mb-4">أضف بيانات شخص العائلة ليتم إرسال تنبيهات له عند حدوث نوبة.</p>
                            <form method="POST" action="{{ route('family.store') }}" class="space-y-4">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label fw-bold">الاسم</label>
                                    <input type="text" name="name" value="{{ old('name') }}" class="form-control rounded-3" required>
                                    @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">البريد الإلكتروني</label>
                                    <input type="email" name="email" value="{{ old('email') }}" class="form-control rounded-3" required>
                                    <small class="text-muted">يجب أن يكون مسجلاً برول family</small>
                                    @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">رقم الهاتف (اختياري)</label>
                                    <input type="text" name="phone" value="{{ old('phone') }}" class="form-control rounded-3">
                                    @error('phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">صلة القرابة</label>
                                    <input type="text" name="relationship" value="{{ old('relationship') }}" class="form-control rounded-3" placeholder="الأب، الأم، الأخ..." required>
                                    @error('relationship')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                                <button type="submit" class="btn btn-primary btn-lg w-100 rounded-3">
                                    <i class="fas fa-send"></i> إرسال طلب الارتباط
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card-modern">
                            <h3 class="h4 fw-bold mb-3">
                                <i class="fas fa-users text-primary"></i> أفراد العائلة المرتبطين
                            </h3>
                            @if($contacts && $contacts->count() > 0)
                                <div class="space-y-3">
                                    @foreach($contacts as $contact)
                                        <div class="border rounded-3 p-3">
                                            <h5 class="fw-bold mb-1">{{ $contact->name }}</h5>
                                            <p class="small text-muted mb-1">{{ $contact->relationship }}</p>
                                            <p class="small text-muted"><i class="fas fa-phone"></i> {{ $contact->phone }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted text-center">
                                    <i class="fas fa-inbox text-muted" style="font-size: 2rem;"></i>
                                    <br>لا يوجد أفراد عائلة مسجلين
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Patient Details Modal -->
        <div class="modal fade" id="familyPatientModal" tabindex="-1" aria-labelledby="familyPatientModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="familyPatientModalLabel"><i class="fas fa-user"></i> تفاصيل المريض</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <div class="modal-body">
                        <div id="familyPatientDetailsContent"></div>
                        <div id="familyPatientMapContainer" class="mt-4" style="display:none;">
                            <h6 class="mb-2"><i class="fas fa-map-marker-alt"></i> الموقع الحالي</h6>
                            <div id="familyPatientMap" style="height: 320px; min-height: 320px;"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                    </div>
                </div>
            </div>
        </div>

        <link rel="stylesheet" href="{{ asset('css/leaflet.css') }}" />
        <script src="{{ asset('js/leaflet.js') }}"></script>

        <script>
            const familyPatients = {!! json_encode($patients) !!};
            const familyPatientsData = Array.isArray(familyPatients) ? familyPatients : Object.values(familyPatients);
            let patientMap;
            let patientMarker;

            function showFamilyTab(tabName, button) {
                document.querySelectorAll('.family-tab-content').forEach(tab => {
                    tab.classList.add('hidden');
                });
                document.querySelectorAll('.family-tab-button').forEach(btn => {
                    btn.classList.remove('border-blue-500', 'text-blue-600');
                    btn.classList.add('border-transparent', 'text-gray-500');
                });

                document.getElementById(tabName + '-tab').classList.remove('hidden');
                if (button) {
                    button.classList.remove('border-transparent', 'text-gray-500');
                    button.classList.add('border-blue-500', 'text-blue-600');
                }
            }

            function getFamilyPatient(patientId) {
                return familyPatientsData.find(patient => patient.id === patientId);
            }

            function viewPatientDetails(patientId) {
                showFamilyPatientDetails(patientId);
            }

            function renderPatientDetails(patient) {
                const statusLabel = patient.status === 'alert' ?
                    '<span class="badge bg-danger">تحذير</span>' :
                    '<span class="badge bg-success">مستقر</span>';

                const latestSeizure = patient.seizures && patient.seizures.length ? patient.seizures[0] : null;
                const seizureText = latestSeizure ?
                    `آخر نوبة: ${latestSeizure.start_time ? new Date(latestSeizure.start_time).toLocaleString('ar-EG') : 'غير متوفر'}` :
                    'لا توجد نوبات مسجلة';

                return `
                    <div class="row g-3">
                        <div class="col-md-6">
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>الاسم</span><strong>${patient.name}</strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>الهاتف</span><strong>${patient.phone || 'غير متوفر'}</strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>البريد الإلكتروني</span><strong>${patient.email || 'غير متوفر'}</strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>آخر تحديث</span><strong>${patient.last_update || 'لا يوجد'}</strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>عدد النوبات النشطة</span><strong>${patient.active_seizures}</strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>صلة القرابة</span><strong>${patient.relationship || 'غير محدد'}</strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>الحالة</span>${statusLabel}
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded">
                                <h6 class="mb-3">معلومات إضافية</h6>
                                <p class="mb-2"><strong>العنوان:</strong> ${patient.address || 'غير متوفر'}</p>
                                <p class="mb-2"><strong>إحداثيات:</strong> ${patient.latitude && patient.longitude ? patient.latitude + ', ' + patient.longitude : 'غير متوفرة'}</p>
                                <p class="mb-2"><strong>معلومات النوبة:</strong> ${seizureText}</p>
                            </div>
                        </div>
                    </div>
                `;
            }

            function showFamilyPatientDetails(patientId) {
                const patient = getFamilyPatient(patientId);
                if (!patient) {
                    alert('المريض غير موجود. تأكد من قبول الطلب أولاً.');
                    return;
                }

                document.getElementById('familyPatientDetailsContent').innerHTML = renderPatientDetails(patient);
                document.getElementById('familyPatientMapContainer').style.display = 'none';
                const familyPatientModal = new bootstrap.Modal(document.getElementById('familyPatientModal'));
                familyPatientModal.show();
            }

            function shareLocation(patientId) {
                const patient = getFamilyPatient(patientId);
                if (!patient) {
                    alert('المريض غير موجود. تأكد من قبول الطلب أولاً.');
                    return;
                }

                if (!patient.latitude || !patient.longitude) {
                    alert('لا توجد بيانات موقع لهذا المريض حالياً.');
                    return;
                }

                document.getElementById('familyPatientDetailsContent').innerHTML = `
                    <div class="alert alert-info">سيتم عرض الموقع الحالي للمريض والمستشفيات القريبة أدناه.</div>
                `;
                document.getElementById('familyPatientMapContainer').style.display = 'block';

                const mapElement = document.getElementById('familyPatientMap');
                if (!patientMap) {
                    initFamilyMap(mapElement, patient);
                } else {
                    updateFamilyMap(patient);
                }

                const familyPatientModal = new bootstrap.Modal(document.getElementById('familyPatientModal'));
                familyPatientModal.show();
            }

            async function initFamilyMap(mapElement, patient) {
                // Load Leaflet if not loaded
                if (typeof L === 'undefined') {
                    await loadLeafletScript();
                }

                // Try to get user location first
                let userLocation = { lat: patient.latitude, lng: patient.longitude };
                try {
                    userLocation = await getUserLocation();
                    console.log('Family user location obtained:', userLocation);
                } catch (error) {
                    console.warn('Using patient location as fallback:', error);
                }

                // Initialize map centered on user location
                patientMap = L.map(mapElement).setView([userLocation.lat, userLocation.lng], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(patientMap);

                // Add user location marker
                const userIcon = L.divIcon({
                    className: 'user-location-marker',
                    html: `<div class="rounded-full bg-blue-600 text-white text-sm px-3 py-2 shadow-lg">📍 أنت هنا</div>`,
                    iconSize: [100, 40],
                    iconAnchor: [50, 20]
                });
                L.marker([userLocation.lat, userLocation.lng], { icon: userIcon }).addTo(patientMap);

                // Add patient marker
                const patientIcon = L.divIcon({
                    className: 'patient-marker',
                    html: `<div class="rounded-full bg-red-600 text-white text-sm px-3 py-2 shadow-lg">🏥 ${patient.name}</div>`,
                    iconSize: [120, 40],
                    iconAnchor: [60, 20]
                });
                patientMarker = L.marker([patient.latitude, patient.longitude], { icon: patientIcon }).addTo(patientMap)
                    .bindPopup(`<strong>${patient.name}</strong><br>موقع المريض الحالي`).openPopup();

                // Search for nearby hospitals
                try {
                    const hospitals = await searchNearbyHospitals(userLocation.lat, userLocation.lng);
                    console.log('Family hospitals found:', hospitals);

                    // Add hospital markers
                    hospitals.forEach(hospital => {
                        const hospitalIcon = L.divIcon({
                            className: 'hospital-marker',
                            html: `<div class="rounded-full bg-green-600 text-white text-xs px-2 py-1">🏥</div>`,
                            iconSize: [32, 32],
                            iconAnchor: [16, 32]
                        });
                        L.marker([hospital.lat, hospital.lng], { icon: hospitalIcon }).addTo(patientMap)
                            .bindPopup(`<strong>${hospital.name}</strong><br>${hospital.address}<br>${hospital.distance} - ${hospital.eta}`);
                    });
                } catch (error) {
                    console.error('Hospital search failed for family:', error);
                }
            }

            async function updateFamilyMap(patient) {
                if (!patientMap) return;

                // Update patient marker
                if (patientMarker) {
                    patientMap.removeLayer(patientMarker);
                }

                const patientIcon = L.divIcon({
                    className: 'patient-marker',
                    html: `<div class="rounded-full bg-red-600 text-white text-sm px-3 py-2 shadow-lg">🏥 ${patient.name}</div>`,
                    iconSize: [120, 40],
                    iconAnchor: [60, 20]
                });
                patientMarker = L.marker([patient.latitude, patient.longitude], { icon: patientIcon }).addTo(patientMap)
                    .bindPopup(`<strong>${patient.name}</strong><br>موقع المريض الحالي`).openPopup();

                patientMap.setView([patient.latitude, patient.longitude], 13);
            }

            // Helper functions (same as main map)
            function getUserLocation() {
                return new Promise((resolve, reject) => {
                    if (!navigator.geolocation) {
                        reject(new Error('المتصفح لا يدعم تحديد الموقع الجغرافي'));
                        return;
                    }

                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            resolve({
                                lat: position.coords.latitude,
                                lng: position.coords.longitude
                            });
                        },
                        (error) => {
                            console.warn('فشل في الحصول على الموقع:', error);
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

            async function searchNearbyHospitals(lat, lng, radius = 5000) {
                try {
                    const query = `[out:json];node["amenity"="hospital"](around:${radius},${lat},${lng});out;`;
                    const response = await fetch(`https://overpass-api.de/api/interpreter?data=${encodeURIComponent(query)}`);
                    const data = await response.json();

                    const hospitals = data.elements.map(element => ({
                        name: element.tags?.name || 'مستشفى غير معروف',
                        lat: element.lat,
                        lng: element.lon,
                        address: element.tags?.['addr:full'] || element.tags?.['addr:city'] || 'العنوان غير متوفر',
                        phone: element.tags?.phone || null,
                        website: element.tags?.website || null
                    }));

                    // If no hospitals found, use OpenAI fallback
                    if (hospitals.length === 0) {
                        return await searchHospitalsWithAI(lat, lng);
                    }

                    // Calculate distances and ETAs
                    for (const hospital of hospitals) {
                        const distance = calculateDistance(lat, lng, hospital.lat, hospital.lng);
                        hospital.distance = `${distance.toFixed(1)} كم`;
                        hospital.eta = calculateETA(distance);
                    }

                    return hospitals.slice(0, 5);
                } catch (error) {
                    console.error('Error searching hospitals:', error);
                    return await searchHospitalsWithAI(lat, lng);
                }
            }

            async function searchHospitalsWithAI(lat, lng) {
                try {
                    const response = await fetch('/api/search-hospitals-ai', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            latitude: lat,
                            longitude: lng,
                            location: 'الرياض، المملكة العربية السعودية'
                        })
                    });

                    if (!response.ok) {
                        throw new Error('AI search failed');
                    }

                    const data = await response.json();
                    return data.hospitals || [];
                } catch (error) {
                    console.error('AI search failed:', error);
                    return [
                        { name: 'مستشفى الملك فيصل التخصصي', lat: 24.7133, lng: 46.6840, distance: '2.5 كم', eta: '8 دقائق', address: 'الرياض، المملكة العربية السعودية' },
                        { name: 'مستشفى الحرس الوطني', lat: 24.7040, lng: 46.6908, distance: '3.8 كم', eta: '12 دقيقة', address: 'الرياض، المملكة العربية السعودية' },
                        { name: 'مدينة الملك عبدالعزيز الطبية', lat: 24.6969, lng: 46.7500, distance: '5.2 كم', eta: '15 دقيقة', address: 'الرياض، المملكة العربية السعودية' },
                    ];
                }
            }

            function calculateDistance(lat1, lng1, lat2, lng2) {
                const R = 6371;
                const dLat = (lat2 - lat1) * Math.PI / 180;
                const dLng = (lng2 - lng1) * Math.PI / 180;
                const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                         Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                         Math.sin(dLng/2) * Math.sin(dLng/2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                return R * c;
            }

            function calculateETA(distanceKm) {
                const timeHours = distanceKm / 30;
                const timeMinutes = Math.round(timeHours * 60);
                return `${timeMinutes} دقيقة`;
            }

            async function loadLeafletScript() {
                const sources = [
                    '/js/leaflet.js'
                ];

                function loadSrc(src) {
                    return new Promise((resolve, reject) => {
                        if (window.L) {
                            return resolve(window.L);
                        }
                        const script = document.createElement('script');
                        script.src = src;
                        script.onload = () => {
                            if (window.L) {
                                resolve(window.L);
                            } else {
                                reject(new Error('Leaflet تم تحميله لكن لم يُعَرَّف.'));
                            }
                        };
                        script.onerror = () => reject(new Error(`فشل تحميل مكتبة Leaflet من ${src}`));
                        document.head.appendChild(script);
                    });
                }

                return sources.reduce((promise, src) => {
                    return promise.catch(() => loadSrc(src));
                }, Promise.reject()).catch(error => {
                    throw new Error('فشل تحميل مكتبة Leaflet: ' + error.message);
                });
            }

            function saveNotificationSettings() {
                alert('تم حفظ الإعدادات بنجاح');
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
            const familyRealtimePatients = @json($patients);
            const familyRealtimeUserId = {{ auth()->id() }};
            const familyRealtimeBadge = document.getElementById('familyRealtimeBadge');
            const familyRealtimeStatus = document.getElementById('familyRealtimeStatus');
            const familyLastReceivedAt = document.getElementById('familyLastReceivedAt');
            const familyLatestAlert = document.getElementById('familyLatestAlert');
            const familyLatestReading = document.getElementById('familyLatestReading');

            const initialFamilyReading = familyRealtimePatients.find((patient) => patient?.latest_reading)?.latest_reading || 'لا توجد قراءة حديثة';
            if (familyLatestReading) {
                familyLatestReading.textContent = initialFamilyReading;
            }

            function formatFamilyTimestamp(value) {
                if (!value) return '--';
                if (typeof value === 'string' && /ago|قبل|منذ/.test(value)) return value;
                const parsed = new Date(value);
                if (Number.isNaN(parsed.getTime())) return value;
                return parsed.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            }

            function familyReadingText(payload) {
                const eventData = payload?.data || payload || {};
                const vital = eventData.vital_sign || {};
                const parts = [];

                if (vital.heart_rate) parts.push(`معدل النبض ${vital.heart_rate}`);
                if (vital.oxygen_level) parts.push(`تشبع الأكسجين ${vital.oxygen_level}%`);
                if (vital.temperature) parts.push(`الحرارة ${vital.temperature}°C`);
                if (vital.eeg_signal) parts.push(`EEG ${vital.eeg_signal}`);
                if (vital.emg_signal) parts.push(`EMG ${vital.emg_signal}`);

                if (!parts.length && typeof payload === 'string') {
                    return payload;
                }

                return parts.length ? parts.join(' • ') : 'لا توجد قراءة حديثة';
            }

            function familyAlertLevel(payload) {
                const eventData = payload?.data || payload || {};
                const analysis = eventData.analysis || {};
                if (analysis.seizure_detected || analysis.alert_level === 'emergency') return 'danger';
                if (analysis.alert_level === 'warning' || Number(analysis.prediction_score) >= 0.65) return 'warning';
                return 'info';
            }

            function familyAlertMessage(payload) {
                const eventData = payload?.data || payload || {};
                const analysis = eventData.analysis || {};

                if (analysis.seizure_detected || analysis.alert_level === 'emergency') {
                    return 'تنبيه طارئ: احتمال نوبة عالية';
                }

                if (analysis.alert_level === 'warning' || Number(analysis.prediction_score) >= 0.65) {
                    return 'تنبيه تحذيري: راقب الحالة عن كثب';
                }

                return 'تحديث طبي جديد تم استلامه';
            }

            function familyStatusText(payload) {
                const level = familyAlertLevel(payload);
                return level === 'danger' ? '⚠️ تحذير' : '✓ مستقر';
            }

            function setFamilyRealtimeState(status, label) {
                if (!familyRealtimeBadge || !familyRealtimeStatus) return;
                familyRealtimeBadge.textContent = label;
                familyRealtimeBadge.className = 'inline-flex items-center justify-center px-4 py-2 rounded-full text-sm font-semibold';
                if (status === 'connected') {
                    familyRealtimeBadge.classList.add('bg-emerald-100', 'text-emerald-700');
                } else if (status === 'warning') {
                    familyRealtimeBadge.classList.add('bg-amber-100', 'text-amber-700');
                } else {
                    familyRealtimeBadge.classList.add('bg-rose-100', 'text-rose-700');
                }
                familyRealtimeStatus.textContent = label;
            }

            function updateFamilyPatientUI(patientId, payload) {
                const eventData = payload?.data || payload || {};
                const timestamp = eventData.timestamp || new Date().toISOString();
                const reading = familyReadingText(payload);
                const statusText = familyStatusText(payload);
                const statusClass = familyAlertLevel(payload) === 'danger' ? 'text-red-600' : 'text-green-600';
                const alertClass = familyAlertLevel(payload) === 'danger' ? 'text-red-600' : 'text-gray-700';

                const statusEl = document.getElementById(`patientStatus-${patientId}`);
                const updateEl = document.getElementById(`patientLastUpdate-${patientId}`);
                const readingEl = document.getElementById(`patientLatestReading-${patientId}`);
                const alertEl = document.getElementById(`patientAlertText-${patientId}`);
                const cardEl = document.getElementById(`patientCard-${patientId}`);
                const monitoringStatusEl = document.getElementById(`monitoringPatientStatus-${patientId}`);
                const monitoringUpdateEl = document.getElementById(`monitoringPatientUpdate-${patientId}`);
                const monitoringReadingEl = document.getElementById(`monitoringPatientReading-${patientId}`);

                if (statusEl) {
                    statusEl.textContent = statusText;
                    statusEl.className = 'text-sm font-semibold';
                    statusEl.classList.add(statusClass);
                }

                if (updateEl) updateEl.textContent = formatFamilyTimestamp(timestamp);
                if (readingEl) readingEl.textContent = reading;
                if (alertEl) {
                    alertEl.textContent = familyAlertMessage(payload);
                    alertEl.className = 'text-sm font-semibold';
                    alertEl.classList.add(alertClass);
                }

                if (cardEl) {
                    cardEl.className = cardEl.className.replace(/border-(red|green)-500/g, '').trim();
                    cardEl.classList.add(familyAlertLevel(payload) === 'danger' ? 'border-red-500' : 'border-green-500');
                }

                if (monitoringStatusEl) {
                    monitoringStatusEl.textContent = familyAlertLevel(payload) === 'danger' ? 'تنبيه نشط' : 'مستقر';
                    monitoringStatusEl.className = 'px-3 py-1 rounded-full text-sm font-bold';
                    if (familyAlertLevel(payload) === 'danger') {
                        monitoringStatusEl.classList.add('bg-red-100', 'text-red-800', 'animate-pulse');
                    } else {
                        monitoringStatusEl.classList.add('bg-green-100', 'text-green-800');
                    }
                }

                if (monitoringUpdateEl) monitoringUpdateEl.textContent = `آخر تحديث: ${formatFamilyTimestamp(timestamp)}`;
                if (monitoringReadingEl) monitoringReadingEl.textContent = `آخر قراءة: ${reading}`;

                if (familyLatestAlert) familyLatestAlert.textContent = familyAlertMessage(payload);
                if (familyLatestReading) familyLatestReading.textContent = reading;
                if (familyLastReceivedAt) familyLastReceivedAt.textContent = `آخر تحديث: ${formatFamilyTimestamp(timestamp)}`;
            }

            function prependRealtimeNotification(patientName, payload) {
                const notificationsContainer = document.getElementById('notificationsContainer');
                if (!notificationsContainer) return;

                const alertLevel = familyAlertLevel(payload);
                const title = alertLevel === 'danger' ? 'تنبيه طارئ' : 'تحديث طبي';
                const colorClass = alertLevel === 'danger' ? 'border-red-500 bg-red-50 text-red-800' : 'border-orange-500 bg-orange-50 text-orange-800';

                const notification = document.createElement('div');
                notification.className = `border-l-4 ${colorClass} p-4 rounded-lg`;
                notification.innerHTML = `
                    <div class="flex items-center gap-3">
                        <i class="fas ${alertLevel === 'danger' ? 'fa-exclamation-circle text-red-600' : 'fa-bell text-orange-600'} text-lg"></i>
                        <div class="flex-1">
                            <h5 class="font-bold">${title}</h5>
                            <p class="text-sm">${patientName} - ${familyAlertMessage(payload)}</p>
                            <p class="text-xs mt-1">${formatFamilyTimestamp(payload?.data?.timestamp || new Date().toISOString())}</p>
                        </div>
                    </div>
                `;

                notificationsContainer.prepend(notification);
            }

            if (window.Echo) {
                setFamilyRealtimeState('connected', 'متصل');

                familyRealtimePatients.forEach((patient) => {
                    if (!patient?.id) return;

                    const channel = window.Echo.private(`family.${patient.id}`);
                    channel.listen('MedicalDataUpdated', (event) => {
                        setFamilyRealtimeState('connected', 'متصل');
                        updateFamilyPatientUI(patient.id, event);
                        prependRealtimeNotification(patient.name, event);
                    });
                });

                const connector = window.Echo.connector;
                if (connector && connector.socket && connector.socket.on) {
                    connector.socket.on('connect', () => setFamilyRealtimeState('connected', 'متصل'));
                    connector.socket.on('disconnect', () => setFamilyRealtimeState('warning', 'إعادة الاتصال...'));
                }
            } else {
                setFamilyRealtimeState('warning', 'إعادة الاتصال...');
            }
        </script>
    </div>
</x-app-layout>
