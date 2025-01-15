<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RunAtNinePm extends Command
{
    // The name and signature of the console command.
    protected $signature = 'run:at-nine-pm';

    // The console command description.
    protected $description = 'Run a specific task at 9 PM every day';

    // Execute the console command.
    public function handle()
    {
        // Place your logic here.
        echo('Task ran at 9 PM');
        // Add your function or request here.
    }
}
