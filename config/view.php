<?php

return [
    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    |
    | By default Laravel looks for views in the `resources/views` folder. In
    | API-only mode we keep this empty so the framework does not attempt to
    | resolve Blade templates during normal API requests. This is a
    | non-destructive change: views files remain on disk.
    |
    */

    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | This value determines where all the compiled Blade templates will be
    | stored for your application. The default is within the storage folder.
    |
    */

    'compiled' => env(
        'VIEW_COMPILED_PATH', realpath(storage_path('framework/views'))
    ),
];
