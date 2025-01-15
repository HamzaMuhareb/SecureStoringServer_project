<?php

namespace Database\Factories;

use App\Models\EmployeeDailyRecord;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeDailyRecordFactory extends Factory
{
    protected $model = EmployeeDailyRecord::class;

    public function definition()
    {
        $employeeIds = Employee::pluck('id')->toArray();

        $hour = $this->faker->numberBetween(8, 11);
        $minute = $this->faker->numberBetween(0, 59);
        $checkInTime = sprintf('%02d:%02d:00', $hour, $minute);

        return [
            'employee_id' => $this->faker->randomElement($employeeIds),
            'check_in' => $checkInTime,
            'customers_number' => $this->faker->numberBetween(0, 50),
            'purchases_attempt' => $this->faker->numberBetween(0, 10),
            'changing_clothes_attempt' => $this->faker->numberBetween(0, 5),
            'disagreement_over_customer' => json_encode([$this->faker->randomElement($employeeIds)]),
            'customer_alone' => json_encode([$this->faker->time()]),
            'score' => $this->faker->numberBetween(0, 100),
            'record_date' => $this->faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
        ];
    }
}
