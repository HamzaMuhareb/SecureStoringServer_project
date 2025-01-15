<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Activity;
use Carbon\Carbon;
use App\Http\Controllers\EmployeeController;

class FetchEmployeeActivities extends Command
{
    protected $signature = 'fetch:employee-activities';
    protected $description = 'Fetch employee activities from external API and store them in the database';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $Url = 'https://api.example.com/employee-activities';

        $employeeController = new EmployeeController();
        $employees = $employeeController->getAllEmployeesNamesAndIds();

        $response = Http::post($Url, ['employees' => $employees]);

        if ($response->successful()) {
            $employeesActivities = $response->json();

            foreach ($employeesActivities as $employeeActivity) {
                $employeeId = $employeeActivity['employee_id'];
                $activities = $employeeActivity['activities'];

                foreach ($activities as $activity) {
                    Activity::create([
                        'employee_id' => $employeeId,
                        'service_id' => $activity['service_id'],
                        'day_date' => Carbon::now()->toDateString(),
                        'duration' => $activity['duration'],
                    ]);
                }
            }

            $this->info('Employee activities have been fetched and stored successfully.');
        } else {
            $this->error('Failed to fetch employee activities.');
        }
    }
}
