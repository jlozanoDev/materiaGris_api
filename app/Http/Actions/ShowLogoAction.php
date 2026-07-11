<?php

namespace App\Http\Actions;

use Illuminate\Support\Facades\Storage;

class ShowLogoAction
{
    public function __invoke(string $filename): mixed
    {
        if (! Storage::disk('public')->exists('logos/' . $filename)) {
            abort(404);
        }

        return Storage::disk('public')->response('logos/' . $filename);
    }
}
