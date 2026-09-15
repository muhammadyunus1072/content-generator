<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Temporary File Uploads
    |--------------------------------------------------------------------------
    |
    | Livewire default limits temporary file uploads to 12MB (12288 KB).
    | We override this to allow up to 500MB (512000 KB) for video processing.
    |
    */

    'temporary_file_upload' => [
        'disk' => null,
        'rules' => ['file', 'max:512000'], // 500MB
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a', 'jpg',
            'jpeg', 'mpga', 'webp', 'wma',
        ],
    ],

];
