<?php

namespace App\Console\Commands;

use App\Models\EmergencyContact;
use App\Models\PatientDoctor;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class RunRealtimeSimulation extends Command
{
    protected $signature = 'simulation:realtime
        {--base-url=http://127.0.0.1:8000}
        {--patient-email=patient@sanadk.com}
        {--doctor-email=doctor@sanadk.com}
        {--family-email=family@sanadk.com}
        {--password=password}
        {--wait=12}';

    protected $description = 'Run a real end-to-end medical device simulation through the API, queue, and event pipeline.';

    public function handle(): int
    {
        $baseUrl = rtrim($this->option('base-url'), '/');
        $password = $this->option('password');

        $this->info("Starting realtime simulation against {$baseUrl}");

        $patient = $this->ensureUser('patient', $this->option('patient-email'), $password);
        $doctor = $this->ensureUser('doctor', $this->option('doctor-email'), $password);
        $family = $this->ensureUser('family', $this->option('family-email'), $password);

        $this->ensureDoctorRelation($patient, $doctor);
        $this->ensureFamilyRelation($patient, $family);

        $token = $patient->createToken('simulation-token')->plainTextToken;
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];

        $this->ensureServerIsReachable($baseUrl);

        $payloads = [
            'polar' => [
                'route' => '/api/devices/polar/data',
                'payload' => [
                    'heart_rate' => 92,
                    'rr_interval' => 0.82,
                    'hrv' => 48,
                    'device_id' => 'polar-sim-' . Str::random(6),
                    'battery_level' => 94,
                    'signal_quality' => 100,
                ],
            ],
            'emotiv' => [
                'route' => '/api/devices/emotiv/data',
                'payload' => [
                    'AF3' => 12.4,
                    'AF4' => 11.6,
                    'F3' => 8.3,
                    'F4' => 9.1,
                    'FC5' => 7.8,
                    'FC6' => 8.1,
                    'P7' => 10.2,
                    'P8' => 9.7,
                    'O1' => 6.4,
                    'O2' => 7.0,
                    'session_id' => 'emotiv-session-' . Str::random(6),
                ],
            ],
            'esp32' => [
                'route' => '/api/devices/esp32/data',
                'payload' => [
                    'heart_rate' => 88,
                    'oxygen_level' => 97,
                    'temperature' => 36.7,
                    'bp_systolic' => 118,
                    'bp_diastolic' => 76,
                    'device_id' => 'esp32-sim-' . Str::random(6),
                    'signal_quality' => 98,
                ],
            ],
        ];

        foreach ($payloads as $device => $config) {
            $response = Http::withHeaders($headers)
                ->timeout(15)
                ->retry(2, 250)
                ->post("{$baseUrl}{$config['route']}", $config['payload']);

            if (! $response->successful()) {
                $this->error("Failed to send {$device} payload: " . $response->body());
                return 1;
            }

            $this->line("Queued {$device} payload: " . $response->json('message', 'ok'));
        }

        $worker = new Process([
            PHP_BINARY,
            'artisan',
            'queue:work',
            '--once',
            '--queue=device-processing',
            '--stop-when-empty',
        ]);

        $worker->setWorkingDirectory(base_path());
        $worker->run();

        if (! $worker->isSuccessful()) {
            $this->error("Queue worker failed: " . $worker->getErrorOutput());
            return 1;
        }

        $this->info($worker->getOutput());

        $this->verifyPersistedData($patient);
        $this->verifyWebDashboardReachability($baseUrl, $patient);

        $this->info('Realtime simulation completed successfully.');

        return 0;
    }

    protected function ensureUser(string $role, string $email, string $password): User
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = User::create([
                'name' => ucfirst($role) . ' Simulation User',
                'email' => $email,
                'password' => Hash::make($password),
                'role' => $role,
                'phone' => '0500000000',
            ]);

            $this->line("Created {$role} user {$user->email}");
            return $user;
        }

        if ($user->role !== $role) {
            $user->update(['role' => $role]);
        }

        return $user;
    }

    protected function ensureDoctorRelation(User $patient, User $doctor): void
    {
        $exists = PatientDoctor::where('patient_id', $patient->id)
            ->where('doctor_id', $doctor->id)
            ->exists();

        if (! $exists) {
            PatientDoctor::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'is_active' => true,
            ]);

            $this->line("Linked patient {$patient->email} to doctor {$doctor->email}");
        }
    }

    protected function ensureFamilyRelation(User $patient, User $family): void
    {
        $relation = EmergencyContact::where('user_id', $patient->id)
            ->where('contact_user_id', $family->id)
            ->first();

        if (! $relation) {
            EmergencyContact::create([
                'user_id' => $patient->id,
                'contact_user_id' => $family->id,
                'name' => $family->name,
                'phone' => $family->phone,
                'relationship' => 'Simulation family member',
                'notify_on_prediction' => true,
                'notify_on_seizure' => true,
                'status' => 'accepted',
            ]);

            $this->line("Created accepted family relation for {$family->email}");
            return;
        }

        $relation->update(['status' => 'accepted']);
    }

    protected function ensureServerIsReachable(string $baseUrl): void
    {
        $response = Http::timeout(3)->get($baseUrl);

        if (! $response->successful()) {
            $this->error("Unable to reach {$baseUrl}. Start the local server with `php artisan serve --host=127.0.0.1 --port=8000`.");
            throw new \RuntimeException('Application server is not reachable.');
        }
    }

    protected function verifyPersistedData(User $patient): void
    {
        $recentVitalSigns = DB::table('vital_signs')
            ->where('user_id', $patient->id)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->orderByDesc('created_at')
            ->get();

        if ($recentVitalSigns->isEmpty()) {
            $this->error('No recent vital signs were persisted for the patient.');
            throw new \RuntimeException('Patient vital signs were not persisted.');
        }

        $this->info('Persisted ' . $recentVitalSigns->count() . ' vital signs for the patient.');

        $recentEeg = DB::table('eeg_signals')
            ->where('user_id', $patient->id)
            ->where('timestamp', '>=', now()->subMinutes(5))
            ->count();

        if ($recentEeg === 0) {
            $this->error('No recent EEG records were persisted for the patient.');
            throw new \RuntimeException('EEG data was not persisted.');
        }

        $this->info('Persisted ' . $recentEeg . ' EEG rows for the patient.');
    }

    protected function verifyWebDashboardReachability(string $baseUrl, User $patient): void
    {
        $dashboard = Http::timeout(5)->withHeaders([
            'Accept' => 'text/html',
        ])->get("{$baseUrl}/dashboard");

        if (! $dashboard->successful()) {
            $this->error('Unable to render the dashboard page.');
            throw new \RuntimeException('Dashboard page is not reachable.');
        }

        if (! str_contains($dashboard->body(), 'patient')) {
            $this->warn('Dashboard HTML was returned, but the patient dashboard payload could not be confirmed.');
            return;
        }

        $this->info('Dashboard page is reachable and contains patient dashboard markup.');
    }
}
