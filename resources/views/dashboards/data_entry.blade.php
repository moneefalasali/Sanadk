<x-app-layout>
    <link rel="stylesheet" href="{{ asset('css/modern-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fontawesome.css') }}">

    @include('components.header-nav', ['title' => 'إدخال البيانات'])

    <div class="p-5">
        <form method="POST" action="{{ route('data-entry.store') }}">
            @csrf

            <div class="flex items-center gap-4 mb-6">
                <a href="{{ route('dashboard') }}" class="text-gray-600"><i class="fas fa-chevron-right"></i></a>
                <h2 class="text-xl font-bold">إدخال البيانات</h2>
            </div>
            <p class="text-sm text-gray-500 mb-8 text-center">أدخل بياناتك اليومية لمساعدتنا على تقديم تنبؤات أدق</p>

            @if(session('success'))
                <div class="bg-green-50 text-green-700 p-4 rounded-2xl mb-6">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="bg-red-50 text-red-700 p-4 rounded-2xl mb-6">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card mb-6">
                <h3 class="text-center font-bold mb-6">كيف كانت جودة نومك؟</h3>
                <div class="grid grid-cols-5 gap-2 px-2">
                    @php
                        $sleepOptions = [
                            1 => ['emoji' => '😞', 'label' => 'سيئة جداً'],
                            2 => ['emoji' => '🙁', 'label' => 'سيئة'],
                            3 => ['emoji' => '😐', 'label' => 'متوسطة'],
                            4 => ['emoji' => '😊', 'label' => 'جيدة'],
                            5 => ['emoji' => '🤩', 'label' => 'ممتازة'],
                        ];
                        $selectedSleep = old('sleep_quality', $todayEntry->sleep_quality ?? 4);
                    @endphp
                    @foreach($sleepOptions as $value => $option)
                        <label class="cursor-pointer flex flex-col items-center gap-2 p-4 rounded-2xl border transition {{ $selectedSleep == $value ? 'bg-primary text-white border-primary' : 'bg-gray-50 text-gray-500 border-gray-200' }}"
                               data-active-class="bg-primary text-white border-primary"
                               data-default-class="bg-gray-50 text-gray-500 border-gray-200">
                            <input type="radio" name="sleep_quality" value="{{ $value }}" class="hidden" {{ $selectedSleep == $value ? 'checked' : '' }}>
                            <span class="text-2xl">{{ $option['emoji'] }}</span>
                            <span class="text-[10px]">{{ $option['label'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="card mb-6">
                <h3 class="text-center font-bold mb-6">مستوى التوتر لديك اليوم؟</h3>
                @php $selectedStress = old('stress_level', $todayEntry->stress_level ?? 3); @endphp
                <input id="stressRange" type="range" name="stress_level" min="1" max="5" value="{{ $selectedStress }}" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-primary">
                <div class="flex justify-between mt-2 text-[10px] text-gray-400">
                    <span>1</span>
                    <span>المستوى: <span id="stressValue">{{ $selectedStress }}</span></span>
                    <span>5</span>
                </div>
            </div>

            <div class="card mb-6">
                <h3 class="text-center font-bold mb-6">هل أخذت أدويتك اليوم؟</h3>
                @php $medicationTaken = old('medication_taken', $todayEntry?->medication_taken ? 1 : 0); @endphp
                <div class="grid grid-cols-2 gap-4">
                    <label class="cursor-pointer py-3 rounded-xl border text-center transition {{ $medicationTaken == 0 ? 'border-gray-300 bg-gray-100 text-gray-900' : 'border-gray-200 text-gray-500' }}"
                           data-active-class="border-gray-300 bg-gray-100 text-gray-900"
                           data-default-class="border-gray-200 text-gray-500">
                        <input type="radio" name="medication_taken" value="0" class="hidden" {{ $medicationTaken == 0 ? 'checked' : '' }}>
                        لا
                    </label>
                    <label class="cursor-pointer py-3 rounded-xl border text-center transition {{ $medicationTaken == 1 ? 'border-secondary bg-green-50 text-secondary font-bold' : 'border-gray-200 text-gray-500' }}"
                           data-active-class="border-secondary bg-green-50 text-secondary font-bold"
                           data-default-class="border-gray-200 text-gray-500">
                        <input type="radio" name="medication_taken" value="1" class="hidden" {{ $medicationTaken == 1 ? 'checked' : '' }}>
                        نعم
                    </label>
                </div>
            </div>

            <div class="card mb-8">
                <h3 class="text-center font-bold mb-6">النشاط البدني اليوم</h3>
                @php
                    $selectedActivity = old('activity_level', $todayEntry->activity_level ?? 'medium');
                    $selectedActivity = in_array($selectedActivity, ['نشط', 'high'], true) ? 'high' : (in_array($selectedActivity, ['متوسط', 'medium'], true) ? 'medium' : 'low');
                @endphp
                <div class="grid grid-cols-3 gap-2">
                    <label class="cursor-pointer flex flex-col items-center gap-2 p-4 rounded-2xl text-center transition {{ $selectedActivity === 'low' ? 'bg-primary text-white' : 'bg-gray-50 text-gray-400' }}"
                           data-active-class="bg-primary text-white"
                           data-default-class="bg-gray-50 text-gray-400">
                        <input type="radio" name="activity_level" value="low" class="hidden" {{ $selectedActivity === 'low' ? 'checked' : '' }}>
                        <i class="fas fa-walking"></i>
                        <span class="text-xs">منخفض</span>
                    </label>
                    <label class="cursor-pointer flex flex-col items-center gap-2 p-4 rounded-2xl text-center transition {{ $selectedActivity === 'medium' ? 'bg-primary text-white' : 'bg-gray-50 text-gray-400' }}"
                           data-active-class="bg-primary text-white"
                           data-default-class="bg-gray-50 text-gray-400">
                        <input type="radio" name="activity_level" value="medium" class="hidden" {{ $selectedActivity === 'medium' ? 'checked' : '' }}>
                        <i class="fas fa-running"></i>
                        <span class="text-xs">متوسط</span>
                    </label>
                    <label class="cursor-pointer flex flex-col items-center gap-2 p-4 rounded-2xl text-center transition {{ $selectedActivity === 'high' ? 'bg-primary text-white' : 'bg-gray-50 text-gray-400' }}"
                           data-active-class="bg-primary text-white"
                           data-default-class="bg-gray-50 text-gray-400">
                        <input type="radio" name="activity_level" value="high" class="hidden" {{ $selectedActivity === 'high' ? 'checked' : '' }}>
                        <i class="fas fa-biking"></i>
                        <span class="text-xs">نشط</span>
                    </label>
                </div>
            </div>

            <button type="submit" class="btn-modern">حفظ البيانات</button>
        </form>
    </div>

    <script>
        const stressRange = document.getElementById('stressRange');
        const stressValue = document.getElementById('stressValue');
        if (stressRange && stressValue) {
            stressRange.addEventListener('input', () => {
                stressValue.textContent = stressRange.value;
            });
        }

        function refreshRadioGroup(name) {
            document.querySelectorAll(`input[name="${name}"]`).forEach(input => {
                const label = input.closest('label');
                if (!label) return;
                const activeClasses = label.dataset.activeClass?.split(' ').filter(Boolean) || [];
                const defaultClasses = label.dataset.defaultClass?.split(' ').filter(Boolean) || [];
                if (input.checked) {
                    activeClasses.forEach(cls => label.classList.add(cls));
                    defaultClasses.forEach(cls => label.classList.remove(cls));
                } else {
                    defaultClasses.forEach(cls => label.classList.add(cls));
                    activeClasses.forEach(cls => label.classList.remove(cls));
                }
            });
        }

        function bindInteractiveRadios() {
            document.querySelectorAll('input[type="radio"]').forEach(input => {
                input.addEventListener('change', () => {
                    refreshRadioGroup(input.name);
                });
            });

            ['sleep_quality', 'medication_taken', 'activity_level'].forEach(refreshRadioGroup);
        }

        bindInteractiveRadios();
    </script>

    <!-- Bottom Navigation -->
    @include('components.bottom-nav', ['active' => 'data-entry'])
            <i class="fas fa-home"></i>
            <span>الرئيسية</span>
        </a>
        <a href="{{ route('reports') }}" class="nav-item">
            <i class="fas fa-chart-bar"></i>
            <span>التقارير</span>
        </a>
        <a href="{{ route('data-entry') }}" class="nav-item active">
            <i class="fas fa-edit"></i>
            <span>إدخال البيانات</span>
        </a>
        <a href="{{ route('seizures') }}" class="nav-item">
            <i class="fas fa-clipboard-list"></i>
            <span>السجل</span>
        </a>
        <a href="{{ route('settings') }}" class="nav-item">
            <i class="fas fa-cog"></i>
            <span>الإعدادات</span>
        </a>
    </div>
</x-app-layout>
