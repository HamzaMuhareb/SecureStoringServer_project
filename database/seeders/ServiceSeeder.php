<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Service;

class ServiceSeeder extends Seeder
{
 /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create sample service data
        Service::create([
            'name' => 'Service 1',
            'description' => 'Description for Service 1',
            'points_number' => 10,
            'duration_minutes' => 60,
        ]);

        Service::create([
            'name' => 'Service 2',
            'description' => 'Description for Service 2',
            'points_number' => 15,
            'duration_minutes' => 45,
        ]);

        // Add more service records as needed
    }
}
