<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Indicator;
use App\Models\Phenomenon;
use Illuminate\Database\Seeder;
use NpAnalytics\Core\Data\MasterDataImporter;

class MasterStrategicDataJemberSeeder extends Seeder
{
    public function run(): void
    {
        // Data master dimuat dari paket bersama agar identik dengan server.
        // Record yang sudah ada tidak ditimpa; data bawaan dibuat bersih
        // (bukan dirty) supaya sinkronisasi pertama tidak mengirim duplikat.
        (new MasterDataImporter(Category::class, Indicator::class, Phenomenon::class))
            ->import(false, [
                'dirty' => false,
                'base_version' => 0,
                'server_version' => 0,
            ]);
    }
}
