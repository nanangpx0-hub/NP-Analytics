<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Indicator;
use Illuminate\Database\Seeder;

class IndicatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Data indikator disinkronkan dengan blueprint dan data sample Jember.
     */
    public function run(): void
    {
        // Get categories by name
        $ekonomi = Category::where('name', 'Ekonomi')->first();
        $sosial = Category::where('name', 'Sosial')->first();
        $pertanian = Category::where('name', 'Pertanian')->first();
        $kependudukan = Category::where('name', 'Kependudukan')->first();

        $indicators = [
            // ========================================
            // EKONOMI
            // ========================================
            [
                'category_id' => $ekonomi?->id ?? 1,
                'title' => 'PDRB Atas Dasar Harga Berlaku',
                'value' => 125876.45,
                'unit' => 'miliar rupiah',
                'year' => 2024,
                'trend' => 5.23,
                'is_higher_better' => true,
                'description' => 'PDRB Atas Dasar Harga Berlaku tahun 2024 mencapai 125.876,45 miliar rupiah, tumbuh 5,23% dibanding tahun sebelumnya. Sumber: BPS Jember (2024).',
                'image_path' => null,
            ],
            [
                'category_id' => $ekonomi?->id ?? 1,
                'title' => 'PDRB ADHK (Pertumbuhan Ekonomi)',
                'value' => 4.93,
                'unit' => '%',
                'year' => 2023,
                'trend' => 0.40,
                'is_higher_better' => true,
                'description' => 'Laju pertumbuhan ekonomi Kabupaten Jember tahun 2023 diukur dari PDRB Atas Dasar Harga Konstan. Sumber: BPS Jember (2023).',
                'image_path' => null,
            ],
            [
                'category_id' => $ekonomi?->id ?? 1,
                'title' => 'Inflasi Kota Jember (y-on-y)',
                'value' => 2.87,
                'unit' => '%',
                'year' => 2024,
                'trend' => -0.45,
                'is_higher_better' => false,
                'description' => 'Tingkat inflasi tahunan (year-on-year) Kota Jember pada Desember 2024. Penurunan didorong oleh stabilitas harga komoditas pangan utama. Sumber: BPS Jember (Des 2024).',
                'image_path' => null,
            ],
            [
                'category_id' => $ekonomi?->id ?? 1,
                'title' => 'Tingkat Pengangguran Terbuka (TPT)',
                'value' => 4.01,
                'unit' => '%',
                'year' => 2023,
                'trend' => -0.92,
                'is_higher_better' => false,
                'description' => 'Persentase angkatan kerja yang tidak bekerja dan sedang mencari pekerjaan. Penurunan TPT menunjukkan perbaikan kondisi ketenagakerjaan. Sumber: BPS Jember (Sakernas Agustus 2023).',
                'image_path' => null,
            ],

            // ========================================
            // SOSIAL
            // ========================================
            [
                'category_id' => $sosial?->id ?? 2,
                'title' => 'Indeks Pembangunan Manusia (IPM)',
                'value' => 74.24,
                'unit' => 'poin',
                'year' => 2023,
                'trend' => 0.85,
                'is_higher_better' => true,
                'description' => 'Indikator komposit yang mengukur kualitas hidup manusia dari aspek kesehatan, pendidikan, dan standar hidup layak. Sumber: BPS Jember (2023).',
                'image_path' => null,
            ],
            [
                'category_id' => $sosial?->id ?? 2,
                'title' => 'Tingkat Kemiskinan',
                'value' => 9.82,
                'unit' => '%',
                'year' => 2024,
                'trend' => -0.45,
                'is_higher_better' => false,
                'description' => 'Persentase penduduk miskin mengalami penurunan pada 2024 seiring perbaikan daya beli dan program perlindungan sosial. Sumber: BPS Jember (2024).',
                'image_path' => null,
            ],
            [
                'category_id' => $sosial?->id ?? 2,
                'title' => 'Rata-rata Lama Sekolah',
                'value' => 8.92,
                'unit' => 'tahun',
                'year' => 2024,
                'trend' => 0.15,
                'is_higher_better' => true,
                'description' => 'Rata-rata lama sekolah penduduk usia 25+ tahun meningkat seiring akses pendidikan yang lebih baik. Sumber: BPS Jember (2024).',
                'image_path' => null,
            ],
            [
                'category_id' => $sosial?->id ?? 2,
                'title' => 'Angka Harapan Hidup',
                'value' => 71.25,
                'unit' => 'tahun',
                'year' => 2024,
                'trend' => 0.22,
                'is_higher_better' => true,
                'description' => 'Angka harapan hidup meningkat seiring perbaikan layanan kesehatan dan gizi masyarakat. Sumber: BPS Jember (2024).',
                'image_path' => null,
            ],

            // ========================================
            // PERTANIAN
            // ========================================
            [
                'category_id' => $pertanian?->id ?? 3,
                'title' => 'Produksi Padi',
                'value' => 612450.00,
                'unit' => 'ton GKG',
                'year' => 2024,
                'trend' => 3.25,
                'is_higher_better' => true,
                'description' => 'Total produksi padi dalam bentuk Gabah Kering Giling (GKG). Kenaikan terjadi karena produktivitas lahan yang membaik meskipun luas panen sedikit berkurang. Sumber: BPS Jember (Angka Sementara 2024).',
                'image_path' => null,
            ],
            [
                'category_id' => $pertanian?->id ?? 3,
                'title' => 'Produktivitas Padi',
                'value' => 5.82,
                'unit' => 'ton/ha',
                'year' => 2024,
                'trend' => 2.45,
                'is_higher_better' => true,
                'description' => 'Produktivitas padi rata-rata per hektar meningkat karena adopsi varietas unggul dan perbaikan teknik budidaya. Sumber: BPS Jember (2024).',
                'image_path' => null,
            ],
            [
                'category_id' => $pertanian?->id ?? 3,
                'title' => 'Luas Panen Padi',
                'value' => 145320.00,
                'unit' => 'ha',
                'year' => 2024,
                'trend' => 0.72,
                'is_higher_better' => true,
                'description' => 'Luas panen padi meningkat tipis melalui optimalisasi lahan dan perbaikan kalender tanam. Sumber: BPS Jember (2024).',
                'image_path' => null,
            ],

            // ========================================
            // KEPENDUDUKAN
            // ========================================
            [
                'category_id' => $kependudukan?->id ?? 4,
                'title' => 'Jumlah Penduduk',
                'value' => 2456789,
                'unit' => 'jiwa',
                'year' => 2024,
                'trend' => 1.02,
                'is_higher_better' => true,
                'description' => 'Jumlah penduduk mengalami peningkatan alami dan mobilitas masuk. Sumber: BPS Jember (2024).',
                'image_path' => null,
            ],
            [
                'category_id' => $kependudukan?->id ?? 4,
                'title' => 'Kepadatan Penduduk',
                'value' => 1245.32,
                'unit' => 'jiwa/km2',
                'year' => 2024,
                'trend' => 1.05,
                'is_higher_better' => false,
                'description' => 'Kepadatan penduduk meningkat mengikuti konsentrasi populasi di wilayah perkotaan. Sumber: BPS Jember (2024).',
                'image_path' => null,
            ],
            [
                'category_id' => $kependudukan?->id ?? 4,
                'title' => 'Rasio Jenis Kelamin',
                'value' => 98.50,
                'unit' => 'per 100',
                'year' => 2024,
                'trend' => 0.12,
                'is_higher_better' => true,
                'description' => 'Rasio jenis kelamin menunjukkan komposisi penduduk laki-laki terhadap 100 perempuan. Sumber: BPS Jember (2024).',
                'image_path' => null,
            ],
        ];

        foreach ($indicators as $indicator) {
            Indicator::create($indicator);
        }
    }
}
