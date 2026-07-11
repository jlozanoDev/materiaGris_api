<?php

namespace App\Repositories\Clinic;

use App\Models\Clinic;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ClinicSaveRepository
{
    public function getOrFail(): Clinic
    {
        return Clinic::firstOrFail();
    }

    public function update(array $data): Clinic
    {
        $clinic = Clinic::first() ?? new Clinic();
        $clinic->fill($data);
        $clinic->save();
        return $clinic->fresh();
    }

    public function updateLogo(UploadedFile $file): Clinic
    {
        $clinic = Clinic::firstOrFail();

        // Delete old logo if exists
        if ($clinic->logo) {
            Storage::disk('public')->delete('logos/' . $clinic->logo);
        }

        $filename = $clinic->id . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->storeAs('logos', $filename, 'public');

        $clinic->update(['logo' => $filename]);

        return $clinic->fresh();
    }
}
