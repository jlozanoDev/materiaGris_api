<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LlmInteraction extends Model
{
    /** @use HasFactory<\Database\Factories\LlmInteractionFactory> */
    use HasFactory;

    public const TYPE_STT = 'stt';

    protected $fillable = [
        "patient_report_id",
        "type",
        "request_payload",
        "response_payload",
        "processing_time_ms",
    ];

    protected $casts = [
        "request_payload" => "array",
        "response_payload" => "array",
        "type" => "string",
    ];

    public function patientReport(): BelongsTo
    {
        return $this->belongsTo(PatientReport::class);
    }
}
