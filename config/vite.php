<?php

return [
    'hot_file' => public_path('hot'),
    'build_directory' => 'build',
    'manifest' => public_path('build/manifest.json'),
    'dev_server' => [
        'url' => env('VITE_DEV_SERVER_URL', 'http://localhost:5173'),
        'timeout' => 3,
    ],
];
