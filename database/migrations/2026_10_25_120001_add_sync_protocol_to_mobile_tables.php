<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedBigInteger('server_version')->default(0)->index();
            $table->boolean('dirty')->default(true)->index();
            $table->unsignedBigInteger('base_version')->default(0);
        });

        Schema::table('indicators', function (Blueprint $table) {
            $table->string('series_key', 150)->nullable()->after('category_id');
            $table->unsignedBigInteger('server_version')->default(0)->index();
            $table->boolean('dirty')->default(true)->index();
            $table->unsignedBigInteger('base_version')->default(0);
            $table->index('series_key');
        });

        Schema::table('phenomena', function (Blueprint $table) {
            $table->unsignedBigInteger('server_version')->default(0)->index();
            $table->boolean('dirty')->default(true)->index();
            $table->unsignedBigInteger('base_version')->default(0);
        });

        $this->backfillSeriesKeys();
        $this->backfillDirtyFlags();

        Schema::table('indicators', function (Blueprint $table) {
            $table->unique(['series_key', 'year'], 'indicators_series_year_unique');
        });

        Schema::create('sync_conflicts', function (Blueprint $table) {
            $table->id();
            $table->string('record_type', 20);
            $table->uuid('record_uuid');
            $table->json('local_payload')->nullable();
            $table->json('server_payload')->nullable();
            $table->unsignedBigInteger('server_version')->default(0);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['record_type', 'record_uuid']);
            $table->index('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_conflicts');

        Schema::table('indicators', function (Blueprint $table) {
            $table->dropUnique('indicators_series_year_unique');
            $table->dropIndex(['series_key']);
            $table->dropColumn(['series_key', 'server_version', 'dirty', 'base_version']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['server_version', 'dirty', 'base_version']);
        });

        Schema::table('phenomena', function (Blueprint $table) {
            $table->dropColumn(['server_version', 'dirty', 'base_version']);
        });
    }

    private function backfillSeriesKeys(): void
    {
        $seen = [];

        DB::table('indicators')
            ->orderBy('id')
            ->select(['id', 'title', 'year'])
            ->get()
            ->each(function (object $row) use (&$seen): void {
                $base = Str::limit(Str::slug((string) $row->title), 150, '') ?: 'indicator-'.$row->id;
                $key = $base;
                $suffix = 1;

                while (isset($seen[$row->year][$key])) {
                    $suffix++;
                    $key = $base.'-'.$suffix;
                }

                $seen[$row->year][$key] = true;

                DB::table('indicators')
                    ->where('id', $row->id)
                    ->update(['series_key' => $key]);
            });
    }

    /**
     * Data hasil seeder dianggap bersih karena identik dengan master server.
     * Perubahan lokal yang belum terkirim sejak sync lama tetap ditandai dirty.
     */
    private function backfillDirtyFlags(): void
    {
        $lastSync = DB::table('sync_states')->where('key', 'last_server_time')->value('value');

        foreach (['categories', 'indicators', 'phenomena'] as $table) {
            DB::table($table)->update(['dirty' => false]);

            if (! $lastSync) {
                continue;
            }

            DB::table($table)
                ->where(function ($query) use ($lastSync): void {
                    $query->where('updated_at', '>', $lastSync)
                        ->orWhere('deleted_at', '>', $lastSync);
                })
                ->update(['dirty' => true]);
        }
    }
};
