<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Indicator;
use App\Models\Phenomenon;
use Database\Seeders\MasterStrategicDataJemberSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MasterStrategicDataJemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_strategic_data_seeder_populates_all_strategic_domains(): void
    {
        $this->seed(MasterStrategicDataJemberSeeder::class);

        // 8 Kategori strategis terakomodasi
        $this->assertEquals(8, Category::count());
        $this->assertDatabaseHas('categories', ['name' => 'Ekonomi & PDRB']);
        $this->assertDatabaseHas('categories', ['name' => 'Sosial & Kemiskinan']);
        $this->assertDatabaseHas('categories', ['name' => 'Ketenagakerjaan & SDM']);
        $this->assertDatabaseHas('categories', ['name' => 'Indeks Pembangunan Manusia']);
        $this->assertDatabaseHas('categories', ['name' => 'Pertanian & Ketahanan Pangan']);
        $this->assertDatabaseHas('categories', ['name' => 'Pariwisata & Jasa']);
        $this->assertDatabaseHas('categories', ['name' => 'Kependudukan']);
        $this->assertDatabaseHas('categories', ['name' => 'Potensi Wilayah & Desa (PODES)']);

        // Indikator empiris resmi BPS Jember
        $this->assertGreaterThanOrEqual(60, Indicator::count());
        $this->assertDatabaseHas('indicators', [
            'title' => 'PDRB ADHK (Pertumbuhan Ekonomi)',
            'year' => 2025,
            'value' => 5.47,
        ]);
        $this->assertDatabaseHas('indicators', [
            'title' => 'Tingkat Kemiskinan (P0)',
            'year' => 2026,
            'value' => 8.00,
        ]);
        $this->assertDatabaseHas('indicators', [
            'title' => 'Indeks Pembangunan Manusia (IPM)',
            'year' => 2025,
            'value' => 71.57,
        ]);
        $this->assertDatabaseHas('indicators', [
            'title' => 'Produksi Padi (GKG)',
            'year' => 2025,
            'value' => 679513.67,
        ]);
        $this->assertDatabaseHas('indicators', [
            'title' => 'Tingkat Pengangguran Terbuka (TPT)',
            'year' => 2025,
            'value' => 3.07,
        ]);

        // Fenomena dan isu strategis terakomodasi
        $this->assertGreaterThanOrEqual(15, Phenomenon::count());
    }

    public function test_smart_trend_logic_calculates_correct_colors(): void
    {
        $this->seed(MasterStrategicDataJemberSeeder::class);

        // Pertumbuhan ekonomi naik (is_higher_better = true, trend > 0) -> green
        $pdrb = Indicator::where('title', 'PDRB ADHK (Pertumbuhan Ekonomi)')->where('year', 2025)->first();
        $this->assertEquals('green', $pdrb->trend_color);
        $this->assertEquals('arrow-up', $pdrb->trend_icon);

        // Kemiskinan turun (is_higher_better = false, trend < 0) -> green (bagus)
        $kemiskinan = Indicator::where('title', 'Tingkat Kemiskinan (P0)')->where('year', 2026)->first();
        $this->assertEquals('green', $kemiskinan->trend_color);
        $this->assertEquals('arrow-down', $kemiskinan->trend_icon);

        // Indeks Kedalaman Kemiskinan naik (is_higher_better = false, trend > 0) -> red (buruk)
        $p1 = Indicator::where('title', 'Indeks Kedalaman Kemiskinan (P1)')->where('year', 2026)->first();
        $this->assertEquals('red', $p1->trend_color);
        $this->assertEquals('arrow-up', $p1->trend_icon);
    }

    public function test_historical_time_series_is_accessible(): void
    {
        $this->seed(MasterStrategicDataJemberSeeder::class);

        $ipm2025 = Indicator::where('title', 'Indeks Pembangunan Manusia (IPM)')->where('year', 2025)->first();
        $this->assertNotNull($ipm2025);

        $history = $ipm2025->history;
        $this->assertEquals(5, $history->count()); // 2021, 2022, 2023, 2024, 2025
        $this->assertEquals(2021, $history->first()->year);
        $this->assertEquals(2025, $history->last()->year);
    }

    public function test_dashboard_renders_with_real_statistics(): void
    {
        $this->seed(MasterStrategicDataJemberSeeder::class);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Dashboard Statistik');
        $response->assertSee('Ekonomi &amp; PDRB', false);
    }

    public function test_indicator_list_and_detail_routes_function_properly(): void
    {
        $this->seed(MasterStrategicDataJemberSeeder::class);

        $indicator = Indicator::where('title', 'PDRB ADHK (Pertumbuhan Ekonomi)')->where('year', 2025)->first();
        $this->assertNotNull($indicator);

        $response = $this->get('/indicators');
        $response->assertStatus(200);
        $response->assertSee('Daftar Indikator');

        $detailResponse = $this->get("/indicators/{$indicator->id}");
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('PDRB ADHK (Pertumbuhan Ekonomi)');
        $detailResponse->assertSee('5,47');
        $detailResponse->assertSee('Riwayat Perkembangan Indikator');
        $detailResponse->assertSee('Metadata', false);
    }
}
