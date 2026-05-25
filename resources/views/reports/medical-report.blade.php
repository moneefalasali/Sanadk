<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Report - {{ $user->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: white;
            padding: 40px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #007bff;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 14px;
        }

        .report-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }

        .info-item {
            border-left: 4px solid #007bff;
            padding-left: 10px;
        }

        .info-item label {
            display: block;
            color: #666;
            font-size: 12px;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .info-item value {
            display: block;
            color: #333;
            font-size: 14px;
        }

        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }

        .section-title {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 3px;
            font-size: 16px;
            font-weight: bold;
        }

        .summary-box {
            background-color: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 3px;
        }

        .summary-box p {
            margin-bottom: 8px;
            line-height: 1.8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table thead {
            background-color: #f0f0f0;
            border-bottom: 2px solid #007bff;
        }

        table th {
            padding: 12px;
            text-align: right;
            font-weight: bold;
            color: #333;
        }

        table td {
            padding: 10px 12px;
            border-bottom: 1px solid #ddd;
        }

        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
        }

        .stat-card .label {
            display: block;
            color: #666;
            font-size: 12px;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .stat-card .value {
            display: block;
            color: #007bff;
            font-size: 24px;
            font-weight: bold;
        }

        .recommendations-list {
            list-style: none;
            padding: 0;
        }

        .recommendations-list li {
            padding: 10px 15px;
            margin-bottom: 8px;
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            border-radius: 3px;
        }

        .recommendations-list li:before {
            content: "✓ ";
            color: #28a745;
            font-weight: bold;
            margin-right: 8px;
        }

        .alert-high {
            background-color: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }

        .alert-medium {
            background-color: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }

        .alert-low {
            background-color: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 12px;
        }

        .signature-area {
            margin-top: 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        .signature-line {
            border-top: 1px solid #333;
            padding-top: 10px;
            text-align: center;
            font-size: 12px;
        }

        @media print {
            body {
                background-color: white;
            }
            .container {
                box-shadow: none;
                padding: 20px;
            }
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>تقرير طبي شامل</h1>
            <h2>SANADK Medical Report</h2>
            <p>نظام الكشف والتنبؤ بنوبات الصرع</p>
        </div>

        <!-- Report Information -->
        <div class="report-info">
            <div class="info-item">
                <label>اسم المريض / Patient Name:</label>
                <value>{{ $user->name }}</value>
            </div>
            <div class="info-item">
                <label>رقم المريض / Patient ID:</label>
                <value>{{ $user->id }}</value>
            </div>
            <div class="info-item">
                <label>نوع التقرير / Report Type:</label>
                <value>{{ ucfirst(str_replace('_', ' ', $report->report_type)) }}</value>
            </div>
            <div class="info-item">
                <label>تاريخ التقرير / Report Date:</label>
                <value>{{ $generatedAt->format('Y-m-d H:i') }}</value>
            </div>
        </div>

        <!-- Summary Section -->
        <div class="section">
            <div class="section-title">الملخص الطبي / Medical Summary</div>
            <div class="summary-box">
                {!! nl2br($report->summary) !!}
            </div>
        </div>

        <!-- Vital Signs Statistics -->
        @if($report->vital_signs_data)
        <div class="section">
            <div class="section-title">إحصائيات العلامات الحيوية / Vital Signs Statistics</div>
            
            <div class="stats-grid">
                @php
                    $vitalSigns = collect($report->vital_signs_data);
                    $avgHR = $vitalSigns->avg('heart_rate');
                    $maxHR = $vitalSigns->max('heart_rate');
                    $avgO2 = $vitalSigns->avg('oxygen_level');
                @endphp
                
                <div class="stat-card">
                    <span class="label">متوسط نبض القلب / Avg Heart Rate</span>
                    <span class="value">{{ round($avgHR, 1) }} BPM</span>
                </div>
                <div class="stat-card">
                    <span class="label">أقصى نبض / Max Heart Rate</span>
                    <span class="value">{{ round($maxHR, 1) }} BPM</span>
                </div>
                <div class="stat-card">
                    <span class="label">متوسط الأكسجين / Avg Oxygen</span>
                    <span class="value">{{ round($avgO2, 1) }}%</span>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>الوقت / Time</th>
                        <th>نبض القلب / Heart Rate</th>
                        <th>الأكسجين / Oxygen</th>
                        <th>درجة الحرارة / Temperature</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report->vital_signs_data as $vital)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($vital['created_at'])->format('H:i:s') }}</td>
                        <td>{{ $vital['heart_rate'] ?? '-' }} BPM</td>
                        <td>{{ $vital['oxygen_level'] ?? '-' }}%</td>
                        <td>{{ $vital['temperature'] ?? '-' }}°C</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Seizure Events -->
        @if($report->seizure_events && count($report->seizure_events) > 0)
        <div class="section">
            <div class="section-title">أحداث النوبات / Seizure Events</div>
            
            <table>
                <thead>
                    <tr>
                        <th>الوقت / Time</th>
                        <th>النوع / Type</th>
                        <th>متنبأ به / Predicted</th>
                        <th>الملاحظات / Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report->seizure_events as $seizure)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($seizure['created_at'])->format('Y-m-d H:i:s') }}</td>
                        <td>{{ $seizure['type'] ?? '-' }}</td>
                        <td>{{ $seizure['is_predicted'] ? 'نعم / Yes' : 'لا / No' }}</td>
                        <td>{{ $seizure['notes'] ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Recommendations -->
        @if($report->recommendations && count($report->recommendations) > 0)
        <div class="section">
            <div class="section-title">التوصيات الطبية / Medical Recommendations</div>
            
            <ul class="recommendations-list">
                @foreach($report->recommendations as $recommendation)
                <li>{{ $recommendation }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- EEG Analysis -->
        @if($report->eeg_data && count($report->eeg_data) > 0)
        <div class="section page-break">
            <div class="section-title">تحليل موجات الدماغ / EEG Analysis</div>
            
            <p style="margin-bottom: 15px;">عدد العينات المسجلة: {{ count($report->eeg_data) }} عينة</p>
            
            <table>
                <thead>
                    <tr>
                        <th>الوقت / Time</th>
                        <th>AF3</th>
                        <th>AF4</th>
                        <th>F3</th>
                        <th>F4</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report->eeg_data->take(10) as $eeg)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($eeg['timestamp'])->format('H:i:s') }}</td>
                        <td>{{ round($eeg['af3'] ?? 0, 2) }}</td>
                        <td>{{ round($eeg['af4'] ?? 0, 2) }}</td>
                        <td>{{ round($eeg['f3'] ?? 0, 2) }}</td>
                        <td>{{ round($eeg['f4'] ?? 0, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Signature Area -->
        <div class="signature-area">
            <div class="signature-line">
                <p>توقيع الطبيب / Doctor Signature</p>
                <p style="margin-top: 30px;">_____________________</p>
            </div>
            <div class="signature-line">
                <p>توقيع المريض / Patient Signature</p>
                <p style="margin-top: 30px;">_____________________</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>هذا التقرير سري وخاص بالمريض. يُرجى الاحتفاظ به في مكان آمن.</p>
            <p>This report is confidential and patient-specific. Please keep it in a safe place.</p>
            <p>تم إنشاؤه بواسطة نظام SANADK الطبي | Generated by SANADK Medical System</p>
            <p>{{ $generatedAt->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
