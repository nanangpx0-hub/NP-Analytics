<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabel categories untuk menyimpan kategori indikator
     * seperti: Ekonomi, Sosial, Pertanian, dll.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);           // Nama kategori (contoh: "Ekonomi", "Sosial")
            $table->string('icon', 100)->nullable(); // Icon class (contoh: "lucide-bar-chart", "heroicon-chart-bar")
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
