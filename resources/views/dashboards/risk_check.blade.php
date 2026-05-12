<x-app-layout>
    <link rel="stylesheet" href="{{ asset('css/modern-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fontawesome.css') }}">

    @php
        $riskScoreValue = is_numeric($riskScore) ? (int) round($riskScore) : 50;
        $riskLabel = $riskLabel ?? ($riskScoreValue >= 70 ? 'عالي' : ($riskScoreValue >= 40 ? 'متوسط' : 'منخفض'));
        $riskClasses = $riskScoreValue >= 70 ? 'bg-orange-400 text-orange-700' : ($riskScoreValue >= 40 ? 'bg-yellow-100 text-yellow-700' : 'bg-emerald-100 text-emerald-700');
        $riskDescription = $riskScoreValue >= 70 ? 'الخطر مرتفع، احرص على البقاء تحت المراقبة الطبية.' : ($riskScoreValue >= 40 ? 'الخطر متوسط، تابع حالتك وارتاح أكثر.' : 'الخطر منخفض، الوضع مستقر حالياً.');
        $riskAdvice = match($riskLabel) {
            'عالي' => 'اتصل بطبيبك أو الدعم الطبي فوراً، واحتفظ بالمياه والأدوية بجانبك.',
            'متوسط' => 'احرص على تناول الأدوية في مواعيدها وابتعد عن الإجهاد.',
            default => 'تابع حالتك بشكل دوري وحافظ على الراحة والنوم الجيد.',
        };
        $adviceItems = match($riskLabel) {
            'عالي' => [
                'اتصل بطبيبك أو الدعم الطبي فوراً.',
                'اشرب ماءً وافتح النافذة لتجديد الهواء.',
                'استرح ولا تبذل جهداً كبيراً.',
                'راقب تنفسك ومعدل النبض باستمرار.',
            ],
            'متوسط' => [
                'احرص على تناول الأدوية في مواعيدها.',
                'خذ راحة إضافية وابتعد عن الإجهاد.',
                'تابع حالتك خلال اليوم وأعد القياس لاحقاً.',
                'احفظ أرقام الطوارئ بالقرب منك.',
            ],
            default => [
                'استمر في نمط حياة صحي ومتابعة القياسات.',
                'تأكد من شرب الماء بانتظام.',
                'احصل على قسط كافٍ من النوم الليلة.',
                'سجل أي تغيير في حالتك أو أعراض جديدة.',
            ],
        };
        $lastSeizureText = $lastSeizure ? $lastSeizure->created_at->diffForHumans() : 'لا يوجد';
        $lastUpdateText = $latestVitals ? ($latestVitals->created_at->diffForHumans()) : 'غير متاح';
        $timeToEventText = $timeToEvent ?? 'غير محدد';
    @endphp

    @include('components.header-nav', ['title' => 'فحص الخطر'])

    <div class="p-5">
        <div class="flex items-center gap-4 mb-8">
            <a href="{{ route('dashboard') }}" class="text-gray-600"><i class="fas fa-chevron-right"></i></a>
            <h2 class="text-xl font-bold">فحص الخطر</h2>
        </div>

        <div class="card text-center py-10">
            <div class="relative w-64 h-64 mx-auto mb-6">
                <div class="absolute inset-0 rounded-full border-[24px] border-gray-100"></div>
                <div id="riskCircle" class="absolute inset-0 rounded-full" style="background: conic-gradient(#4A90E2 {{ $riskScoreValue }}%, #EDF2F7 0deg);"></div>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <div class="text-5xl font-extrabold text-slate-900">{{ $riskScoreValue }}</div>
                    <div class="text-sm text-gray-500">درجة الخطر</div>
                </div>
            </div>

            <div class="inline-flex items-center justify-center gap-2 rounded-full px-4 py-2 mb-4 {{ $riskClasses }}">
                <i class="fas fa-exclamation-triangle"></i>
                <span class="font-semibold">{{ $riskLabel }}</span>
            </div>

            <p class="text-gray-600 mb-2">احتمال نوبة {{ $timeToEventText }}</p>
            <p class="text-sm text-slate-500 mb-2">{{ $riskDescription }}</p>
            <p class="text-sm text-gray-500 mb-6">{{ $riskAdvice }}</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                <div class="bg-white p-4 rounded-3xl shadow-sm">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="bg-blue-100 text-blue-700 rounded-2xl p-3"><i class="fas fa-heartbeat"></i></span>
                        <div>
                            <p class="text-xs text-gray-500">معدل النبض</p>
                            <p class="font-semibold text-lg">{{ $liveDeviceData['ecg']['heart_rate'] ?? $latestVitals?->heart_rate ?? 'غير متاح' }}</p>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">نبضة/دقيقة</p>
                </div>
                <div class="bg-white p-4 rounded-3xl shadow-sm">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="bg-red-100 text-red-700 rounded-2xl p-3"><i class="fas fa-temperature-high"></i></span>
                        <div>
                            <p class="text-xs text-gray-500">التوتر</p>
                            <p class="font-semibold text-lg">{{ $stressLevel }}</p>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">مستوى التوتر الحالي</p>
                </div>
                <div class="bg-white p-4 rounded-3xl shadow-sm">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="bg-indigo-100 text-indigo-700 rounded-2xl p-3"><i class="fas fa-running"></i></span>
                        <div>
                            <p class="text-xs text-gray-500">النشاط</p>
                            <p class="font-semibold text-lg">{{ $activityLevel }}</p>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">معدل النشاط المتوقع</p>
                </div>
                <div class="bg-white p-4 rounded-3xl shadow-sm">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="bg-emerald-100 text-emerald-700 rounded-2xl p-3"><i class="fas fa-brain"></i></span>
                        <div>
                            <p class="text-xs text-gray-500">آخر نوبة</p>
                            <p class="font-semibold text-lg">{{ $lastSeizureText }}</p>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">آخر نوبة مسجلة</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                <div class="bg-white p-4 rounded-3xl shadow-sm">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="bg-cyan-100 text-cyan-700 rounded-2xl p-3"><i class="fas fa-tint"></i></span>
                        <div>
                            <p class="text-xs text-gray-500">تشبع الأكسجين</p>
                            <p class="font-semibold text-lg">{{ $liveDeviceData['ecg']['oxygen_level'] ?? $latestVitals?->oxygen_level ? $latestVitals->oxygen_level . '%' : 'غير متاح' }}</p>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">نسبة الأوكسجين في الدم</p>
                </div>
                <div class="bg-white p-4 rounded-3xl shadow-sm">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="bg-orange-100 text-orange-700 rounded-2xl p-3"><i class="fas fa-thermometer-half"></i></span>
                        <div>
                            <p class="text-xs text-gray-500">درجة الحرارة</p>
                            <p class="font-semibold text-lg">{{ $liveDeviceData['ecg']['temperature'] ?? $latestVitals?->temperature ? $latestVitals->temperature . '°' : 'غير متاح' }}</p>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">درجة حرارة الجسم</p>
                </div>
            </div>

            <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-200 mb-6 text-start">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">نصائح سريعة</h3>
                        <p class="text-xs text-slate-500">مبنية على مستوى الخطر الحالي</p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $riskLabel }}</span>
                </div>
                <ul class="space-y-3 text-sm text-slate-600">
                    @foreach($adviceItems as $item)
                        <li class="flex items-start gap-2">
                            <span class="mt-1 inline-flex h-2.5 w-2.5 rounded-full bg-slate-900"></span>
                            <span>{{ $item }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-white p-4 rounded-3xl shadow-sm text-center">
                    <p class="text-xs text-gray-500 mb-2">أجهزة متصلة</p>
                    <p class="text-xl font-bold">{{ $connectedDevices }}</p>
                </div>
                <div class="bg-white p-4 rounded-3xl shadow-sm text-center">
                    <p class="text-xs text-gray-500 mb-2">أنواع الأجهزة</p>
                    <p class="text-xl font-bold">{{ $deviceTypes }}</p>
                </div>
                <div class="bg-white p-4 rounded-3xl shadow-sm text-center">
                    <p class="text-xs text-gray-500 mb-2">أفراد العائلة</p>
                    <p class="text-xl font-bold">{{ $familyContacts }}</p>
                </div>
                <div class="bg-white p-4 rounded-3xl shadow-sm text-center">
                    <p class="text-xs text-gray-500 mb-2">الأطباء المرتبطين</p>
                    <p class="text-xl font-bold">{{ $linkedDoctors }}</p>
                </div>
            </div>

            <button id="shareResult" class="btn-modern bg-secondary shadow-secondary/30 w-full max-w-xs mx-auto">مشاركة النتيجة</button>
        </div>
    </div>

    @include('components.bottom-nav', ['active' => 'risk_check'])

    <script>
        // Live device data update function
        async function fetchLiveDeviceData() {
            try {
                const response = await fetch('{{ route("devices.live-data") }}', { cache: 'no-store' });
                const data = await response.json();

                if (data.success) {
                    // Update heart rate
                    const heartRateElement = document.querySelector('.bg-blue-100 + div p.font-semibold');
                    if (heartRateElement) {
                        heartRateElement.textContent = data.heart_rate || 'غير متاح';
                    }

                    // Update oxygen
                    const oxygenElement = document.querySelector('.bg-cyan-100 + div p.font-semibold');
                    if (oxygenElement) {
                        oxygenElement.textContent = data.oxygen_level ? data.oxygen_level + '%' : 'غير متاح';
                    }

                    // Update temperature
                    const tempElement = document.querySelector('.bg-orange-100 + div p.font-semibold');
                    if (tempElement) {
                        tempElement.textContent = data.temperature ? data.temperature + '°' : 'غير متاح';
                    }

                    // Update risk score dynamically if needed
                    // This would require re-running the analysis, but for simplicity, keep static
                }
            } catch (error) {
                console.error('Error updating live data:', error);
            }
        }

        // Update live data every 30 seconds
        setInterval(fetchLiveDeviceData, 30000);

        // Initial load
        fetchLiveDeviceData();

        document.getElementById('shareResult')?.addEventListener('click', async function () {
            const shareTitle = 'نتيجة فحص الخطر';
            const shareText = `درجة الخطر: {{ $riskLabel }}\nنسبة الخطر: {{ $riskScoreValue }}%\nالوقت المتوقع: {{ $timeToEventText }}\nالتوصية: {{ $riskAdvice }}\nالرابط: ${window.location.href}`;

            try {
                if (navigator.share) {
                    await navigator.share({
                        title: shareTitle,
                        text: shareText,
                        url: window.location.href
                    });
                } else if (navigator.clipboard) {
                    await navigator.clipboard.writeText(shareText);
                    alert('تم نسخ النتيجة إلى الحافظة. يمكنك الآن مشاركتها في تطبيق المراسلة أو البريد.');
                } else {
                    alert('لا يمكن مشاركة النتائج مباشرة من هذا المتصفح. الرجاء نسخ الرابط يدوياً.');
                }
            } catch (error) {
                console.error('Share error:', error);
                alert('حدث خطأ أثناء محاولة مشاركة النتيجة. حاول مرة أخرى.');
            }
        });
    </script>
</x-app-layout>
