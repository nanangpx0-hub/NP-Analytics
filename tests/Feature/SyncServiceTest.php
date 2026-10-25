<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Indicator;
use App\Models\Phenomenon;
use App\Services\SyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private function pullResponse(string $indicatorTitle = 'PDRB ADHK'): array
    {
        return [
            'server_time' => '2026-10-25T10:00:00+00:00',
            'categories' => [[
                'uuid' => 'cat-0001',
                'name' => 'Ekonomi & PDRB',
                'icon' => 'chart',
                'updated_at' => '2026-10-25T09:00:00+00:00',
                'deleted_at' => null,
            ]],
            'indicators' => [[
                'uuid' => 'ind-0001',
                'category_uuid' => 'cat-0001',
                'title' => $indicatorTitle,
                'value' => 5.47,
                'unit' => '%',
                'year' => 2025,
                'trend' => 0.12,
                'is_higher_better' => true,
                'description' => 'Pertumbuhan ekonomi.',
                'updated_at' => '2026-10-25T09:10:00+00:00',
                'deleted_at' => null,
            ]],
            'phenomena' => [[
                'uuid' => 'phe-0001',
                'indicator_uuid' => 'ind-0001',
                'title' => 'Panen raya',
                'description' => 'Produksi pertanian meningkat.',
                'impact' => 'positive',
                'order' => 1,
                'updated_at' => '2026-10-25T09:20:00+00:00',
                'deleted_at' => null,
            ]],
        ];
    }

    public function test_pull_applies_categories_indicators_and_phenomena_in_one_transaction(): void
    {
        config([
            'sync.base_url' => 'https://sync.test',
            'sync.api_key' => 'secret',
        ]);

        Http::fake([
            'sync.test/api/sync/pull*' => Http::response($this->pullResponse(), 200),
        ]);

        $result = app(SyncService::class)->sync();

        $this->assertSame([], $result['errors']);
        $this->assertSame(
            ['categories' => 1, 'indicators' => 1, 'phenomena' => 1],
            $result['pulled'],
        );

        $this->assertDatabaseHas('categories', ['uuid' => 'cat-0001', 'name' => 'Ekonomi & PDRB']);
        $this->assertDatabaseHas('indicators', ['uuid' => 'ind-0001', 'title' => 'PDRB ADHK']);
        $this->assertDatabaseHas('phenomena', ['uuid' => 'phe-0001', 'title' => 'Panen raya']);
        $this->assertSame(
            '2026-10-25 09:10:00',
            Indicator::where('uuid', 'ind-0001')->first()->updated_at->toDateTimeString(),
        );
        $this->assertDatabaseHas('sync_states', ['key' => 'last_server_time']);
    }

    public function test_second_pull_updates_existing_records_without_duplicates(): void
    {
        config([
            'sync.base_url' => 'https://sync.test',
            'sync.api_key' => 'secret',
        ]);

        $responses = [
            $this->pullResponse('PDRB ADHK'),
            $this->pullResponse('PDRB ADHK (Revisi)'),
        ];

        Http::fake([
            'sync.test/api/sync/pull*' => function () use (&$responses) {
                return Http::response(array_shift($responses), 200);
            },
        ]);

        $service = app(SyncService::class);
        $service->sync();
        $result = $service->sync();

        $this->assertSame([], $result['errors']);
        $this->assertSame(1, Category::count());
        $this->assertSame(1, Indicator::count());
        $this->assertSame(1, Phenomenon::count());
        $this->assertSame('PDRB ADHK (Revisi)', Indicator::first()->title);
        $this->assertSame(
            DB::table('categories')->where('uuid', 'cat-0001')->value('id'),
            Indicator::first()->category_id,
        );
    }
}
