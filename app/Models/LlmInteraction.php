<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LlmInteraction extends Model
{
    /** @use HasFactory<\Database\Factories\LlmInteractionFactory> */
    use HasFactory;

    protected $fillable = [
        "patient_report_id",
        "request_payload",
        "response_payload",
        "processing_time_ms",
    ];

    protected $casts = [
        "request_payload" => "array",
        "response_payload" => "array",
    ];

    public function patientReport(): BelongsTo
    {
        return $this->belongsTo(PatientReport::class);
    }
}
