<?php

namespace Database\Seeders;

use App\Models\Indicator;
use App\Models\Phenomenon;
use Illuminate\Database\Seeder;

class PhenomenonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get indicators by title
        $pdrb = Indicator::where('title', 'PDRB Atas Dasar Harga Berlaku')->first();
        $ipm = Indicator::where('title', 'like', '%Indeks Pembangunan Manusia%')->first();
        $kemiskinan = Indicator::where('title', 'like', '%Kemiskinan%')->first();
        $padi = Indicator::where('title', 'Produksi Padi')->first();
        $inflasi = Indicator::where('title', 'like', '%Inflasi%')->first();

        $phenomena = [
            // PDRB phenomena
            [
                'indicator_id' => $pdrb?->id ?? 1,
                'title' => 'Pertumbuhan Sektor Perdagangan',
                'description' => 'Sektor perdagangan mengalami pertumbuhan signifikan yang didorong oleh meningkatnya aktivitas ekspor-impor dan perdagangan domestik. Hal ini berkontribusi positif terhadap peningkatan PDRB.',
                'impact' => 'positive',
                'source' => 'BPS Jember 2024',
                'order' => 1,
            ],
            [
                'indicator_id' => $pdrb?->id ?? 1,
                'title' => 'Penurunan Sektor Konstruksi',
                'description' => 'Sektor konstruksi mengalami kontraksi akibat menurunnya belanja infrastruktur pemerintah dan perlambatan sektor properti.',
                'impact' => 'negative',
                'source' => 'BPS Jember 2024',
                'order' => 2,
            ],

            // IPM phenomena
            [
                'indicator_id' => $ipm?->id ?? 5,
                'title' => 'Peningkatan Akses Pendidikan',
                'description' => 'Program wajib belajar dan beasiswa daerah meningkatkan angka partisipasi sekolah di semua jenjang, memperbaiki komponen pendidikan pada IPM.',
                'impact' => 'positive',
                'source' => 'BPS Jember 2023',
                'order' => 1,
            ],
            [
                'indicator_id' => $ipm?->id ?? 5,
                'title' => 'Perbaikan Layanan Kesehatan',
                'description' => 'Penambahan fasilitas kesehatan dan tenaga medis di daerah terpencil meningkatkan angka harapan hidup masyarakat.',
                'impact' => 'positive',
                'source' => 'BPS Jember 2023',
                'order' => 2,
            ],

            // Kemiskinan phenomena
            [
                'indicator_id' => $kemiskinan?->id ?? 6,
                'title' => 'Program Bantuan Sosial',
                'description' => 'Penyaluran bantuan sosial yang tepat sasaran membantu menurunkan tingkat kemiskinan di kelompok rentan.',
                'impact' => 'positive',
                'source' => 'BPS Jember 2024',
                'order' => 1,
            ],
            [
                'indicator_id' => $kemiskinan?->id ?? 6,
                'title' => 'Kenaikan Harga Bahan Pokok',
                'description' => 'Kenaikan harga bahan pokok terutama beras dan minyak goreng menekan daya beli masyarakat berpenghasilan rendah.',
                'impact' => 'negative',
                'source' => 'BPS Jember 2024',
                'order' => 2,
            ],

            // Produksi Padi phenomena
            [
                'indicator_id' => $padi?->id ?? 9,
                'title' => 'Program Intensifikasi Pertanian',
                'description' => 'Program intensifikasi dengan penggunaan bibit unggul dan pupuk bersubsidi berhasil meningkatkan produktivitas lahan sawah.',
                'impact' => 'positive',
                'source' => 'BPS Jember 2024',
                'order' => 1,
            ],
            [
                'indicator_id' => $padi?->id ?? 9,
                'title' => 'Alih Fungsi Lahan Pertanian',
                'description' => 'Alih fungsi lahan sawah menjadi permukiman dan kawasan industri mengurangi luas area tanam padi.',
                'impact' => 'negative',
                'source' => 'BPS Jember 2024',
                'order' => 2,
            ],

            // Inflasi phenomena
            [
                'indicator_id' => $inflasi?->id ?? 3,
                'title' => 'Stabilitas Pasokan Pangan',
                'description' => 'Ketersediaan pasokan pangan yang relatif stabil membuat tekanan inflasi tahunan cenderung terkendali.',
                'impact' => 'neutral',
                'source' => 'BPS Jember 2024',
                'order' => 1,
            ],
            [
                'indicator_id' => $inflasi?->id ?? 3,
                'title' => 'Kenaikan Tarif Listrik',
                'description' => 'Penyesuaian tarif dasar listrik berkontribusi terhadap tekanan inflasi pada kelompok pengeluaran perumahan.',
                'impact' => 'negative',
                'source' => 'BPS Jember 2024',
                'order' => 2,
            ],
        ];

        foreach ($phenomena as $phenomenon) {
            Phenomenon::create($phenomenon);
        }
    }
}
