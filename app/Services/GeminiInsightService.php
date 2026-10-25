<?php

namespace App\Services;

use App\Models\AiInsight;
use App\Models\Indicator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GeminiInsightService
{
    private const PROMPT_VERSION = 'v1';

    private const MAX_RECOMMENDATIONS = 6;

    public function isConfigured(): bool
    {
        return trim((string) config('gemini.api_key')) !== '';
    }

    public function modelName(): string
    {
        return (string) config('gemini.model', 'gemini-2.5-flash');
    }

    public function cachedForIndicator(Indicator $indicator, Collection $history): ?array
    {
        return $this->cached($this->hash('indicator', $this->indicatorContext($indicator, $history)));
    }

    public function analyzeIndicator(Indicator $indicator, Collection $history, bool $forceRefresh = true): array
    {
        $context = $this->indicatorContext($indicator, $history);

        return $this->analyze(
            'indicator',
            $context,
            $indicator,
            $forceRefresh,
            fn (array $context): array => $this->localIndicatorInsight($context),
        );
    }

    public function cachedForDashboard(int $year, Collection $categories, array $stats): ?array
    {
        return $this->cached($this->hash('dashboard', $this->dashboardContext($year, $categories, $stats)));
    }

    public function analyzeDashboard(int $year, Collection $categories, array $stats, bool $forceRefresh = true): array
    {
        $context = $this->dashboardContext($year, $categories, $stats);

        return $this->analyze(
            'dashboard',
            $context,
            null,
            $forceRefresh,
            fn (array $context): array => $this->localDashboardInsight($context),
        );
    }

    private function analyze(
        string $scope,
        array $context,
        ?Indicator $indicator,
        bool $forceRefresh,
        callable $localFallback,
    ): array {
        $hash = $this->hash($scope, $context);
        $cached = $this->cached($hash);

        if ($cached !== null && ! $forceRefresh) {
            return $cached;
        }

        if (! $this->isConfigured()) {
            return $cached !== null
                ? $this->withNotice($cached, 'GEMINI_API_KEY belum diatur. Menampilkan analisis tersimpan terakhir dari perangkat.')
                : $localFallback($context);
        }

        try {
            $content = $this->request($scope, $context);
            $generatedAt = Carbon::now();
            $this->store($hash, $scope, $indicator, $context, $content, $generatedAt);

            return $this->format($content, 'gemini', $generatedAt, $this->modelName());
        } catch (Throwable $exception) {
            Log::warning('Gemini insight request failed', [
                'scope' => $scope,
                'error' => $exception->getMessage(),
            ]);

            if ($cached !== null) {
                return $this->withNotice($cached, 'Gemini tidak dapat dihubungi. Menampilkan analisis tersimpan di perangkat.');
            }

            return $this->withNotice(
                $localFallback($context),
                'Gemini tidak dapat dihubungi (offline atau layanan tidak tersedia). Menampilkan analisis lokal berbasis data.',
            );
        }
    }

    private function request(string $scope, array $context): array
    {
        $model = $this->modelName();
        $endpoint = rtrim((string) config('gemini.endpoint', 'https://generativelanguage.googleapis.com/v1beta'), '/');

        $response = Http::acceptJson()
            ->withHeaders(['x-goog-api-key' => (string) config('gemini.api_key')])
            ->connectTimeout((int) config('gemini.connect_timeout', 5))
            ->timeout((int) config('gemini.timeout', 30))
            ->post($endpoint . '/models/' . $model . ':generateContent', [
                'systemInstruction' => [
                    'parts' => [[
                        'text' => 'Anda adalah analis data dan kebijakan daerah BPS Kabupaten Jember. Gunakan bahasa Indonesia formal, ringkas, dan berbasis data. Jangan mengarang angka. Jawab hanya dengan JSON valid sesuai skema.',
                    ]],
                ],
                'contents' => [[
                    'role' => 'user',
                    'parts' => [[
                        'text' => $this->prompt($scope, $context),
                    ]],
                ]],
                'generationConfig' => [
                    'temperature' => 0.4,
                    'responseMimeType' => 'application/json',
                    'responseSchema' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'ringkasan_eksekutif' => ['type' => 'STRING'],
                            'analisis_akar_masalah' => ['type' => 'STRING'],
                            'rekomendasi_kebijakan' => [
                                'type' => 'ARRAY',
                                'items' => ['type' => 'STRING'],
                            ],
                        ],
                        'required' => ['ringkasan_eksekutif', 'analisis_akar_masalah', 'rekomendasi_kebijakan'],
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Gemini API returned HTTP ' . $response->status());
        }

        $text = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        $data = $this->decodeResponse($text);

        $summary = trim((string) ($data['ringkasan_eksekutif'] ?? $data['summary'] ?? ''));
        $rootCause = trim((string) ($data['analisis_akar_masalah'] ?? $data['root_cause'] ?? ''));
        $recommendations = collect($data['rekomendasi_kebijakan'] ?? $data['recommendations'] ?? [])
            ->filter(fn ($item): bool => is_string($item) && trim($item) !== '')
            ->map(fn (string $item): string => trim($item))
            ->take(self::MAX_RECOMMENDATIONS)
            ->values()
            ->all();

        if ($summary === '' || $rootCause === '' || $recommendations === []) {
            throw new RuntimeException('Gemini returned an incomplete analysis payload.');
        }

        return [
            'summary' => $summary,
            'root_cause' => $rootCause,
            'recommendations' => $recommendations,
        ];
    }

    private function decodeResponse(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            throw new RuntimeException('Gemini returned an empty response.');
        }

        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $text) ?? $text;
        }

        $data = json_decode($text, true);

        if (! is_array($data)) {
            throw new RuntimeException('Gemini response is not valid JSON.');
        }

        return $data;
    }

    private function prompt(string $scope, array $context): string
    {
        $data = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $focus = $scope === 'indicator'
            ? 'Fokus pada indikator yang dianalisis: makna capaian, arah tren, akar masalah dari fenomena lokal, serta rekomendasi kebijakan yang dapat dijalankan pemerintah daerah.'
            : 'Fokus pada gambaran kabupaten: indikator prioritas, keseimbangan tren positif dan negatif, akar masalah agregat, serta prioritas kebijakan daerah.';

        return <<<PROMPT
        Analisis konteks data statistik daerah berikut.

        {$focus}

        Konteks data (JSON):
        {$data}

        Hasilkan tepat tiga bagian:
        1. ringkasan_eksekutif: 3-5 kalimat padat untuk pengambil keputusan.
        2. analisis_akar_masalah: 3-5 kalimat yang menghubungkan angka, tren, dan fenomena lokal.
        3. rekomendasi_kebijakan: 3-5 butir aksi konkret, terukur, dan relevan untuk Kabupaten Jember.

        Jangan menambahkan angka yang tidak ada di konteks. Jangan menulis markdown.
        PROMPT;
    }

    private function indicatorContext(Indicator $indicator, Collection $history): array
    {
        $phenomena = $indicator->relationLoaded('phenomena')
            ? $indicator->phenomena
            : $indicator->phenomena()->orderBy('order')->get();

        return [
            'jenis_analisis' => 'indicator',
            'tahun' => (int) $indicator->year,
            'indikator' => [
                'judul' => $indicator->title,
                'kategori' => $indicator->category?->name,
                'nilai' => (float) $indicator->value,
                'satuan' => $indicator->unit,
                'tahun' => (int) $indicator->year,
                'tren_persen' => $indicator->trend !== null ? (float) $indicator->trend : null,
                'lebih_tinggi_lebih_baik' => (bool) $indicator->is_higher_better,
                'deskripsi' => $indicator->description,
                'wilayah' => $indicator->region_level,
                'frekuensi' => $indicator->frequency,
                'sumber_dokumen' => $indicator->source_document,
                'catatan_kualitas' => $indicator->quality_note,
            ],
            'time_series' => $history->map(fn (Indicator $row): array => [
                'tahun' => (int) $row->year,
                'nilai' => (float) $row->value,
                'tren_persen' => $row->trend !== null ? (float) $row->trend : null,
            ])->values()->all(),
            'fenomena_lokal' => $phenomena->take(10)->map(fn ($phenomenon): array => [
                'judul' => $phenomenon->title,
                'dampak' => $phenomenon->impact,
                'deskripsi' => $phenomenon->description,
                'sumber' => $phenomenon->source,
            ])->values()->all(),
        ];
    }

    private function dashboardContext(int $year, Collection $categories, array $stats): array
    {
        $rows = [];

        foreach ($categories as $category) {
            foreach ($category->indicators as $indicator) {
                $rows[] = [
                    'judul' => $indicator->title,
                    'kategori' => $category->name,
                    'nilai' => (float) $indicator->value,
                    'satuan' => $indicator->unit,
                    'tren_persen' => $indicator->trend !== null ? (float) $indicator->trend : null,
                    'lebih_tinggi_lebih_baik' => (bool) $indicator->is_higher_better,
                ];
            }
        }

        $priorities = collect($rows)
            ->filter(fn (array $row): bool => $row['tren_persen'] !== null)
            ->sortByDesc(fn (array $row): float => abs((float) $row['tren_persen']))
            ->take(10)
            ->values()
            ->all();

        return [
            'jenis_analisis' => 'dashboard',
            'tahun' => $year,
            'statistik' => [
                'jumlah_kategori' => (int) ($stats['total_categories'] ?? 0),
                'jumlah_indikator' => (int) ($stats['total_indicators'] ?? 0),
                'tren_positif' => (int) ($stats['positive_trends'] ?? 0),
                'tren_negatif' => (int) ($stats['negative_trends'] ?? 0),
            ],
            'kategori' => $categories->map(fn ($category): array => [
                'nama' => $category->name,
                'jumlah_indikator' => $category->indicators->count(),
            ])->values()->all(),
            'indikator_prioritas' => $priorities,
        ];
    }

    private function localIndicatorInsight(array $context): array
    {
        $indicator = $context['indikator'];
        $trend = $indicator['tren_persen'];
        $higherIsBetter = (bool) $indicator['lebih_tinggi_lebih_baik'];
        $formattedValue = number_format((float) $indicator['nilai'], 2, ',', '.') . ' ' . $indicator['satuan'];

        $trendText = $trend === null
            ? 'Data tren persentase belum tersedia untuk periode ini.'
            : sprintf('Tren tercatat %s%.2f%% dibanding periode sebelumnya.', $trend > 0 ? '+' : '', (float) $trend);

        $favorable = null;
        if ($trend !== null && (float) $trend !== 0.0) {
            $favorable = $higherIsBetter ? (float) $trend > 0 : (float) $trend < 0;
        }

        $summary = sprintf(
            '%s tahun %d tercatat %s. %s Indikator ini %s.',
            $indicator['judul'],
            (int) $indicator['tahun'],
            $formattedValue,
            $trendText,
            $higherIsBetter ? 'dinilai membaik ketika nilainya meningkat' : 'dinilai membaik ketika nilainya menurun',
        );

        $negativePhenomena = collect($context['fenomena_lokal'] ?? [])
            ->where('dampak', 'negative')
            ->pluck('judul')
            ->filter()
            ->take(3)
            ->values();

        $rootCause = $negativePhenomena->isNotEmpty()
            ? 'Fenomena lokal yang perlu ditelusuri sebagai akar masalah: ' . $negativePhenomena->implode('; ') . '.'
            : 'Belum ada fenomena negatif terverifikasi pada basis data lokal. Telusuri penyebab perubahan melalui data sektoral dan verifikasi lapangan sebelum menetapkan intervensi.';

        if (! empty($indicator['deskripsi'])) {
            $rootCause .= ' Konteks indikator: ' . $indicator['deskripsi'];
        }

        if ($favorable === true) {
            $recommendations = [
                'Pertahankan dan perluas program yang berkontribusi pada capaian positif ' . $indicator['judul'] . '.',
                'Dokumentasikan praktik baik lintas perangkat daerah agar capaian dapat direplikasi.',
                'Lanjutkan pemantauan time-series dan bandingkan dengan wilayah pembanding.',
            ];
        } elseif ($favorable === false) {
            $recommendations = [
                'Lakukan evaluasi mendalam atas penyebab perlambatan atau perburukan ' . $indicator['judul'] . '.',
                'Perkuat koordinasi lintas perangkat daerah untuk intervensi pada fenomena negatif yang tercatat.',
                'Tetapkan target antara yang terukur dan pantau perkembangannya setiap periode rilis data.',
            ];
        } else {
            $recommendations = [
                'Lengkapi data tren dan fenomena lokal agar arah kebijakan dapat ditetapkan secara lebih presisi.',
                'Verifikasi kualitas data dengan sumber resmi BPS sebelum mengambil keputusan.',
                'Siapkan skenario kebijakan untuk arah perubahan positif maupun negatif.',
            ];
        }

        return $this->format([
            'summary' => $summary,
            'root_cause' => $rootCause,
            'recommendations' => $recommendations,
        ], 'local', Carbon::now(), null, 'Analisis lokal disusun dari data perangkat. Aktifkan GEMINI_API_KEY untuk analisis AI.');
    }

    private function localDashboardInsight(array $context): array
    {
        $stats = $context['statistik'];
        $year = (int) $context['tahun'];
        $negative = (int) $stats['tren_negatif'];
        $positive = (int) $stats['tren_positif'];

        $priority = collect($context['indikator_prioritas'] ?? [])->take(5);
        $priorityText = $priority
            ->map(fn (array $item): string => sprintf(
                '%s (%s) %+.2f%%',
                $item['judul'],
                $item['kategori'],
                (float) $item['tren_persen'],
            ))
            ->implode('; ');

        $summary = sprintf(
            'Dashboard %d memuat %d indikator dalam %d kategori, dengan %d tren positif dan %d tren negatif.',
            $year,
            (int) $stats['jumlah_indikator'],
            (int) $stats['jumlah_kategori'],
            $positive,
            $negative,
        );

        if ($priorityText !== '') {
            $summary .= ' Perubahan terbesar: ' . $priorityText . '.';
        }

        $rootCause = $negative > 0
            ? 'Tekanan utama berasal dari indikator yang bergerak ke arah tidak menguntungkan. Perlu penelusuran penyebab lintas sektor, validasi data, dan identifikasi wilayah terdampak sebelum intervensi dijalankan.'
            : 'Tidak ada tren negatif pada periode ini. Namun capaian tetap perlu diverifikasi kualitas datanya dan diuji ketahanannya terhadap perubahan kondisi daerah.';

        $recommendations = $negative > 0
            ? [
                'Prioritaskan indikator dengan tren negatif terbesar untuk evaluasi dan intervensi lintas perangkat daerah.',
                'Validasi data indikator prioritas bersama sumber resmi BPS dan dinas teknis.',
                'Tetapkan target antara per indikator dan pantau setiap periode rilis data.',
            ]
            : [
                'Pertahankan kebijakan yang menjaga tren positif dan dokumentasikan praktik baiknya.',
                'Perkuat sistem peringatan dini untuk mendeteksi perubahan tren pada periode berikutnya.',
                'Lengkapi fenomena lokal sebagai dasar analisis akar masalah yang lebih tajam.',
            ];

        return $this->format([
            'summary' => $summary,
            'root_cause' => $rootCause,
            'recommendations' => $recommendations,
        ], 'local', Carbon::now(), null, 'Analisis lokal disusun dari data perangkat. Aktifkan GEMINI_API_KEY untuk analisis AI.');
    }

    private function cached(string $hash): ?array
    {
        try {
            $record = AiInsight::query()->where('hash', $hash)->first();
        } catch (Throwable $exception) {
            Log::warning('AI insight cache unavailable', ['error' => $exception->getMessage()]);

            return null;
        }

        if ($record === null) {
            return null;
        }

        return $this->format(
            (array) $record->payload,
            'cache',
            $record->generated_at,
            $record->model,
        );
    }

    private function store(
        string $hash,
        string $scope,
        ?Indicator $indicator,
        array $context,
        array $content,
        Carbon $generatedAt,
    ): void {
        try {
            AiInsight::query()->updateOrCreate(
                ['hash' => $hash],
                [
                    'scope' => $scope,
                    'indicator_id' => $indicator?->id,
                    'year' => isset($context['tahun']) ? (int) $context['tahun'] : null,
                    'model' => $this->modelName(),
                    'source' => 'gemini',
                    'payload' => $content,
                    'generated_at' => $generatedAt,
                ],
            );
        } catch (Throwable $exception) {
            Log::warning('AI insight cache write failed', [
                'scope' => $scope,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function format(
        array $content,
        string $source,
        ?Carbon $generatedAt,
        ?string $model,
        ?string $notice = null,
    ): array {
        return [
            'summary' => trim((string) ($content['summary'] ?? '')),
            'root_cause' => trim((string) ($content['root_cause'] ?? '')),
            'recommendations' => array_values(array_filter(
                (array) ($content['recommendations'] ?? []),
                fn ($item): bool => is_string($item) && trim($item) !== '',
            )),
            'source' => $source,
            'model' => $model,
            'generated_at' => $generatedAt?->toIso8601String(),
            'generated_at_label' => $generatedAt
                ?->timezone(config('app.timezone'))
                ->locale(config('app.locale'))
                ->translatedFormat('d M Y, H:i'),
            'notice' => $notice,
        ];
    }

    private function withNotice(array $insight, string $notice): array
    {
        $insight['notice'] = $notice;

        return $insight;
    }

    private function hash(string $scope, array $context): string
    {
        return hash('sha256', implode('|', [
            self::PROMPT_VERSION,
            $scope,
            $this->modelName(),
            json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '',
        ]));
    }
}
