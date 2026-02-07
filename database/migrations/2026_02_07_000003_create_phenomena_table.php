<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabel phenomena untuk menyimpan fenomena/insight
     * yang berkaitan dengan indikator tertentu.
     * 
     * Contoh: Indikator "Produksi Padi" bisa memiliki fenomena
     * "Peningkatan produktivitas akibat program intensifikasi"
     */
    public function up(): void
    {
        Schema::create('phenomena', function (Blueprint $table) {
            $table->id();
            
            // Foreign key ke indicators
            $table->foreignId('indicator_id')
                  ->constrained('indicators')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
            
            $table->string('title', 255);         // Judul fenomena
            $table->text('description');          // Deskripsi lengkap fenomena
            $table->enum('impact', ['positive', 'negative']); // Dampak fenomena
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phenomena');
    }
};
