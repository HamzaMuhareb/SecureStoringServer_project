<?php

namespace Database\Factories;
use App\Models\Photo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PhotoFactory extends Factory
{
    protected $model = Photo::class;

    public function definition()
    {
        return [
            'employee_id' => $this->faker->numberBetween(1, 10),
            'photo_path' => 'photos/' . Str::random(10) . '.jpg',
        ];
    }
}
