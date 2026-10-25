<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indicators', function (Blueprint $table) {
            $table->index('title');
            $table->index(['category_id', 'year', 'title']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index('name');
        });

        Schema::table('phenomena', function (Blueprint $table) {
            $table->index('title');
            $table->index(['indicator_id', 'impact', 'order']);
        });
    }

    public function down(): void
    {
        Schema::table('phenomena', function (Blueprint $table) {
            $table->dropIndex(['indicator_id', 'impact', 'order']);
            $table->dropIndex(['title']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });

        Schema::table('indicators', function (Blueprint $table) {
            $table->dropIndex(['category_id', 'year', 'title']);
            $table->dropIndex(['title']);
        });
    }
};
