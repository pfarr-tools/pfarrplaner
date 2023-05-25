<?php

namespace App\Console\Commands;

use App\Services\InstanceRegistryService;
use Illuminate\Console\Command;

class Ping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Ping';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ping the central instance registry';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->line('Pinging the central istances registry...');
        InstanceRegistryService::ping(true);
        return Command::SUCCESS;
    }
}
