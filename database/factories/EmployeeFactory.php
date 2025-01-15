<?php
namespace Database\Factories;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'department' => $this->faker->randomElement(['HR', 'Cashier', 'Cleaning', 'Marketing']),
            'phone_number' => $this->faker->phoneNumber,
            'address' => $this->faker->address,
            'shift_starts' => $this->faker->time(),
            'shift_ends' => $this->faker->time(),
            'info_description' => $this->faker->text,
            'salary'=> $this->faker->numberBetween(1000,10000),
            'position'=> $this->faker->word(),
            'total_score' => $this->faker->numberBetween(0, 1000),
            'birth_date' => $this->faker->date(),
        ];
    }
}
