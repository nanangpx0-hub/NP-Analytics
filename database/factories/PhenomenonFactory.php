<?php

namespace Database\Factories;

use App\Models\Indicator;
use App\Models\Phenomenon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Phenomenon>
 */
class PhenomenonFactory extends Factory
{
    protected $model = Phenomenon::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'indicator_id' => Indicator::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraphs(2, true),
            'impact' => $this->faker->randomElement(['positive', 'negative']),
        ];
    }

    /**
     * Indicate that the impact is positive.
     */
    public function positive(): static
    {
        return $this->state(fn (array $attributes) => [
            'impact' => 'positive',
        ]);
    }

    /**
     * Indicate that the impact is negative.
     */
    public function negative(): static
    {
        return $this->state(fn (array $attributes) => [
            'impact' => 'negative',
        ]);
    }
}
