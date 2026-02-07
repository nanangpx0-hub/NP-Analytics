<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Ekonomi',
                'icon' => 'lucide-trending-up',
            ],
            [
                'name' => 'Sosial',
                'icon' => 'lucide-users',
            ],
            [
                'name' => 'Pertanian',
                'icon' => 'lucide-wheat',
            ],
            [
                'name' => 'Kependudukan',
                'icon' => 'lucide-user-check',
            ],
            [
                'name' => 'Industri',
                'icon' => 'lucide-factory',
            ],
            [
                'name' => 'Infrastruktur',
                'icon' => 'lucide-building-2',
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
