<?php

return [
    'timeout' => (int) env('SYNC_TIMEOUT', 20),
    'connect_timeout' => (int) env('SYNC_CONNECT_TIMEOUT', 5),
];
