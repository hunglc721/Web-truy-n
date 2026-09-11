<?php

return [
    'enabled' => env('READER_VARIANTS_ENABLED', true),
    'quality' => 88,
    'max_pixels' => 24000000,
    // Never run image transformations in an upload request, even with QUEUE_CONNECTION=sync.
    'connection' => env('READER_QUEUE_CONNECTION', 'reader-images'),
    'queue' => 'reader-images',
];
