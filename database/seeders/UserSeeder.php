<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        // Creating 10 institutions
        for ($i = 0; $i < 10; $i++) {
            User::create([
                'name' => $faker->company,
                'password' => bcrypt('password'), // Default password
                'national_id' => $faker->unique()->numerify('###########'),
                'phone_number' => $faker->phoneNumber,
                'birth_date' => $faker->date(),
                'role' => 'institution',
            ]);
        }

        // Creating 10 individuals
        for ($i = 0; $i < 10; $i++) {
            User::create([
                'name' => $faker->name,
                'password' => bcrypt('password'), // Default password
                'national_id' => $faker->unique()->numerify('###########'),
                'phone_number' => $faker->phoneNumber,
                'birth_date' => $faker->date(),
                'role' => 'individual',
            ]);
        }
    }
}
