<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Indicator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicatorTest extends TestCase
{
    use RefreshDatabase;
    /** @return \App\Models\Category */
    private function createCategory(): mixed
    {
        return Category::create([
            'name' => 'Ekonomi & PDRB',
            'icon' => null,
        ]);
    }

    /** @return \App\Models\Indicator */
    private function makeIndicator(array $attributes = []): mixed
    {
        $category = $this->createCategory();

        return $category->indicators()->create(array_merge([
            'title' => 'Indicator Uji',
            'value' => 100,
            'unit' => '%',
            'year' => 2025,
            'trend' => null,
            'is_higher_better' => true,
        ], $attributes));
    }

    public function test_trend_color_green_when_positive_indicator_increases(): void
    {
        $indicator = $this->makeIndicator([
            'trend' => 5.0,
            'is_higher_better' => true,
        ]);

        $this->assertEquals('green', $indicator->trend_color);
        $this->assertEquals('arrow-up', $indicator->trend_icon);
        $this->assertEquals('+5.00%', $indicator->formatted_trend);
    }

    public function test_trend_color_red_when_positive_indicator_decreases(): void
    {
        $indicator = $this->makeIndicator([
            'trend' => -3.5,
            'is_higher_better' => true,
        ]);

        $this->assertEquals('red', $indicator->trend_color);
        $this->assertEquals('arrow-down', $indicator->trend_icon);
        $this->assertEquals('-3.50%', $indicator->formatted_trend);
    }

    public function test_trend_color_green_when_negative_indicator_decreases(): void
    {
        $indicator = $this->makeIndicator([
            'trend' => -1.2,
            'is_higher_better' => false,
        ]);

        $this->assertEquals('green', $indicator->trend_color);
        $this->assertEquals('arrow-down', $indicator->trend_icon);
    }

    public function test_trend_color_red_when_negative_indicator_increases(): void
    {
        $indicator = $this->makeIndicator([
            'trend' => 2.0,
            'is_higher_better' => false,
        ]);

        $this->assertEquals('red', $indicator->trend_color);
        $this->assertEquals('arrow-up', $indicator->trend_icon);
    }

    public function test_trend_color_gray_when_trend_is_zero(): void
    {
        $indicator = $this->makeIndicator(['trend' => 0]);

        $this->assertEquals('gray', $indicator->trend_color);
        $this->assertEquals('minus', $indicator->trend_icon);
        $this->assertEquals('0.00%', $indicator->formatted_trend);
    }

    public function test_trend_color_gray_when_trend_is_null(): void
    {
        $indicator = $this->makeIndicator(['trend' => null]);

        $this->assertEquals('gray', $indicator->trend_color);
        $this->assertEquals('minus', $indicator->trend_icon);
        $this->assertEquals('-', $indicator->formatted_trend);
    }

    public function test_formatted_value_includes_unit(): void
    {
        $indicator = $this->makeIndicator([
            'value' => 1234.5,
            'unit' => 'Milyar',
        ]);

        $this->assertEquals('1,234.50 Milyar', $indicator->formatted_value);
    }

    public function test_uuid_is_generated_automatically_on_create(): void
    {
        $indicator = $this->makeIndicator();

        $this->assertNotNull($indicator->uuid);
        $this->assertTrue((bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $indicator->uuid));
    }

    public function test_indicator_belongs_to_category(): void
    {
        $category = $this->createCategory();
        $indicator = $category->indicators()->create([
            'title' => 'X', 'value' => 1, 'unit' => '%', 'year' => 2025,
            'is_higher_better' => true,
        ]);

        $this->assertEquals($category->id, $indicator->category->id);
        $this->assertTrue($category->indicators->contains($indicator));
    }
}
