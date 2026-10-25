<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indicators', function (Blueprint $table) {
            $table->string('region_level', 50)->nullable()->after('unit');
            $table->string('frequency', 30)->nullable()->after('region_level');
            $table->date('period_start')->nullable()->after('frequency');
            $table->date('period_end')->nullable()->after('period_start');
            $table->date('published_at')->nullable()->after('period_end');
            $table->string('source_document', 255)->nullable()->after('published_at');
            $table->string('source_table', 100)->nullable()->after('source_document');
            $table->string('source_page', 50)->nullable()->after('source_table');
            $table->string('revision', 50)->nullable()->after('source_page');
            $table->text('quality_note')->nullable()->after('revision');
            $table->string('preferred_direction', 30)->nullable()->after('quality_note');

            $table->index(['frequency', 'period_start']);
            $table->index('region_level');
        });

        Schema::table('phenomena', function (Blueprint $table) {
            $table->string('insight_type', 30)->nullable()->after('impact');
            $table->string('source_document', 255)->nullable()->after('source');
            $table->string('source_page', 50)->nullable()->after('source_document');
            $table->date('published_at')->nullable()->after('source_page');
            $table->string('author', 150)->nullable()->after('published_at');
            $table->text('quality_note')->nullable()->after('author');

            $table->index('insight_type');
        });
    }

    public function down(): void
    {
        Schema::table('phenomena', function (Blueprint $table) {
            $table->dropIndex(['insight_type']);
            $table->dropColumn(['insight_type', 'source_document', 'source_page', 'published_at', 'author', 'quality_note']);
        });

        Schema::table('indicators', function (Blueprint $table) {
            $table->dropIndex(['frequency', 'period_start']);
            $table->dropIndex(['region_level']);
            $table->dropColumn([
                'region_level',
                'frequency',
                'period_start',
                'period_end',
                'published_at',
                'source_document',
                'source_table',
                'source_page',
                'revision',
                'quality_note',
                'preferred_direction',
            ]);
        });
    }
};
