<x-app-layout>
    <link rel="stylesheet" href="{{ asset('css/modern-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fontawesome.css') }}">
    <script src="{{ asset('js/chart.min.js') }}"></script>

    @include('components.header-nav', ['title' => 'التقارير'])

    <div class="container py-6">
        <div class="page-title">
            <div>
                <h2>التقارير</h2>
                <p class="page-subtitle">عرض الأداء، مخاطر النوبات، وتحليل العوامل المؤثرة.</p>
            </div>
            <div class="header-actions">
                <button class="btn-secondary" onclick="window.print()"><i class="fas fa-print ml-2"></i>طباعة التقرير</button>
                <button class="btn-modern"><i class="fas fa-download ml-2"></i>تنزيل</button>
            </div>
        </div>

        <div class="report-summary mb-6">
            <div class="report-item">
                <strong>{{ $seizures->count() }}</strong>
                <small>إجمالي النوبات</small>
            </div>
            <div class="report-item">
                <strong>{{ round($averageDuration, 1) }}</strong>
                <small>متوسط المدة (دقيقة)</small>
            </div>
            <div class="report-item">
                <strong>{{ $predictionRate }}%</strong>
                <small>النوبات المتوقعة</small>
            </div>
        </div>

        <div class="dashboard-card text-center mb-6">
            @php
                $reportsRiskScore = is_numeric($riskScore) ? (int) round($riskScore) : 0;
                $reportsRiskLabel = $reportsRiskScore >= 70 ? 'عالي' : ($reportsRiskScore >= 40 ? 'متوسط' : 'منخفض');
            @endphp
            <p class="text-sm text-gray-500 mb-3">مؤشر الخطر الحالي</p>
            <div class="progress-circle mb-4">
                <canvas id="riskDonutChart" width="180" height="180"></canvas>
                <div class="progress-center">
                    <strong>{{ $reportsRiskScore }}</strong>
                    <small>من 100</small>
                </div>
            </div>
            <p class="font-bold mb-1">{{ $reportsRiskLabel }}</p>
            <p class="text-sm text-gray-500">احتمال حدوث نوبة خلال 30 دقيقة القادمة {{ $reportsRiskLabel }}.</p>
        </div>

        <div class="report-toolbar mb-6">
            <div class="tabs">
                <button id="weekBtn" type="button" class="tab active" onclick="setReportPeriod('week')">أسبوع</button>
                <button id="monthBtn" type="button" class="tab" onclick="setReportPeriod('month')">شهر</button>
                <button id="quarterBtn" type="button" class="tab" onclick="setReportPeriod('quarter')">3 أشهر</button>
            </div>
            <div class="header-actions">
                <span class="info-chip">{{ $riskScore }}%</span>
                <button class="btn-secondary" onclick="window.print()"><i class="fas fa-print ml-2"></i>طباعة</button>
            </div>
        </div>

        <div class="report-card mb-6">
            <div class="card-header">
                <h3>مؤشر الخطر خلال الفترة</h3>
                <span class="tag-pill">اتجاهات</span>
            </div>
            <canvas id="riskChart" height="220"></canvas>
        </div>

        <div class="report-summary mb-6">
            <div class="report-item">
                <strong id="liveHeartRate">--</strong>
                <small>معدل النبض</small>
            </div>
            <div class="report-item">
                <strong id="liveBloodPressure">--/--</strong>
                <small>ضغط الدم</small>
            </div>
            <div class="report-item">
                <strong id="liveMuscleTension">--</strong>
                <small>توتر العضلات</small>
            </div>
        </div>

        <div class="report-card">
            <div class="card-header">
                <h3>العوامل المؤثرة</h3>
                <span class="tag-pill">تحليل ذكي</span>
            </div>
            <div class="flex flex-wrap gap-6 items-center justify-between">
                <div class="relative w-40 h-40">
                    <canvas id="factorsChart"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-[10px] text-gray-400">مستوى</span>
                        <span class="text-sm font-bold">التأثير</span>
                    </div>
                </div>
                <div class="flex-1 min-w-[220px] space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                            <span class="text-xs text-gray-500">النوم</span>
                        </div>
                        <span class="text-xs font-bold">{{ $sleepImpact }}%</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-orange-400 rounded-full"></span>
                            <span class="text-xs text-gray-500">التوتر</span>
                        </div>
                        <span class="text-xs font-bold">{{ $stressImpact }}%</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-blue-400 rounded-full"></span>
                            <span class="text-xs text-gray-500">النشاط</span>
                        </div>
                        <span class="text-xs font-bold">{{ $activityImpact }}%</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-purple-400 rounded-full"></span>
                            <span class="text-xs text-gray-500">أخرى</span>
                        </div>
                        <span class="text-xs font-bold">{{ $otherImpact }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('components.bottom-nav', ['active' => 'reports'])

    <script>
        const rootStyles = getComputedStyle(document.documentElement);
        const primaryColor = rootStyles.getPropertyValue('--primary').trim() || '#4A90E2';

        const ctxRisk = document.getElementById('riskChart').getContext('2d');
        const donutCtx = document.getElementById('riskDonutChart').getContext('2d');
        const riskLabels = @json($riskLabels);
        const riskData = @json($riskData);
        const reportsRiskScore = {{ $reportsRiskScore }};

        const periodData = {
            week: {
                labels: riskLabels,
                data: riskData
            },
            month: {
                labels: riskLabels,
                data: riskData
            },
            quarter: {
                labels: riskLabels,
                data: riskData.map(value => Math.round(value * 0.85))
            }
        };

        const riskChart = new Chart(ctxRisk, {
            type: 'line',
            data: {
                labels: periodData.month.labels,
                datasets: [{
                    label: 'مؤشر الخطر',
                    data: periodData.month.data,
                    borderColor: primaryColor,
                    backgroundColor: 'rgba(74, 144, 226, 0.14)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: primaryColor
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, max: 100, ticks: { stepSize: 25 } },
                    x: { grid: { display: false } }
                }
            }
        });

        const riskDonut = new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: ['الخطر', 'المتبقي'],
                datasets: [{
                    data: [reportsRiskScore, Math.max(0, 100 - reportsRiskScore)],
                    backgroundColor: [primaryColor, '#E5E8F5'],
                    borderWidth: 0,
                    cutout: '80%'
                }]
            },
            options: {
                plugins: { legend: { display: false }, tooltip: { enabled: false } }
            }
        });

        function setReportPeriod(period) {
            const weekBtn = document.getElementById('weekBtn');
            const monthBtn = document.getElementById('monthBtn');
            const quarterBtn = document.getElementById('quarterBtn');

            [weekBtn, monthBtn, quarterBtn].forEach(btn => btn.classList.remove('active'));

            if (period === 'week') {
                weekBtn.classList.add('active');
                riskChart.data.labels = periodData.week.labels;
                riskChart.data.datasets[0].data = periodData.week.data;
            } else if (period === 'month') {
                monthBtn.classList.add('active');
                riskChart.data.labels = periodData.month.labels;
                riskChart.data.datasets[0].data = periodData.month.data;
            } else {
                quarterBtn.classList.add('active');
                riskChart.data.labels = periodData.quarter.labels;
                riskChart.data.datasets[0].data = periodData.quarter.data;
            }
            riskChart.update();
        }

        setReportPeriod('week');

        function updateLiveData() {
            return fetch('{{ route('devices.live-data') }}', { cache: 'no-store' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('liveHeartRate').textContent = data.heart_rate || '--';
                        document.getElementById('liveBloodPressure').textContent = `${data.blood_pressure_systolic || '--'}/${data.blood_pressure_diastolic || '--'}`;
                        document.getElementById('liveMuscleTension').textContent = data.muscle_tension || '--';
                        const brainActivityElement = document.getElementById('liveBrainActivity');
                        if (brainActivityElement) {
                            brainActivityElement.textContent = data.brain_activity || '--';
                        }
                    }
                })
                .catch(error => console.error('Error updating live data:', error));
        }

        setInterval(updateLiveData, 30000);
        updateLiveData();
    </script>
</x-app-layout>
