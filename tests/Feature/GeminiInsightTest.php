<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\IndicatorDetail;
use App\Models\AiInsight;
use App\Models\Category;
use App\Models\Indicator;
use App\Services\GeminiInsightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class GeminiInsightTest extends TestCase
{
    use RefreshDatabase;

    private function createIndicator(): Indicator
    {
        $category = Category::create([
            'name' => 'Ekonomi & PDRB',
            'icon' => null,
        ]);

        $indicator = $category->indicators()->create([
            'title' => 'PDRB ADHK (Pertumbuhan Ekonomi)',
            'value' => 5.47,
            'unit' => '%',
            'year' => 2025,
            'trend' => 0.12,
            'is_higher_better' => true,
            'description' => 'Laju pertumbuhan ekonomi Kabupaten Jember.',
        ]);

        $indicator->phenomena()->create([
            'title' => 'Panen raya tembakau',
            'description' => 'Peningkatan produksi tembakau mendorong sektor pertanian.',
            'impact' => 'positive',
            'source' => 'BPS Jember',
            'order' => 1,
        ]);

        return $indicator;
    }

    private function geminiResponse(): array
    {
        return [
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode([
                            'ringkasan_eksekutif' => 'Pertumbuhan ekonomi Jember 2025 tercatat 5,47 persen.',
                            'analisis_akar_masalah' => 'Sektor pertanian dan perdagangan menjadi pendorong utama.',
                            'rekomendasi_kebijakan' => [
                                'Perkuat hilirisasi komoditas unggulan daerah.',
                                'Jaga stabilitas harga pangan strategis.',
                                'Perluas akses pasar bagi UMKM.',
                            ],
                        ], JSON_UNESCAPED_UNICODE),
                    ]],
                ],
            ]],
        ];
    }

    public function test_gemini_result_is_parsed_and_cached_in_sqlite(): void
    {
        config(['gemini.api_key' => 'test-key', 'gemini.model' => 'gemini-2.5-flash']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->geminiResponse(), 200),
        ]);

        $indicator = $this->createIndicator();
        $history = Indicator::where('title', $indicator->title)->get();
        $service = app(GeminiInsightService::class);

        $insight = $service->analyzeIndicator($indicator, $history);

        $this->assertSame('gemini', $insight['source']);
        $this->assertSame('Pertumbuhan ekonomi Jember 2025 tercatat 5,47 persen.', $insight['summary']);
        $this->assertCount(3, $insight['recommendations']);
        $this->assertSame('gemini-2.5-flash', $insight['model']);
        $this->assertSame(1, AiInsight::where('scope', 'indicator')->count());

        Http::assertSentCount(1);

        // Analisis kedua dengan konteks sama dibaca dari cache lokal, tanpa HTTP baru.
        $cached = $service->analyzeIndicator($indicator, $history, false);

        $this->assertSame('cache', $cached['source']);
        $this->assertSame($insight['summary'], $cached['summary']);
        Http::assertSentCount(1);
    }

    public function test_missing_api_key_returns_local_insight_without_http_call(): void
    {
        config(['gemini.api_key' => null]);
        Http::fake();

        $indicator = $this->createIndicator();
        $history = Indicator::where('title', $indicator->title)->get();

        $insight = app(GeminiInsightService::class)->analyzeIndicator($indicator, $history);

        $this->assertSame('local', $insight['source']);
        $this->assertNotEmpty($insight['summary']);
        $this->assertNotEmpty($insight['recommendations']);
        $this->assertStringContainsString('GEMINI_API_KEY', (string) $insight['notice']);
        Http::assertNothingSent();
    }

    public function test_gemini_http_failure_falls_back_to_local_then_serves_cache(): void
    {
        config(['gemini.api_key' => 'test-key']);
        $indicator = $this->createIndicator();
        $history = Indicator::where('title', $indicator->title)->get();
        $service = app(GeminiInsightService::class);

        $responses = [
            Http::response(['error' => 'unavailable'], 503),
            Http::response($this->geminiResponse(), 200),
            Http::response(['error' => 'offline'], 500),
        ];
        Http::fake([
            'generativelanguage.googleapis.com/*' => function () use (&$responses) {
                return array_shift($responses);
            },
        ]);

        $offline = $service->analyzeIndicator($indicator, $history);

        $this->assertSame('local', $offline['source']);
        $this->assertStringContainsString('tidak dapat dihubungi', (string) $offline['notice']);
        $this->assertSame(0, AiInsight::count());

        // Setelah ada hasil Gemini tersimpan, kegagalan berikutnya memakai cache.
        $service->analyzeIndicator($indicator, $history);
        $this->assertSame(1, AiInsight::count());

        $cached = $service->analyzeIndicator($indicator, $history);

        $this->assertSame('cache', $cached['source']);
        $this->assertStringContainsString('tersimpan', (string) $cached['notice']);
    }

    public function test_dashboard_ai_action_and_cached_render(): void
    {
        config(['gemini.api_key' => 'test-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->geminiResponse(), 200),
        ]);

        $this->createIndicator();

        $component = Livewire::test(Dashboard::class)
            ->assertSee('Ringkasan &amp; Rekomendasi AI', false)
            ->call('generateAiInsight');

        $insight = $component->get('aiInsight');
        $this->assertSame('gemini', $insight['source']);
        $this->assertNotEmpty($insight['recommendations']);
        $this->assertSame(1, AiInsight::where('scope', 'dashboard')->count());

        // Render ulang memakai cache dashboard tanpa memanggil Gemini lagi.
        Livewire::test(Dashboard::class)
            ->assertSee('Pertumbuhan ekonomi Jember 2025 tercatat 5,47 persen.');

        Http::assertSentCount(1);
    }

    public function test_indicator_detail_ai_action_and_panel_render(): void
    {
        config(['gemini.api_key' => 'test-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->geminiResponse(), 200),
        ]);

        $indicator = $this->createIndicator();

        Livewire::test(IndicatorDetail::class, ['id' => $indicator->id])
            ->assertSee('Ringkasan &amp; Rekomendasi AI', false)
            ->call('generateAiInsight')
            ->assertSee('Pertumbuhan ekonomi Jember 2025 tercatat 5,47 persen.')
            ->assertSee('Perkuat hilirisasi komoditas unggulan daerah.');

        $this->assertSame(1, AiInsight::where('scope', 'indicator')->count());
    }

    public function test_ai_panel_handles_missing_key_on_detail_page(): void
    {
        config(['gemini.api_key' => null]);

        $indicator = $this->createIndicator();

        $this->get("/indicators/{$indicator->id}")
            ->assertStatus(200)
            ->assertSee('Ringkasan &amp; Rekomendasi AI', false)
            ->assertSee('GEMINI_API_KEY belum diatur');
    }
}
