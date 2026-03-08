<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default OCR Engine
    |--------------------------------------------------------------------------
    |
    | The default OCR engine to use. Options: 'paddle', 'tesseract', 'auto'
    | 'auto' will use PaddleOCR if available, fallback to Tesseract
    |
    */
    'default' => env('OCR_DEFAULT_ENGINE', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | PaddleOCR Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for PaddleOCR Docker microservice
    |
    */
    'paddle' => [
        'url' => env('PADDLE_OCR_URL', 'http://localhost:8000'),
        'timeout' => env('PADDLE_OCR_TIMEOUT', 120),
        'enabled' => env('PADDLE_OCR_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tesseract Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for local Tesseract OCR
    |
    */
    'tesseract' => [
        'path' => env('TESSERACT_PATH', 'tesseract'),
        'lang' => env('TESSERACT_LANG', 'eng+msa+ara'),
        'enabled' => env('TESSERACT_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Processing
    |--------------------------------------------------------------------------
    |
    | Configuration for image preprocessing
    |
    */
    'image' => [
        'max_width' => 4096,
        'max_height' => 4096,
        'quality' => 95,
        'temp_directory' => storage_path('app/temp/ocr'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Quality Thresholds
    |--------------------------------------------------------------------------
    |
    | Thresholds for determining if OCR result is acceptable
    |
    */
    'thresholds' => [
        'min_text_length' => 10,
        'min_confidence' => 0.6,
        'fallback_threshold' => 20, // Try fallback if text shorter than this
    ],
];
