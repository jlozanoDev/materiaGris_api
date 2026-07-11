<?php

namespace App\Models;

use Database\Factories\ClinicFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
{
    /** @use HasFactory<ClinicFactory> */
    use HasFactory;

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'email',
        'ciudad',
        'provincia',
        'codigo_postal',
        'web',
        'cuit',
        'logo',
    ];

    /**
     * Transform logo from raw filename to absolute URL in JSON responses.
     */
    public function toArray(): array
    {
        $data = parent::toArray();

        if (! empty($this->attributes['logo'])) {
            $data['logo'] = route('logo.show', ['filename' => $this->attributes['logo']]);
        }

        return $data;
    }
}
