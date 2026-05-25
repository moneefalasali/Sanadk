<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // EEG Signals Table
        Schema::create('eeg_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('af3', 10, 4)->nullable();
            $table->decimal('af4', 10, 4)->nullable();
            $table->decimal('f3', 10, 4)->nullable();
            $table->decimal('f4', 10, 4)->nullable();
            $table->decimal('fc5', 10, 4)->nullable();
            $table->decimal('fc6', 10, 4)->nullable();
            $table->decimal('p7', 10, 4)->nullable();
            $table->decimal('p8', 10, 4)->nullable();
            $table->decimal('o1', 10, 4)->nullable();
            $table->decimal('o2', 10, 4)->nullable();
            $table->string('device_type')->default('emotiv');
            $table->string('session_id')->nullable();
            $table->integer('signal_quality')->default(100);
            $table->json('artifacts')->nullable();
            $table->timestamp('timestamp')->useCurrent();
            $table->timestamps();

            $table->index('user_id');
            $table->index('session_id');
            $table->index('timestamp');
        });

        // Device Connections Table
        Schema::create('device_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('device_type'); // polar, emotiv, esp32
            $table->string('device_id')->unique();
            $table->string('device_name')->nullable();
            $table->boolean('is_connected')->default(false);
            $table->integer('battery_level')->nullable();
            $table->integer('signal_quality')->default(100);
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamp('last_disconnected_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('device_type');
        });

        // Real-time Alerts Table
        Schema::create('real_time_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('alert_type'); // seizure_detected, high_heart_rate, low_oxygen, etc.
            $table->string('severity'); // low, medium, high, critical
            $table->text('message');
            $table->json('vital_signs')->nullable();
            $table->json('eeg_data')->nullable();
            $table->boolean('is_acknowledged')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('alert_type');
            $table->index('severity');
        });

        // Device Sessions Table
        Schema::create('device_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->unique();
            $table->string('device_type');
            $table->string('device_id');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('total_data_points')->default(0);
            $table->json('session_summary')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('session_id');
        });

        // Medical Reports Table
        Schema::create('medical_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('doctor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('report_type'); // session_report, daily_report, weekly_report
            $table->text('summary');
            $table->json('vital_signs_data')->nullable();
            $table->json('eeg_data')->nullable();
            $table->json('seizure_events')->nullable();
            $table->json('recommendations')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->index('user_id');
            $table->index('doctor_id');
            $table->index('report_type');
        });

        // Update existing vital_signs table
        Schema::table('vital_signs', function (Blueprint $table) {
            if (!Schema::hasColumn('vital_signs', 'device_type')) {
                $table->string('device_type')->nullable()->after('temperature');
            }
            if (!Schema::hasColumn('vital_signs', 'device_id')) {
                $table->string('device_id')->nullable()->after('device_type');
            }
            if (!Schema::hasColumn('vital_signs', 'rr_interval')) {
                $table->decimal('rr_interval', 10, 4)->nullable()->after('heart_rate');
            }
            if (!Schema::hasColumn('vital_signs', 'hrv')) {
                $table->decimal('hrv', 10, 4)->nullable()->after('rr_interval');
            }
            if (!Schema::hasColumn('vital_signs', 'blood_pressure_systolic')) {
                $table->integer('blood_pressure_systolic')->nullable()->after('oxygen_level');
            }
            if (!Schema::hasColumn('vital_signs', 'blood_pressure_diastolic')) {
                $table->integer('blood_pressure_diastolic')->nullable()->after('blood_pressure_systolic');
            }
            if (!Schema::hasColumn('vital_signs', 'signal_quality')) {
                $table->integer('signal_quality')->default(100)->after('blood_pressure_diastolic');
            }
            if (!Schema::hasColumn('vital_signs', 'timestamp')) {
                $table->timestamp('timestamp')->useCurrent()->after('signal_quality');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_reports');
        Schema::dropIfExists('device_sessions');
        Schema::dropIfExists('real_time_alerts');
        Schema::dropIfExists('device_connections');
        Schema::dropIfExists('eeg_signals');

        // Remove columns from vital_signs
        Schema::table('vital_signs', function (Blueprint $table) {
            $table->dropColumn([
                'device_type',
                'device_id',
                'rr_interval',
                'hrv',
                'blood_pressure_systolic',
                'blood_pressure_diastolic',
                'signal_quality',
                'timestamp',
            ]);
        });
    }
};
