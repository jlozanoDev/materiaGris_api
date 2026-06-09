<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientReport extends Model
{
    use HasFactory;

    protected $table = 'patient_reports';

    protected $fillable = [
        'patient_id',
        'user_id',
        'template_id',
        'status',
        'template_structure_snapshot',
        'values',
        'signature_path',
        'pdf_path',
        'signed_at',
        'closed_at',
    ];

    protected $casts = [
        'status' => ReportStatus::class,
        'values' => 'array',
        'template_structure_snapshot' => 'array',
        'signed_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class)->withTrashed();
    }
}
