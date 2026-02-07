<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Indicator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Indicator>
 */
class IndicatorFactory extends Factory
{
    protected $model = Indicator::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $indicators = [
            ['title' => 'Produksi Padi', 'unit' => 'ton', 'is_higher_better' => true],
            ['title' => 'PDRB', 'unit' => 'miliar rupiah', 'is_higher_better' => true],
            ['title' => 'Tingkat Kemiskinan', 'unit' => '%', 'is_higher_better' => false],
            ['title' => 'Tingkat Pengangguran', 'unit' => '%', 'is_higher_better' => false],
            ['title' => 'Inflasi', 'unit' => '%', 'is_higher_better' => false],
            ['title' => 'IPM', 'unit' => 'poin', 'is_higher_better' => true],
            ['title' => 'Rata-rata Lama Sekolah', 'unit' => 'tahun', 'is_higher_better' => true],
            ['title' => 'Angka Harapan Hidup', 'unit' => 'tahun', 'is_higher_better' => true],
        ];

        $indicator = $this->faker->randomElement($indicators);

        return [
            'category_id' => Category::factory(),
            'title' => $indicator['title'],
            'value' => $this->faker->randomFloat(2, 1, 10000),
            'unit' => $indicator['unit'],
            'year' => $this->faker->numberBetween(2020, 2025),
            'trend' => $this->faker->randomFloat(2, -15, 15),
            'is_higher_better' => $indicator['is_higher_better'],
            'description' => $this->faker->paragraph(),
            'image_path' => null,
        ];
    }

    /**
     * Indicate that this is a positive indicator (higher is better).
     */
    public function positive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_higher_better' => true,
        ]);
    }

    /**
     * Indicate that this is a negative indicator (lower is better).
     */
    public function negative(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_higher_better' => false,
        ]);
    }
}
