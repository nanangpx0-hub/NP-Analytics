<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\IndicatorDetail;
use App\Models\AiInsight;
use App\Models\Category;
use App\Models\DeviceCredential;
use App\Models\Indicator;
use App\Services\GeminiInsightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class GeminiInsightTest extends TestCase
{
    use RefreshDatabase;

    private function linkDevice(): void
    {
        DeviceCredential::create([
            'server_url' => 'https://sync.test',
            'device_uuid' => '11111111-1111-4111-8111-111111111111',
            'device_name' => 'Test Device',
            'token' => 'ai-token',
            'abilities' => ['sync:pull', 'sync:push', 'ai:insight'],
            'last_registered_at' => now(),
        ]);
    }

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

    private function aiResponse(): array
    {
        return [
            'summary' => 'Pertumbuhan ekonomi Jember 2025 tercatat 5,47 persen.',
            'root_cause' => 'Sektor pertanian dan perdagangan menjadi pendorong utama.',
            'recommendations' => [
                'Perkuat hilirisasi komoditas unggulan daerah.',
                'Jaga stabilitas harga pangan strategis.',
                'Perluas akses pasar bagi UMKM.',
            ],
            'model' => 'gemini-2.5-flash',
            'generated_at' => '2026-10-25T10:00:00+00:00',
            'source' => 'gemini',
        ];
    }

    public function test_ai_result_is_fetched_from_backend_proxy_and_cached_locally(): void
    {
        $this->linkDevice();
        Http::fake([
            'sync.test/api/ai/insight' => Http::response($this->aiResponse(), 200),
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

        Http::assertSent(fn ($request): bool => $request->url() === 'https://sync.test/api/ai/insight'
            && $request->hasHeader('Authorization', 'Bearer ai-token')
            && $request['scope'] === 'indicator');
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'generativelanguage.googleapis.com'));

        // Analisis kedua dengan konteks sama dibaca dari cache lokal, tanpa HTTP baru.
        $cached = $service->analyzeIndicator($indicator, $history, false);

        $this->assertSame('cache', $cached['source']);
        $this->assertSame($insight['summary'], $cached['summary']);
        Http::assertSentCount(1);
    }

    public function test_unlinked_device_returns_local_insight_without_http_call(): void
    {
        Http::fake();

        $indicator = $this->createIndicator();
        $history = Indicator::where('title', $indicator->title)->get();

        $insight = app(GeminiInsightService::class)->analyzeIndicator($indicator, $history);

        $this->assertSame('local', $insight['source']);
        $this->assertNotEmpty($insight['summary']);
        $this->assertNotEmpty($insight['recommendations']);
        $this->assertStringContainsString('Perangkat belum diaktifkan', (string) $insight['notice']);
        Http::assertNothingSent();
    }

    public function test_backend_failure_falls_back_to_local_then_serves_cache(): void
    {
        $this->linkDevice();
        $indicator = $this->createIndicator();
        $history = Indicator::where('title', $indicator->title)->get();
        $service = app(GeminiInsightService::class);

        $responses = [
            Http::response(['code' => 'ai_unavailable'], 503),
            Http::response($this->aiResponse(), 200),
            Http::response(['code' => 'rate_limited'], 429),
        ];
        Http::fake([
            'sync.test/api/ai/insight' => function () use (&$responses) {
                return array_shift($responses);
            },
        ]);

        $offline = $service->analyzeIndicator($indicator, $history);

        $this->assertSame('local', $offline['source']);
        $this->assertStringContainsString('tidak tersedia', (string) $offline['notice']);
        $this->assertSame(0, AiInsight::count());

        $service->analyzeIndicator($indicator, $history);
        $this->assertSame(1, AiInsight::count());

        $cached = $service->analyzeIndicator($indicator, $history);

        $this->assertSame('cache', $cached['source']);
        $this->assertStringContainsString('Batas permintaan', (string) $cached['notice']);
    }

    public function test_revoked_device_falls_back_with_friendly_notice(): void
    {
        $this->linkDevice();
        Http::fake([
            'sync.test/api/ai/insight' => Http::response(['message' => 'Unauthenticated.'], 401),
        ]);

        $indicator = $this->createIndicator();
        $history = Indicator::where('title', $indicator->title)->get();

        $insight = app(GeminiInsightService::class)->analyzeIndicator($indicator, $history);

        $this->assertSame('local', $insight['source']);
        $this->assertStringContainsString('Sesi perangkat tidak valid', (string) $insight['notice']);
    }

    public function test_dashboard_ai_action_and_cached_render(): void
    {
        $this->linkDevice();
        Http::fake([
            'sync.test/api/ai/insight' => Http::response($this->aiResponse(), 200),
        ]);

        $this->createIndicator();

        $component = Livewire::test(Dashboard::class)
            ->assertSee('Ringkasan &amp; Rekomendasi AI', false)
            ->call('generateAiInsight');

        $insight = $component->get('aiInsight');
        $this->assertSame('gemini', $insight['source']);
        $this->assertNotEmpty($insight['recommendations']);
        $this->assertSame(1, AiInsight::where('scope', 'dashboard')->count());

        Livewire::test(Dashboard::class)
            ->assertSee('Pertumbuhan ekonomi Jember 2025 tercatat 5,47 persen.');

        Http::assertSentCount(1);
    }

    public function test_indicator_detail_ai_action_and_panel_render(): void
    {
        $this->linkDevice();
        Http::fake([
            'sync.test/api/ai/insight' => Http::response($this->aiResponse(), 200),
        ]);

        $indicator = $this->createIndicator();

        Livewire::test(IndicatorDetail::class, ['id' => $indicator->id])
            ->assertSee('Ringkasan &amp; Rekomendasi AI', false)
            ->call('generateAiInsight')
            ->assertSee('Pertumbuhan ekonomi Jember 2025 tercatat 5,47 persen.')
            ->assertSee('Perkuat hilirisasi komoditas unggulan daerah.');

        $this->assertSame(1, AiInsight::where('scope', 'indicator')->count());
    }

    public function test_ai_panel_handles_unlinked_device_on_detail_page(): void
    {
        $indicator = $this->createIndicator();

        $this->get("/indicators/{$indicator->id}")
            ->assertStatus(200)
            ->assertSee('Ringkasan &amp; Rekomendasi AI', false)
            ->assertSee('Perangkat belum diaktifkan');
    }
}
