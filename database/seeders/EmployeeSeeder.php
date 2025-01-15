<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Employee; 

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create sample employee data
        Employee::create([
            'nickname' => 'John Doe',
            'phone_number' => '1234567890',
            'total_score' => 100,
        ]);

        Employee::create([
            'nickname' => 'Jane Smith',
            'phone_number' => '9876543210',
            'total_score' => 90,
        ]);

        // Add more employee records as needed
    }
}
