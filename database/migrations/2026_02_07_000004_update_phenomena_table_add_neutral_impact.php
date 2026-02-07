<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Perbaikan struktur tabel phenomena sesuai Blueprint:
     * 1. Menambahkan 'neutral' ke enum impact
     * 2. Menambahkan field source untuk referensi
     * 3. Menambahkan field order untuk urutan tampilan
     * 
     * Note: SQLite tidak mendukung ALTER COLUMN untuk mengubah enum.
     * Solusi: Buat tabel baru, pindahkan data, drop lama, rename baru.
     */
    public function up(): void
    {
        // Untuk SQLite, kita perlu recreate tabel
        // Karena SQLite tidak support ALTER COLUMN

        // 1. Rename tabel lama (jika ada)
        if (Schema::hasTable('phenomena')) {
            Schema::rename('phenomena', 'phenomena_backup');
        }

        // 2. Buat tabel baru dengan struktur yang benar
        Schema::create('phenomena', function (Blueprint $table) {
            $table->id();
            
            // Foreign key ke indicators
            $table->foreignId('indicator_id')
                  ->constrained('indicators')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
            
            $table->string('title', 255);         // Judul fenomena (ex: "Gagal Panen")
            $table->text('description');          // Penjelasan detail konteks kejadian
            
            // Impact dengan 3 nilai sesuai Blueprint
            $table->enum('impact', ['positive', 'negative', 'neutral'])->default('neutral');
            
            // Field tambahan untuk fitur lebih lengkap
            $table->string('source', 255)->nullable();  // Sumber referensi (ex: "Analisis BPS 2024")
            $table->integer('order')->default(0);       // Urutan tampilan
            
            $table->timestamps();

            // Index untuk query
            $table->index('indicator_id');
            $table->index('impact');
        });

        // 3. Pindahkan data dari backup ke tabel baru (jika ada)
        if (Schema::hasTable('phenomena_backup')) {
            // Copy data lama ke tabel baru
            $oldData = \DB::table('phenomena_backup')->get();
            
            foreach ($oldData as $row) {
                \DB::table('phenomena')->insert([
                    'id' => $row->id,
                    'indicator_id' => $row->indicator_id,
                    'title' => $row->title,
                    'description' => $row->description,
                    'impact' => $row->impact, // positive/negative tetap valid
                    'source' => null,
                    'order' => 0,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
            
            // Drop tabel backup
            Schema::dropIfExists('phenomena_backup');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembali ke struktur lama
        if (Schema::hasTable('phenomena')) {
            Schema::rename('phenomena', 'phenomena_new');
        }

        Schema::create('phenomena', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')
                  ->constrained('indicators')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
            $table->string('title', 255);
            $table->text('description');
            $table->enum('impact', ['positive', 'negative']);
            $table->timestamps();
            $table->index('indicator_id');
        });

        // Pindahkan data kembali
        if (Schema::hasTable('phenomena_new')) {
            $newData = \DB::table('phenomena_new')->get();
            
            foreach ($newData as $row) {
                // Skip neutral karena tidak ada di enum lama
                if ($row->impact === 'neutral') {
                    continue;
                }
                
                \DB::table('phenomena')->insert([
                    'id' => $row->id,
                    'indicator_id' => $row->indicator_id,
                    'title' => $row->title,
                    'description' => $row->description,
                    'impact' => $row->impact,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
            
            Schema::dropIfExists('phenomena_new');
        }
    }
};
