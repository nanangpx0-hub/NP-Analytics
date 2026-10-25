<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_insights', function (Blueprint $table) {
            $table->id();
            $table->string('hash', 64)->unique();
            $table->string('scope', 20)->index();
            $table->foreignId('indicator_id')->nullable()->constrained('indicators')->nullOnDelete();
            $table->integer('year')->nullable()->index();
            $table->string('model', 80)->nullable();
            $table->string('source', 20)->default('gemini');
            $table->json('payload');
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['scope', 'indicator_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_insights');
    }
};
