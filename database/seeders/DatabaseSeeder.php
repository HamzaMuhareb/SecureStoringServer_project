<?php

use Illuminate\Database\Seeder;
use App\Models\Service;
use App\Models\Employee;
use App\Models\EmployeeDailyRecord;
use App\Models\Photo;
class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $user = \App\Models\User::factory()->create();
        $user->is_admin = true;
        $user->first_name = "John";
        $user->email = "john@example.com";
        $user->save();
        Service::factory(5)->create();
        Employee::factory(10)->create();
        EmployeeDailyRecord::factory()->count(600)->create();
        Photo::factory(30)->create();
    }
}
