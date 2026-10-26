<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DeviceCredential;
use App\Models\Indicator;
use App\Models\Phenomenon;
use App\Models\SyncConflict;
use App\Services\SyncException;
use App\Services\SyncService;
use Database\Seeders\MasterStrategicDataJemberSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private array $pullPages = [];

    private array $pushResponses = [];

    private array $pushRequests = [];

    private array $pullRequests = [];

    private function linkDevice(): DeviceCredential
    {
        return DeviceCredential::create([
            'server_url' => 'https://sync.test',
            'device_uuid' => '11111111-1111-4111-8111-111111111111',
            'device_name' => 'Test Device',
            'token' => 'sync-token',
            'abilities' => ['sync:pull', 'sync:push', 'ai:insight'],
            'last_registered_at' => now(),
        ]);
    }

    private function fakeHttp(): void
    {
        Http::fake([
            'sync.test/api/sync/pull*' => function (Request $request) {
                $this->pullRequests[] = $request;

                return Http::response(
                    array_shift($this->pullPages) ?? $this->emptyPull(),
                    200,
                );
            },
            'sync.test/api/sync/push' => function (Request $request) {
                $this->pushRequests[] = (array) $request->data();

                return Http::response(
                    array_shift($this->pushResponses) ?? $this->emptyPush(),
                    200,
                );
            },
        ]);
    }

    private function emptyPull(int $maxVersion = 99): array
    {
        return [
            'server_time' => '2026-10-25T10:00:00+00:00',
            'max_version' => $maxVersion,
            'next_cursor' => null,
            'records' => [],
        ];
    }

    private function emptyPush(): array
    {
        return [
            'server_time' => '2026-10-25T10:00:00+00:00',
            'max_version' => 99,
            'results' => [],
            'replayed' => false,
        ];
    }

    private function categoryRecord(array $overrides = []): array
    {
        return array_merge([
            'uuid' => '10000000-0000-4000-8000-000000000001',
            'name' => 'Ekonomi & PDRB',
            'icon' => 'lucide-trending-up',
            'server_version' => 1,
            'updated_at' => '2026-10-25T09:00:00+00:00',
            'deleted_at' => null,
        ], $overrides);
    }

    private function indicatorRecord(array $overrides = []): array
    {
        return array_merge([
            'uuid' => '20000000-0000-4000-8000-000000000001',
            'category_uuid' => '10000000-0000-4000-8000-000000000001',
            'series_key' => 'pdrb-adhk',
            'title' => 'PDRB ADHK',
            'value' => 5.47,
            'unit' => '%',
            'year' => 2025,
            'trend' => 0.12,
            'is_higher_better' => true,
            'description' => 'Pertumbuhan ekonomi.',
            'image_path' => null,
            'region_level' => 'kabupaten',
            'frequency' => 'annual',
            'period_start' => '2025-01-01',
            'period_end' => '2025-12-31',
            'published_at' => '2026-01-01',
            'source_document' => 'BPS',
            'source_table' => 'Tabel 1',
            'source_page' => null,
            'revision' => null,
            'quality_note' => 'Catatan kualitas.',
            'server_version' => 2,
            'updated_at' => '2026-10-25T09:10:00+00:00',
            'deleted_at' => null,
        ], $overrides);
    }

    private function phenomenonRecord(array $overrides = []): array
    {
        return array_merge([
            'uuid' => '30000000-0000-4000-8000-000000000001',
            'indicator_uuid' => '20000000-0000-4000-8000-000000000001',
            'title' => 'Panen raya',
            'description' => 'Produksi pertanian meningkat.',
            'impact' => 'positive',
            'source' => 'BPS',
            'order' => 1,
            'insight_type' => 'statistik',
            'source_document' => 'BPS',
            'source_page' => null,
            'published_at' => '2026-01-01',
            'author' => 'BPS',
            'quality_note' => null,
            'server_version' => 3,
            'updated_at' => '2026-10-25T09:20:00+00:00',
            'deleted_at' => null,
        ], $overrides);
    }

    private function record(string $type, array $data): array
    {
        return ['type' => $type, 'data' => $data];
    }

    private function seedCleanRecord(string $type, array $attributes): void
    {
        $class = match ($type) {
            'category' => Category::class,
            'indicator' => Indicator::class,
            'phenomenon' => Phenomenon::class,
        };

        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = new $class;
        $model->forceFill($attributes);
        $model->forceFill([
            'dirty' => false,
            'base_version' => 0,
            'server_version' => 0,
        ]);
        $model->timestamps = false;
        $model->saveQuietly();
    }

    private function syncRecordFromModel(string $type, $model, int $version): array
    {
        return $this->record($type, match ($type) {
            'category' => [
                'uuid' => $model->uuid,
                'name' => $model->name,
                'icon' => $model->icon,
                'server_version' => $version,
                'updated_at' => $model->updated_at?->toIso8601String(),
                'deleted_at' => $model->deleted_at?->toIso8601String(),
            ],
            'indicator' => [
                'uuid' => $model->uuid,
                'category_uuid' => $model->category?->uuid,
                'series_key' => $model->series_key,
                'title' => $model->title,
                'value' => $model->value,
                'unit' => $model->unit,
                'year' => $model->year,
                'trend' => $model->trend,
                'is_higher_better' => (bool) $model->is_higher_better,
                'description' => $model->description,
                'image_path' => $model->image_path,
                'region_level' => $model->region_level,
                'frequency' => $model->frequency,
                'period_start' => $model->period_start?->toDateString(),
                'period_end' => $model->period_end?->toDateString(),
                'published_at' => $model->published_at?->toDateString(),
                'source_document' => $model->source_document,
                'source_table' => $model->source_table,
                'source_page' => $model->source_page,
                'revision' => $model->revision,
                'quality_note' => $model->quality_note,
                'server_version' => $version,
                'updated_at' => $model->updated_at?->toIso8601String(),
                'deleted_at' => $model->deleted_at?->toIso8601String(),
            ],
            'phenomenon' => [
                'uuid' => $model->uuid,
                'indicator_uuid' => $model->indicator?->uuid,
                'title' => $model->title,
                'description' => $model->description,
                'impact' => $model->impact,
                'source' => $model->source,
                'order' => (int) $model->order,
                'insight_type' => $model->insight_type,
                'source_document' => $model->source_document,
                'source_page' => $model->source_page,
                'published_at' => $model->published_at?->toDateString(),
                'author' => $model->author,
                'quality_note' => $model->quality_note,
                'server_version' => $version,
                'updated_at' => $model->updated_at?->toIso8601String(),
                'deleted_at' => $model->deleted_at?->toIso8601String(),
            ],
        });
    }

    public function test_bootstrap_from_seeded_device_matches_server_without_duplicates(): void
    {
        $this->linkDevice();
        $this->seed(MasterStrategicDataJemberSeeder::class);

        $categoryCount = Category::count();
        $indicatorCount = Indicator::count();
        $phenomenonCount = Phenomenon::count();

        $this->assertGreaterThan(0, $categoryCount);
        $this->assertGreaterThan(0, $indicatorCount);
        $this->assertSame(0, Indicator::where('dirty', true)->count());

        $records = [];
        $version = 0;

        foreach (Category::withTrashed()->orderBy('id')->get() as $category) {
            $records[] = $this->syncRecordFromModel('category', $category, ++$version);
        }

        foreach (Indicator::withTrashed()->with('category')->orderBy('id')->get() as $indicator) {
            $records[] = $this->syncRecordFromModel('indicator', $indicator, ++$version);
        }

        foreach (Phenomenon::withTrashed()->with('indicator')->orderBy('id')->get() as $phenomenon) {
            $records[] = $this->syncRecordFromModel('phenomenon', $phenomenon, ++$version);
        }

        $this->pullPages = [
            [
                'server_time' => '2026-10-25T10:00:00+00:00',
                'max_version' => $version,
                'next_cursor' => null,
                'records' => $records,
            ],
            $this->emptyPull($version),
        ];

        $this->fakeHttp();

        $result = app(SyncService::class)->sync();

        $this->assertSame([], $result['errors']);
        $this->assertSame([], $result['conflicts']);
        $this->assertSame([], $this->pushRequests);
        $this->assertSame($categoryCount, Category::count());
        $this->assertSame($indicatorCount, Indicator::count());
        $this->assertSame($phenomenonCount, Phenomenon::count());
        $this->assertSame(0, Category::where('dirty', true)->count());
        $this->assertSame(0, Indicator::where('dirty', true)->count());
        $this->assertSame(0, Phenomenon::where('dirty', true)->count());
        $this->assertSame($version, (int) DB::table('sync_states')->where('key', 'last_server_version')->value('value'));

        $this->seed(MasterStrategicDataJemberSeeder::class);

        $this->assertSame($categoryCount, Category::count());
        $this->assertSame($indicatorCount, Indicator::count());
        $this->assertSame($phenomenonCount, Phenomenon::count());
    }

    public function test_dirty_local_record_is_pushed_with_base_version_and_marked_clean(): void
    {
        $this->linkDevice();
        $this->seedCleanRecord('category', [
            'uuid' => '10000000-0000-4000-8000-000000000001',
            'name' => 'Ekonomi & PDRB',
            'icon' => 'lucide-trending-up',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->seedCleanRecord('indicator', [
            'uuid' => '20000000-0000-4000-8000-000000000001',
            'category_id' => Category::first()->id,
            'series_key' => 'pdrb-adhk',
            'title' => 'PDRB ADHK',
            'value' => 5.47,
            'unit' => '%',
            'year' => 2025,
            'trend' => 0.12,
            'is_higher_better' => true,
            'description' => 'Lokal.',
            'region_level' => 'kabupaten',
            'frequency' => 'annual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sync_states')->insert([
            'key' => 'last_server_version',
            'value' => '2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $indicator = Indicator::first();
        $indicator->forceFill(['server_version' => 2, 'base_version' => 2]);
        $indicator->saveQuietly();

        $indicator->title = 'PDRB ADHK Revisi Lokal';
        $indicator->save();

        $this->assertTrue($indicator->fresh()->dirty);
        $this->assertSame(2, (int) $indicator->fresh()->base_version);

        $server = $this->indicatorRecord([
            'title' => 'PDRB ADHK Revisi Lokal',
            'server_version' => 3,
            'updated_at' => '2026-10-25T09:30:00+00:00',
        ]);

        $this->pullPages = [
            $this->emptyPull(3),
            $this->emptyPull(3),
        ];
        $this->pushResponses = [[
            'server_time' => '2026-10-25T10:00:00+00:00',
            'max_version' => 3,
            'results' => [[
                'type' => 'indicator',
                'uuid' => $indicator->uuid,
                'status' => 'applied',
                'server_version' => 3,
                'server' => $server,
            ]],
            'replayed' => false,
        ]];

        $this->fakeHttp();

        $result = app(SyncService::class)->sync();

        $this->assertCount(1, $this->pushRequests);
        $sent = $this->pushRequests[0];
        $this->assertSame('PDRB ADHK Revisi Lokal', $sent['indicators'][0]['title']);
        $this->assertSame(2, $sent['indicators'][0]['base_version']);
        $this->assertArrayHasKey('idempotency_key', $sent);
        $this->assertGreaterThanOrEqual(8, strlen($sent['idempotency_key']));

        $this->assertSame([], $result['errors']);
        $this->assertSame(1, $result['pushed']['indicators']);
        $this->assertFalse($indicator->fresh()->dirty);
        $this->assertSame(3, (int) $indicator->fresh()->server_version);
        $this->assertSame(3, (int) $indicator->fresh()->base_version);
        $this->assertSame(3, (int) DB::table('sync_states')->where('key', 'last_server_version')->value('value'));
    }

    public function test_conflict_is_stored_and_local_version_is_not_overwritten(): void
    {
        $this->linkDevice();
        $this->seedCleanRecord('category', [
            'uuid' => '10000000-0000-4000-8000-000000000001',
            'name' => 'Ekonomi & PDRB',
            'icon' => 'lucide-trending-up',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->seedCleanRecord('indicator', [
            'uuid' => '20000000-0000-4000-8000-000000000001',
            'category_id' => Category::first()->id,
            'series_key' => 'pdrb-adhk',
            'title' => 'Judul Server',
            'value' => 5.47,
            'unit' => '%',
            'year' => 2025,
            'trend' => 0.12,
            'is_higher_better' => true,
            'description' => 'Deskripsi server.',
            'region_level' => 'kabupaten',
            'frequency' => 'annual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $indicator = Indicator::first();
        $indicator->forceFill(['server_version' => 2, 'base_version' => 2]);
        $indicator->saveQuietly();

        $indicator->title = 'Judul Lokal';
        $indicator->save();

        $server = $this->indicatorRecord(['title' => 'Judul Server', 'server_version' => 4]);

        $this->pullPages = [$this->emptyPull(4), $this->emptyPull(4)];
        $this->pushResponses = [[
            'server_time' => '2026-10-25T10:00:00+00:00',
            'max_version' => 4,
            'results' => [[
                'type' => 'indicator',
                'uuid' => $indicator->uuid,
                'status' => 'conflict',
                'server_version' => 4,
                'server' => $server,
            ]],
            'replayed' => false,
        ]];

        $this->fakeHttp();

        $result = app(SyncService::class)->sync();

        $this->assertCount(1, $result['conflicts']);
        $this->assertSame(1, SyncConflict::count());
        $this->assertSame('Judul Lokal', $indicator->fresh()->title);
        $this->assertTrue($indicator->fresh()->dirty);

        $conflict = SyncConflict::first();
        $this->assertSame('indicator', $conflict->record_type);
        $this->assertSame('Judul Lokal', $conflict->local_payload['title']);
        $this->assertSame('Judul Server', $conflict->server_payload['title']);

        app(SyncService::class)->resolveConflictWithServer($conflict);

        $fresh = $indicator->fresh();
        $this->assertSame('Judul Server', $fresh->title);
        $this->assertFalse($fresh->dirty);
        $this->assertSame(4, (int) $fresh->server_version);
        $this->assertSame(0, SyncConflict::count());
    }

    public function test_conflict_resolution_with_local_version_targets_current_server_version(): void
    {
        $this->linkDevice();
        $category = new Category;
        $category->uuid = '10000000-0000-4000-8000-000000000001';
        $category->forceFill(['name' => 'Ekonomi', 'icon' => null, 'dirty' => false]);
        $category->saveQuietly();

        $conflict = SyncConflict::create([
            'record_type' => 'category',
            'record_uuid' => $category->uuid,
            'local_payload' => [
                'uuid' => $category->uuid,
                'base_version' => 1,
                'name' => 'Ekonomi Lokal',
                'icon' => 'chart',
                'deleted_at' => null,
            ],
            'server_payload' => [
                'uuid' => $category->uuid,
                'name' => 'Ekonomi Server',
                'icon' => 'server',
                'server_version' => 6,
                'deleted_at' => null,
            ],
            'server_version' => 6,
        ]);

        app(SyncService::class)->resolveConflictWithLocal($conflict);

        $fresh = $category->fresh();
        $this->assertSame('Ekonomi Lokal', $fresh->name);
        $this->assertTrue($fresh->dirty);
        $this->assertSame(6, (int) $fresh->base_version);
        $this->assertSame(0, SyncConflict::count());
    }

    public function test_pull_applies_explicit_nulls_but_preserves_omitted_fields(): void
    {
        $this->linkDevice();
        $this->seedCleanRecord('category', [
            'uuid' => '10000000-0000-4000-8000-000000000001',
            'name' => 'Ekonomi & PDRB',
            'icon' => 'lucide-trending-up',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->seedCleanRecord('indicator', [
            'uuid' => '20000000-0000-4000-8000-000000000001',
            'category_id' => Category::first()->id,
            'series_key' => 'pdrb-adhk',
            'title' => 'PDRB ADHK',
            'value' => 5.47,
            'unit' => '%',
            'year' => 2025,
            'trend' => 0.12,
            'is_higher_better' => true,
            'description' => 'Deskripsi lama.',
            'quality_note' => 'Pertahankan catatan ini.',
            'region_level' => 'kabupaten',
            'frequency' => 'annual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $category = $this->categoryRecord();
        unset($category['icon']);

        $indicator = $this->indicatorRecord(['description' => null]);
        unset($indicator['quality_note']);

        $this->pullPages = [
            [
                'server_time' => '2026-10-25T10:00:00+00:00',
                'max_version' => 2,
                'next_cursor' => null,
                'records' => [
                    $this->record('category', $category),
                    $this->record('indicator', $indicator),
                ],
            ],
            $this->emptyPull(2),
        ];
        $this->fakeHttp();

        app(SyncService::class)->sync();

        $this->assertNull(Indicator::first()->description);
        $this->assertSame('Pertahankan catatan ini.', Indicator::first()->quality_note);
        $this->assertSame('lucide-trending-up', Category::first()->icon);
    }

    public function test_pull_uses_cursor_pagination_until_last_page(): void
    {
        $this->linkDevice();

        $this->pullPages = [
            [
                'server_time' => '2026-10-25T10:00:00+00:00',
                'max_version' => 2,
                'next_cursor' => 2,
                'records' => [$this->record('category', $this->categoryRecord())],
            ],
            [
                'server_time' => '2026-10-25T10:00:00+00:00',
                'max_version' => 2,
                'next_cursor' => null,
                'records' => [
                    $this->record('indicator', $this->indicatorRecord()),
                ],
            ],
            $this->emptyPull(2),
        ];
        $this->fakeHttp();

        $result = app(SyncService::class)->sync();

        $this->assertSame(1, $result['pulled']['categories']);
        $this->assertSame(1, $result['pulled']['indicators']);
        $this->assertSame(1, Category::count());
        $this->assertSame(1, Indicator::count());
        $this->assertSame(2, (int) $this->pullRequests[1]->data()['cursor']);
        $this->assertNull($this->pullRequests[0]->data()['cursor'] ?? null);
    }

    public function test_pull_skips_dirty_records_during_bootstrap(): void
    {
        $this->linkDevice();
        $this->seedCleanRecord('category', [
            'uuid' => '10000000-0000-4000-8000-000000000001',
            'name' => 'Ekonomi & PDRB',
            'icon' => 'lucide-trending-up',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $category = Category::first();
        $category->name = 'Kategori Lokal';
        $category->save();
        $this->assertTrue($category->fresh()->dirty);

        $this->pullPages = [
            [
                'server_time' => '2026-10-25T10:00:00+00:00',
                'max_version' => 1,
                'next_cursor' => null,
                'records' => [$this->record('category', $this->categoryRecord(['name' => 'Kategori Server']))],
            ],
            $this->emptyPull(1),
        ];
        $this->pushResponses = [[
            'server_time' => '2026-10-25T10:00:00+00:00',
            'max_version' => 2,
            'results' => [[
                'type' => 'category',
                'uuid' => $category->uuid,
                'status' => 'applied',
                'server_version' => 2,
                'server' => $this->categoryRecord([
                    'name' => 'Kategori Lokal',
                    'server_version' => 2,
                ]),
            ]],
            'replayed' => false,
        ]];
        $this->fakeHttp();

        app(SyncService::class)->sync();

        $this->assertSame('Kategori Lokal', $category->fresh()->name);
        $this->assertFalse($category->fresh()->dirty);
        $this->assertSame(1, count($this->pushRequests));
    }

    public function test_sync_without_linked_device_throws_friendly_exception(): void
    {
        Http::fake();

        $this->expectException(SyncException::class);
        $this->expectExceptionMessage('Pengaturan Sync belum lengkap');

        try {
            app(SyncService::class)->sync();
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_revoked_token_maps_to_unauthorized_exception(): void
    {
        $this->linkDevice();

        Http::fake([
            'sync.test/api/sync/pull*' => Http::response(['message' => 'Unauthenticated.'], 401),
        ]);

        try {
            app(SyncService::class)->sync();
            $this->fail('SyncException expected.');
        } catch (SyncException $exception) {
            $this->assertSame('unauthorized', $exception->errorCode);
            $this->assertStringContainsString('Aktifkan ulang', $exception->getMessage());
        }
    }

    public function test_connection_failure_maps_to_offline_exception(): void
    {
        $this->linkDevice();

        Http::fake([
            'sync.test/*' => fn () => throw new ConnectionException('Connection timed out'),
        ]);

        try {
            app(SyncService::class)->sync();
            $this->fail('SyncException expected.');
        } catch (SyncException $exception) {
            $this->assertSame('offline', $exception->errorCode);
            $this->assertStringContainsString('koneksi internet', $exception->getMessage());
        }
    }

    public function test_rate_limited_response_maps_to_friendly_exception(): void
    {
        $this->linkDevice();

        Http::fake([
            'sync.test/api/sync/pull*' => Http::response(['message' => 'Too Many Attempts.'], 429),
        ]);

        try {
            app(SyncService::class)->sync();
            $this->fail('SyncException expected.');
        } catch (SyncException $exception) {
            $this->assertSame('rate_limited', $exception->errorCode);
            $this->assertStringContainsString('Terlalu banyak permintaan', $exception->getMessage());
        }
    }

    public function test_concurrent_sync_is_rejected_by_cache_lock(): void
    {
        $this->linkDevice();
        Http::fake();

        $lock = Cache::lock('np-analytics-sync', 300);
        $this->assertTrue($lock->get());

        try {
            app(SyncService::class)->sync();
            $this->fail('SyncException expected.');
        } catch (SyncException $exception) {
            $this->assertSame('already_running', $exception->errorCode);
            $this->assertStringContainsString('sedang berjalan', $exception->getMessage());
        } finally {
            $lock->release();
        }

        Http::assertNothingSent();
    }
}
