<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Gemini API Key
    |--------------------------------------------------------------------------
    |
    | Your API key for the Google Gemini API. You can obtain this from
    | Google AI Studio.
    |
    */
    'api_key' => env('GEMINI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the Gemini API.
    |
    */
    'base_url' => env('GEMINI_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The request timeout in seconds.
    |
    */
    'request_timeout' => env('GEMINI_REQUEST_TIMEOUT', 60), // Increased to 60s for handling longer transcript prompts
];
