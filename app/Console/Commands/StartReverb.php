<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class StartReverb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reverb:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the Reverb / laravel-websockets server (wrapper)';

    public function handle()
    {
        $this->info('Starting Reverb (laravel-websockets) server...');

        // Try to run the built-in websockets serve command if available
        $php = PHP_BINARY;
        $cmd = [$php, 'artisan', 'websockets:serve'];

        $process = new Process($cmd);
        $process->setTty(true);

        try {
            $process->run(function ($type, $buffer) {
                echo $buffer;
            });
        } catch (\Throwable $e) {
            $this->error('Failed to start websockets: ' . $e->getMessage());
            $this->line('If you do not have beyondcode/laravel-websockets installed, run:');
            $this->line('  composer require beyondcode/laravel-websockets');
            return 1;
        }

        return 0;
    }
}
