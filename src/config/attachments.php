<?php

return [
    'upload' => [
        'threshold' => (int) env('ATTACHMENT_UPLOAD_THRESHOLD', 10 * 1024 * 1024),
        'chunk_size' => (int) env('ATTACHMENT_UPLOAD_CHUNK_SIZE', 5 * 1024 * 1024),
        'expiry_minutes' => (int) env('ATTACHMENT_UPLOAD_EXPIRY_MINUTES', 60),
    ],
];
