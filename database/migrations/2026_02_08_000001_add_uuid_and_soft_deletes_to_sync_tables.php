<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->softDeletes();
            $table->unique('uuid');
        });

        Schema::table('indicators', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->softDeletes();
            $table->unique('uuid');
        });

        Schema::table('phenomena', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->softDeletes();
            $table->unique('uuid');
        });

        $this->backfillUuid('categories');
        $this->backfillUuid('indicators');
        $this->backfillUuid('phenomena');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phenomena', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropSoftDeletes();
            $table->dropColumn('uuid');
        });

        Schema::table('indicators', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropSoftDeletes();
            $table->dropColumn('uuid');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropSoftDeletes();
            $table->dropColumn('uuid');
        });
    }

    private function backfillUuid(string $table): void
    {
        $rows = DB::table($table)->whereNull('uuid')->pluck('id');

        foreach ($rows as $id) {
            DB::table($table)
                ->where('id', $id)
                ->update(['uuid' => (string) Str::uuid()]);
        }
    }
};
