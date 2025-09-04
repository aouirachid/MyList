<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-1 week', 'now');
        $end = (clone $start)->modify('+2 days');

        return [
            'user_id' => User::factory(),
            'document_id' => null,
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(6),
            'startDate' => Carbon::instance($start)->format('Y-m-d H:i:s'),
            'endDate' => Carbon::instance($end)->format('Y-m-d H:i:s'),
            'priority' => $this->faker->numberBetween(1, 3),
            'parentTaskId' => null,
            'status' => 1,
        ];
    }
}


