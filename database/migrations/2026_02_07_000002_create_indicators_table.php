<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabel indicators untuk menyimpan data indikator statistik
     * dengan relasi ke tabel categories.
     * 
     * Field is_higher_better digunakan untuk Smart Trend Logic:
     * - true: Indikator positif (Padi, PDRB) → Naik = Hijau, Turun = Merah
     * - false: Indikator negatif (Kemiskinan, Inflasi) → Naik = Merah, Turun = Hijau
     */
    public function up(): void
    {
        Schema::create('indicators', function (Blueprint $table) {
            $table->id();
            
            // Foreign key ke categories
            $table->foreignId('category_id')
                  ->constrained('categories')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
            
            $table->string('title', 255);              // Judul indikator (contoh: "Produksi Padi")
            $table->decimal('value', 20, 4);           // Nilai indikator (mendukung angka besar & desimal)
            $table->string('unit', 50);                // Satuan (contoh: "ton", "%", "rupiah")
            $table->integer('year');                   // Tahun data (contoh: 2024)
            $table->decimal('trend', 10, 4)->nullable();  // Persentase perubahan dari tahun sebelumnya
            $table->boolean('is_higher_better')->default(true); // Smart Trend Logic
            $table->text('description')->nullable();   // Deskripsi/penjelasan indikator
            $table->string('image_path', 255)->nullable(); // Path ke gambar/chart jika ada
            $table->timestamps();

            // Index untuk query yang sering digunakan
            $table->index(['category_id', 'year']);
            $table->index('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indicators');
    }
};
