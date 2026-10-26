<?php

return [
    /*
    | Analisis AI dijalankan di server. Aplikasi mobile hanya menjadi klien
    | /api/ai/insight dan tidak menyimpan API key Gemini.
    */
    'path' => env('AI_INSIGHT_PATH', '/api/ai/insight'),

    'timeout' => (int) env('GEMINI_TIMEOUT', 30),

    'connect_timeout' => (int) env('GEMINI_CONNECT_TIMEOUT', 5),
];
