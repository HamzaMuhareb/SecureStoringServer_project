<?php

// database/factories/ActivityFactory.php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Service;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition()
    {
        $employeeIds = Employee::pluck('id')->toArray();
        $serviceIds = Service::pluck('id')->toArray();

        return [
            'employee_id' => $this->faker->randomElement($employeeIds),
            'service_id' => $this->faker->randomElement($serviceIds),
            'day_date' => $this->faker->dateTimeBetween('-3 month', 'now'),
            'duration' => $this->faker->numberBetween(5, 12),
            // Add more attributes as needed
        ];
    }
}
