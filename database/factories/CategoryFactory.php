<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            ['name' => 'Ekonomi', 'icon' => 'lucide-trending-up'],
            ['name' => 'Sosial', 'icon' => 'lucide-users'],
            ['name' => 'Pertanian', 'icon' => 'lucide-wheat'],
            ['name' => 'Kependudukan', 'icon' => 'lucide-user-check'],
            ['name' => 'Industri', 'icon' => 'lucide-factory'],
        ];

        $category = $this->faker->randomElement($categories);

        return [
            'name' => $category['name'],
            'icon' => $category['icon'],
        ];
    }
}
