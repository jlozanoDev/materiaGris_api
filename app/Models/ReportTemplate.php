<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'report_templates';

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'structure',
    ];

    protected $casts = [
        'structure' => 'array',
        'is_active' => 'boolean',
    ];
}
