<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Indicator;
use App\Models\Phenomenon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class StrategicData2026Seeder extends Seeder
{
    private const LEVEL = 'kabupaten';
    private const AGENCY = 'BPS Kabupaten Jember';

    public function run(): void
    {
        $this->updateIndicators();
        $this->createIndicators();
        $this->createPhenomena();
    }

    private function updateIndicators(): void
    {
        $data = [
            [
                'title' => 'PDRB ADHK (Pertumbuhan Ekonomi)',
                'value' => 5.4700,
                'unit' => '%',
                'year' => 2025,
                'trend' => null,
                'is_higher_better' => true,
                'preferred_direction' => 'higher',
                'frequency' => 'annual',
                'period' => [2025],
                'source_document' => 'Pertumbuhan Ekonomi Kabupaten Jember 2025 (PDF BPS)',
                'source_table' => 'Ringkasan Eksekutif',
                'description' => 'Perekonomian Kabupaten Jember tahun 2025 tumbuh 5,47 persen atas dasar harga konstan dibanding 2024. Sektor dominan: Pertanian (25,42%), Industri Pengolahan (21,61%), Perdagangan (14,77%).',
            ],
            [
                'title' => 'Tingkat Pengangguran Terbuka (TPT)',
                'value' => 3.0700,
                'unit' => '%',
                'year' => 2025,
                'trend' => null,
                'is_higher_better' => false,
                'preferred_direction' => 'lower',
                'frequency' => 'annual',
                'period' => [2025, 8],
                'source_document' => 'Keadaan Ketenagakerjaan Kabupaten Jember Agustus 2025 (PDF BPS)',
                'source_table' => 'Ringkasan (Sakernas)',
                'description' => 'TPT Agustus 2025 sebesar 3,07 persen, turun 0,16 poin persen dibanding 2024. Angkatan kerja 1,58 juta orang; bekerja 1,53 juta orang.',
            ],
            [
                'title' => 'PDRB Atas Dasar Harga Berlaku',
                'value' => 110980.0000,
                'unit' => 'miliar rupiah',
                'year' => 2025,
                'trend' => null,
                'is_higher_better' => true,
                'preferred_direction' => 'higher',
                'frequency' => 'annual',
                'period' => [2025],
                'source_document' => 'Pertumbuhan Ekonomi Kabupaten Jember 2025 (PDF BPS)',
                'source_table' => 'Ringkasan (ADHB)',
                'description' => 'PDRB atas dasar harga berlaku tahun 2025 mencapai Rp110,98 triliun.',
            ],
            [
                'title' => 'Inflasi Kota Jember (y-on-y)',
                'value' => 3.0400,
                'unit' => '%',
                'year' => 2026,
                'trend' => null,
                'is_higher_better' => false,
                'preferred_direction' => 'lower',
                'frequency' => 'monthly',
                'period' => [2026, 9],
                'source_document' => 'Perkembangan Indeks Harga Konsumen Jember September 2026 (PDF BPS)',
                'source_table' => 'Ringkasan (IHK)',
                'description' => 'Inflasi y-on-y September 2026 sebesar 3,04 persen (Konsumsi 3,45%; inti 1,84%). Andil terbesar: cabai rawit 0,28 pp dan beras 0,22 pp.',
            ],
            [
                'title' => 'Indeks Pembangunan Manusia (IPM)',
                'value' => 71.5700,
                'unit' => 'poin',
                'year' => 2025,
                'trend' => null,
                'is_higher_better' => true,
                'preferred_direction' => 'higher',
                'frequency' => 'annual',
                'period' => [2025],
                'source_document' => 'Indeks Pembangunan Manusia (IPM) Jember Tahun 2025 (PDF BPS)',
                'source_table' => 'Tabel 1 dan Tabel 2',
                'description' => 'IPM Jember 2025 mencapai 71,57 (status tinggi, naik dari 70,93 pada 2024). Pengeluaran riil per kapita Rp11,14 juta per tahun.',
            ],
            [
                'title' => 'Tingkat Kemiskinan',
                'value' => 8.0000,
                'unit' => '%',
                'year' => 2026,
                'trend' => null,
                'is_higher_better' => false,
                'preferred_direction' => 'lower',
                'frequency' => 'semiannual',
                'period' => [2026, 3],
                'source_document' => 'Profil Kemiskinan Kabupaten Jember Maret 2026 (PDF BPS)',
                'source_table' => 'Ringkasan (Profiling Kemiskinan)',
                'description' => 'Persentase penduduk miskin Maret 2026 mencapai 8,00 persen (200,77 ribu jiwa), turun dari 8,67 persen pada Maret 2025 (216,76 ribu jiwa).',
            ],
            [
                'title' => 'Rata-rata Lama Sekolah',
                'value' => 6.6400,
                'unit' => 'tahun',
                'year' => 2025,
                'trend' => null,
                'is_higher_better' => true,
                'preferred_direction' => 'higher',
                'frequency' => 'annual',
                'period' => [2025],
                'source_document' => 'Indeks Pembangunan Manusia (IPM) Jember Tahun 2025 (PDF BPS)',
                'source_table' => 'Bagian 2 (Dimensi Pengetahuan)',
                'description' => 'RLS mencapai 6,64 tahun (2025), sebelumnya 6,54 (2024) dan 6,52 (2023).',
            ],
            [
                'title' => 'Angka Harapan Hidup',
                'value' => 74.3700,
                'unit' => 'tahun',
                'year' => 2025,
                'trend' => null,
                'is_higher_better' => true,
                'preferred_direction' => 'higher',
                'frequency' => 'annual',
                'period' => [2025],
                'source_document' => 'Indeks Pembangunan Manusia (IPM) Jember Tahun 2025 (PDF BPS)',
                'source_table' => 'Bagian 2 (Dimensi Kesehatan)',
                'description' => 'UHH saat lahir mencapai 74,37 tahun (2025), naik dari 74,17 (2024) dan 74,02 (2023).',
            ],
            [
                'title' => 'Produksi Padi',
                'value' => 679513.6700,
                'unit' => 'ton GKG',
                'year' => 2025,
                'trend' => null,
                'is_higher_better' => true,
                'preferred_direction' => 'higher',
                'frequency' => 'annual',
                'period' => [2025],
                'source_document' => 'Luas Panen dan Produksi Padi di Provinsi Jawa Timur 2025 (PDF BPS Provinsi)',
                'source_table' => 'Lampiran 6',
                'description' => 'Produksi padi Jember 2025 sebesar 679.513,67 ton GKG, naik 9,02 persen dari 623.264,88 ton GKG (2024).',
            ],
            [
                'title' => 'Produktivitas Padi',
                'value' => 5.0200,
                'unit' => 'ton/ha',
                'year' => 2025,
                'trend' => null,
                'is_higher_better' => true,
                'preferred_direction' => 'higher',
                'frequency' => 'annual',
                'period' => [2025],
                'source_document' => 'Luas Panen dan Produksi Padi di Provinsi Jawa Timur 2025 (PDF BPS Provinsi)',
                'source_table' => 'Lampiran 3 dan Lampiran 6 (kalkulasi)',
                'description' => 'Produktivitas GKG 2025 \u2248 679.513,67 / 135.436,35 = 5,02 ton/ha.',
            ],
            [
                'title' => 'Luas Panen Padi',
                'value' => 135436.3500,
                'unit' => 'ha',
                'year' => 2025,
                'trend' => null,
                'is_higher_better' => true,
                'preferred_direction' => 'higher',
                'frequency' => 'annual',
                'period' => [2025],
                'source_document' => 'Luas Panen dan Produksi Padi di Provinsi Jawa Timur 2025 (PDF BPS Provinsi)',
                'source_table' => 'Lampiran 3',
                'description' => 'Luas panen padi 2025 \u2248 135.436,35 ha, naik 15.366,94 ha (12,80%) dibanding 2024 (120.069,41 ha).',
            ],
            [
                'title' => 'Jumlah Penduduk',
                'value' => 2620140.0000,
                'unit' => 'jiwa',
                'year' => 2025,
                'trend' => null,
                'is_higher_better' => true,
                'preferred_direction' => 'context',
                'frequency' => 'annual',
                'period' => [2025],
                'source_document' => 'Indikator Kesejahteraan Rakyat Kabupaten Jember 2025 (PDF BPS)',
                'source_table' => 'Tabel 1.1.2 (Proyeksi Penduduk)',
                'description' => 'Proyeksi penduduk 2025: laki-laki 1.304.680 jiwa, perempuan 1.315.460 jiwa, total 2.620.140 jiwa (Rasio Jenis Kelamin 99,18).',
            ],
            [
                'title' => 'Kepadatan Penduduk',
                'value' => 792.5800,
                'unit' => 'jiwa/km2',
                'year' => 2025,
                'trend' => null,
                'is_higher_better' => true,
                'preferred_direction' => 'context',
                'frequency' => 'annual',
                'period' => [2025],
                'source_document' => 'Indikator Kesejahteraan Rakyat Kabupaten Jember 2025 (PDF BPS)',
                'source_table' => 'Tabel 1.2.1',
                'description' => 'Kepadatan 2025 \u2248 792,58 jiwa/km2 (2.620.140 jiwa / 3.306,689 km2).',
            ],
            [
                'title' => 'Rasio Jenis Kelamin',
                'value' => 99.1800,
                'unit' => 'per 100',
                'year' => 2025,
                'trend' => null,
                'is_higher_better' => true,
                'preferred_direction' => 'context',
                'frequency' => 'annual',
                'period' => [2025],
                'source_document' => 'Indikator Kesejahteraan Rakyat Kabupaten Jember 2025 (PDF BPS)',
                'source_table' => 'Tabel 1.1.2',
                'description' => 'Rasio jenis kelamin 2025 sebesar 99,18 (setiap 100 perempuan ada 99,18 laki-laki).',
            ],
        ];

        foreach ($data as $row) {
            $indicator = Indicator::where('title', $row['title'])->whereNull('deleted_at')->first();
            if (! $indicator) {
                $this->command?->warn("SKIP (tidak ditemukan): {$row['title']}");

                continue;
            }

            $periodStart = isset($row['period'][1])
                ? CarbonImmutable::create($row['period'][0], $row['period'][1], 1)
                : CarbonImmutable::create($row['period'][0], 1, 1);
            $periodEnd = $periodStart->endOfMonth();
            if ($row['frequency'] === 'annual') {
                $periodEnd = CarbonImmutable::create($row['period'][0], 12, 31);
            }

            $indicator->update([
                'value' => $row['value'],
                'unit' => $row['unit'],
                'year' => $row['year'],
                'trend' => $row['trend'],
                'is_higher_better' => $row['is_higher_better'],
                'description' => $row['description'],
                'region_level' => self::LEVEL,
                'frequency' => $row['frequency'],
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'source_document' => $row['source_document'],
                'source_table' => $row['source_table'],
                'source_page' => null,
                'preferred_direction' => $row['preferred_direction'],
                'quality_note' => sprintf('%s. Ekstraksi tabel PDF; selisih kolom pdftotext interoperasi, kolom `%s` diverifikasi konsisten aritmetik.', self::AGENCY, $row['source_table']),
            ]);
        }
    }

    private function createIndicators(): void
    {
        $data = [
            [
                'title' => 'Indeks Kedalaman Kemiskinan (P1)',
                'cat' => 'Sosial',
                'value' => 0.7100,
                'unit' => 'indeks',
                'year' => 2026,
                'is_higher_better' => false,
                'preferred_direction' => 'lower',
                'frequency' => 'semiannual',
                'period' => [2026, 3],
                'source_document' => 'Profil Kemiskinan Kabupaten Jember Maret 2026 (PDF BPS)',
                'source_table' => 'Ringkasan',
                'description' => 'P1 Maret 2026 sebesar 0,71; penurunan dibanding Maret 2025 sebesar 0,02.',
            ],
            [
                'title' => 'Indeks Keparahan Kemiskinan (P2)',
                'cat' => 'Sosial',
                'value' => 0.1000,
                'unit' => 'indeks',
                'year' => 2026,
                'is_higher_better' => false,
                'preferred_direction' => 'lower',
                'frequency' => 'semiannual',
                'period' => [2026, 3],
                'source_document' => 'Profil Kemiskinan Kabupaten Jember Maret 2026 (PDF BPS)',
                'source_table' => 'Ringkasan',
                'description' => 'P2 Maret 2026 sebesar 0,10; penurunan dibanding Maret 2025 sebesar 0,04.',
            ],
            [
                'title' => 'Garis Kemiskinan',
                'cat' => 'Sosial',
                'value' => 517125.0000,
                'unit' => 'rupiah/kapita/bulan',
                'year' => 2026,
                'is_higher_better' => false,
                'preferred_direction' => 'context',
                'frequency' => 'semiannual',
                'period' => [2026, 3],
                'source_document' => 'Profil Kemiskinan Kabupaten Jember Maret 2026 (PDF BPS)',
                'source_table' => 'Ringkasan',
                'description' => 'GK Maret 2026 Rp517.125,00 per kapita per bulan.',
            ],
            [
                'title' => 'Produksi Beras',
                'cat' => 'Pertanian',
                'value' => 392364.8900,
                'unit' => 'ton',
                'year' => 2025,
                'is_higher_better' => true,
                'preferred_direction' => 'higher',
                'frequency' => 'annual',
                'period' => [2025],
                'source_document' => 'Luas Panen dan Produksi Padi di Provinsi Jawa Timur 2025 (PDF BPS Provinsi)',
                'source_table' => 'Lampiran 9',
                'description' => 'Produksi beras Jember 2025 sebesar 392.364,89 ton (2024: 359.885,69 ton), naik 9,02 persen.',
            ],
            [
                'title' => 'Tingkat Penghunian Kamar Hotel (TPK)',
                'cat' => 'Industri',
                'value' => 33.2600,
                'unit' => '%',
                'year' => 2026,
                'is_higher_better' => true,
                'preferred_direction' => 'higher',
                'frequency' => 'monthly',
                'period' => [2026, 8],
                'source_document' => 'Perkembangan Tingkat Penghunian Kamar Hotel Kabupaten Jember Agustus 2026 (PDF BPS)',
                'source_table' => 'Tabel 1',
                'description' => 'TPK Agustus 2026 33,26 % (bintang 48,09 %; non-bintang 26,65 %), turun 1,68 pp dari Juli 2026.',
            ],
            [
                'title' => 'Rata-rata Lama Menginap Tamu (RLMT)',
                'cat' => 'Industri',
                'value' => 1.0900,
                'unit' => 'hari',
                'year' => 2026,
                'is_higher_better' => true,
                'preferred_direction' => 'higher',
                'frequency' => 'monthly',
                'period' => [2026, 8],
                'source_document' => 'Perkembangan Tingkat Penghunian Kamar Hotel Kabupaten Jember Agustus 2026 (PDF BPS)',
                'source_table' => 'Tabel 2',
                'description' => 'RLMT Agustus 2026 1,09 hari, naik 0,04 hari dari Juli 2026.',
            ],
            [
                'title' => 'Kontribusi Pertanian terhadap PDRB',
                'cat' => 'Pertanian',
                'value' => 25.4200,
                'unit' => '%',
                'year' => 2025,
                'is_higher_better' => true,
                'preferred_direction' => 'context',
                'frequency' => 'annual',
                'period' => [2025],
                'source_document' => 'Produk Domestik Regional Bruto Kabupaten Jember Menurut Lapangan Usaha 2021-2025 (PDF BPS)',
                'source_table' => 'Bagian Peranan Lapangan Usaha',
                'description' => 'Pertanian, Kehutanan, dan Perikanan menyumbang 25,42 persen PDRB 2025 (turun dari 25,71 persen 2024). Sektor industri pengolahan 21,61 persen.',
            ],
        ];

        foreach ($data as $row) {
            $category = Category::where('name', $row['cat'])->first();

            $indicator = Indicator::withTrashed()->where('title', $row['title'])->first();
            if ($indicator) {
                $indicator->restore();
            } else {
                $indicator = new Indicator();
                $indicator->category_id = $category?->id;
                $indicator->title = $row['title'];
                $indicator->unit = $row['unit'];
                $indicator->image_path = null;
            }

            $periodStart = isset($row['period'][1])
                ? CarbonImmutable::create($row['period'][0], $row['period'][1], 1)
                : CarbonImmutable::create($row['period'][0], 1, 1);
            $periodEnd = $periodStart->endOfMonth();
            if ($row['frequency'] === 'annual') {
                $periodEnd = CarbonImmutable::create($row['period'][0], 12, 31);
            }

            $indicator->fill([
                'value' => $row['value'],
                'unit' => $row['unit'],
                'year' => $row['year'],
                'trend' => null,
                'is_higher_better' => $row['is_higher_better'],
                'description' => $row['description'],
                'region_level' => self::LEVEL,
                'frequency' => $row['frequency'],
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'source_document' => $row['source_document'],
                'source_table' => $row['source_table'],
                'source_page' => null,
                'preferred_direction' => $row['preferred_direction'],
                'quality_note' => sprintf('%s. Ekstraksi tabel PDF; angka diverifikasi konsisten aritmetik.', self::AGENCY),
            ]);
            $indicator->save();
        }
    }

    private function createPhenomena(): void
    {
        $data = [
            [
                'indicator' => 'Inflasi Kota Jember (y-on-y)',
                'title' => 'Inflasi Jember Meningkat 3,04% (September 2026)',
                'impact' => 'negative',
                'insight_type' => 'isu_terkini',
                'source' => 'BPS Kabupaten Jember',
                'source_document' => 'Perkembangan Indeks Harga Konsumen Jember September 2026 (PDF BPS)',
                'description' => 'Inflasi y-on-y September 2026 sebesar 3,04 persen (Konsumsi 3,45%; Inti 1,84%; y-to-d 1,84%; m-to-m 0,27%). Komoditas pendorong: cabai rawit (0,28 pp), beras (0,22 pp), tahu & daging sapi (0,14 pp).',
            ],
            [
                'indicator' => 'Produksi Padi',
                'title' => 'Produksi Padi 2025 Naik 9,02%',
                'impact' => 'positive',
                'insight_type' => 'statistik',
                'source' => 'BPS Provinsi Jawa Timur',
                'source_document' => 'Luas Panen dan Produksi Padi di Provinsi Jawa Timur 2025 (PDF)',
                'description' => 'Produksi padi Jember 2025 mencapai 679.513,67 ton GKG (naik 56.248,79 ton, 9,02%) dengan luas panen 135.436,35 ha (naik 12,80%). Salah satu produsen padi terbesar di Jawa Timur.',
            ],
            [
                'indicator' => 'Tingkat Kemiskinan',
                'title' => 'Kemiskinan Turun ke 8,00% (Maret 2026)',
                'impact' => 'positive',
                'insight_type' => 'statistik',
                'source' => 'BPS Kabupaten Jember',
                'source_document' => 'Profil Kemiskinan Kabupaten Jember Maret 2026 (PDF)',
                'description' => 'Penduduk miskin menurun menjadi 8,00 persen (200,77 ribu jiwa), dari 8,67 persen (216,76 ribu jiwa) pada Maret 2025. GK Rp517.125,00; P1 0,71; P2 0,10.',
            ],
            [
                'indicator' => 'Tingkat Penghunian Kamar Hotel (TPK)',
                'title' => 'OKUPANSI Hotel Melemah menjadi 33,26% (Agustus 2026)',
                'impact' => 'negative',
                'insight_type' => 'isu_terkini',
                'source' => 'BPS Kabupaten Jember',
                'source_document' => 'Perkembangan Tingkat Penghunian Kamar Hotel Kabupaten Jember Agustus 2026 (PDF)',
                'description' => 'TPK hotel Agustus 2026 mencapai 33,26 persen, turun 1,68 pp dibanding Juli 2026 (hotel bintang turun 0,88 pp). RLMT 1,09 hari naik 0,04 hari.',
            ],
            [
                'indicator' => 'Indeks Pembangunan Manusia (IPM)',
                'title' => 'IPM Jember Naik ke 71,57 (Status Tinggi)',
                'impact' => 'positive',
                'insight_type' => 'statistik',
                'source' => 'BPS Kabupaten Jember',
                'source_document' => 'Indeks Pembangunan Manusia (IPM) Jember Tahun 2025 (PDF)',
                'description' => 'IPM 2025 mencapai 71,57 (2024: 70,93), rata-rata naik 0,73% per tahun 2021-2025. UHH 74,37 tahun; HLS 13,51 tahun; RLS 6,64 tahun; pengeluaran riil per kapita Rp11.147 ribu per tahun.',
            ],
            [
                'indicator' => 'PDRB ADHK (Pertumbuhan Ekonomi)',
                'title' => 'Perekonomian 2025 Tumbuh 5,47%',
                'impact' => 'positive',
                'insight_type' => 'statistik',
                'source' => 'BPS Kabupaten Jember',
                'source_document' => 'Pertumbuhan Ekonomi Kabupaten Jember 2025 (PDF)',
                'description' => 'PDRB ADHK 2025 tumbuh 5,47 persen; ADHB Rp110,98 triliun. Pertanian masih dominan (25,42%) namun Industri Pengolahan naik (21,61%).',
            ],
            [
                'indicator' => 'Tingkat Pengangguran Terbuka (TPT)',
                'title' => 'TPT Turun ke 3,07% (Agustus 2025)',
                'impact' => 'positive',
                'insight_type' => 'statistik',
                'source' => 'BPS Kabupaten Jember',
                'source_document' => 'Keadaan Ketenagakerjaan Kabupaten Jember Agustus 2025 (PDF)',
                'description' => 'TPT Agustus 2025 3,07 persen, turun 0,16 pp dari 2024. Angkatan kerja 1,58 juta orang, seluruhnya bekerja 1,53 juta orang. TPT tamatan universitas masih tinggi (4,32-7,24%).',
            ],
            [
                'indicator' => 'Kontribusi Pertanian terhadap PDRB',
                'title' => 'PODES 2025: 211 dari 248 Desa Bermata Pencaharian Pertanian',
                'impact' => 'neutral',
                'insight_type' => 'statistik',
                'source' => 'BPS Kabupaten Jember',
                'source_document' => 'Statistik Potensi Desa Kabupaten Jember 2025 (PDF)',
                'published_at' => '2025-12-10',
                'description' => 'Hasil PODES 2025: 248 desa/kelurahan (226 desa + 22 kelurahan). Mayoritas warga bergantung pertanian (211 desa). 21 desa mencatat pencemaran air. Sarana: 14 RS, 45 puskesmas rawat inap, 70 SMA swasta terbanyak.',
            ],
        ];

        $order = (int) (Phenomenon::max('order') ?? 0) + 1;
        foreach ($data as $row) {
            $exists = Phenomenon::where('title', $row['title'])->first();
            if ($exists) {
                continue;
            }

            $indicator = Indicator::where('title', $row['indicator'])
                ->whereNull('deleted_at')
                ->first();

            Phenomenon::create([
                'indicator_id' => $indicator?->id,
                'title' => $row['title'],
                'description' => $row['description'],
                'impact' => $row['impact'],
                'source' => $row['source'],
                'order' => $order++,
                'insight_type' => $row['insight_type'],
                'source_document' => $row['source_document'],
                'source_page' => null,
                'published_at' => $row['published_at'] ?? null,
                'author' => self::AGENCY,
                'quality_note' => 'Ekstraksi hasil pemrosesan PDF/SPT oleh PSD.',
            ]);
        }
    }
}
