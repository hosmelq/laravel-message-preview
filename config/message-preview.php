<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Auto-refresh
    |--------------------------------------------------------------------------
    |
    | The number of seconds between checks for changed files while
    | Auto-refresh is on. When a file changes, the preview renders the
    | open message again. The interval must be at least one second.
    |
    */

    'auto_refresh' => [
        'interval' => (int) env('MESSAGE_PREVIEW_AUTO_REFRESH_INTERVAL', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Rollback
    |--------------------------------------------------------------------------
    |
    | When enabled, each render runs inside a transaction on the default
    | database connection and rolls it back afterwards, so the models
    | saved by sample data are not kept.
    |
    */

    'database' => [
        'rollback' => (bool) env('MESSAGE_PREVIEW_DATABASE_ROLLBACK', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Discovery
    |--------------------------------------------------------------------------
    |
    | The preview lists the mailables and mail notifications found in these
    | directories. Every class in them is loaded through Composer's
    | autoloader, so list only the directories that hold messages.
    |
    */

    'discovery' => [
        'paths' => [app_path()],
    ],

    /*
    |--------------------------------------------------------------------------
    | Route
    |--------------------------------------------------------------------------
    |
    | This value is the URL prefix of the preview. With the default value,
    | the preview opens at /message-preview on your local site.
    |
    */

    'route' => [
        'path' => env('MESSAGE_PREVIEW_ROUTE_PATH', 'message-preview'),
    ],

];
