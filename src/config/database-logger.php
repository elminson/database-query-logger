<?php

// @codeCoverageIgnore
return [
    /*
    |--------------------------------------------------------------------------
    | Database Query Logger Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can configure the database query logger settings.
    |
    */

    // Enable or disable console output
    'console_output' => env('DB_LOGGER_CONSOLE_OUTPUT', false),

    // Enable or disable file logging
    'file_logging' => env('DB_LOGGER_FILE_LOGGING', false),

    // Path to the log file
    'log_file' => env('DB_LOGGER_FILE_PATH', storage_path('logs/database-queries.log')),

    // Enable or disable the logger completely
    'enabled' => env('DB_LOGGER_ENABLED', false),
];
