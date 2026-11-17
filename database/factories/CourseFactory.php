<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Course> */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Inglês', 'Cálculo', 'Python', 'História da Arte']).' '.fake()->numberBetween(1, 99),
            'type' => 'free',
            'color' => '#3047c9',
            'status' => 'active',
        ];
    }
}
