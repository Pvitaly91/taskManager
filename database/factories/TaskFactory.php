<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
      
        return [
            'name' => fake()->text(),
            'description' => fake()->text(),
            'status' => rand(0,1),
            'created_at' => now(),
            'updated_at' => now(),
            'end_date'=> (rand(0,1) ==1)?date("Y-m-d H:i:s",time()+rand(-99999999,0)):NULL
        ];
    }
}
