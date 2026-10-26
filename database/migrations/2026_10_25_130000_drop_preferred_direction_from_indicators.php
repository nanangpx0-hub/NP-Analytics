<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('indicators', 'preferred_direction')) {
            return;
        }

        Schema::table('indicators', function (Blueprint $table) {
            $table->dropColumn('preferred_direction');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('indicators', 'preferred_direction')) {
            return;
        }

        Schema::table('indicators', function (Blueprint $table) {
            $table->string('preferred_direction', 30)->nullable();
        });
    }
};
