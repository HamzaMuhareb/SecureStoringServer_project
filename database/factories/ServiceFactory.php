<?php
// database/factories/ServiceFactory.php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'description' => $this->faker->sentence,
            'points_number' => $this->faker->numberBetween(1, 100),
            'time_threshold' => $this->faker->numberBetween(3, 20),
            // Add more attributes as needed
        ];
    }
}
