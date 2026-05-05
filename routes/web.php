<?php

use Illuminate\Support\Facades\Route;

// Web routes are disabled in API-only mode. Any web request returns 404.
Route::any('/', function () {
    abort(404);
});

// Web routes are intentionally left to return 404 in API-only mode.
Route::fallback(function () {
    abort(404);
});
