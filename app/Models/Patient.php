<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Patient extends Model
{
    use HasFactory;
    protected $table = 'patients';
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int,string>
     */
    protected $fillable = [
        'medical_record_number',
        'national_id',
        'first_name',
        'last_name',
        'second_last_name',
        'gender',
        'date_of_birth',
        'city',
        'insurance_id',
        'is_active',
        'last_visit_at',
        // Contact
        'email',
        'phone',
        'mobile',
        'contact_name',
        'contact_phone',
        // Address
        'address_line1',
        'address_line2',
        'neighborhood',
        'postal_code',
        'state',
        'country',
    ];

    /**
     * Accessors to append when serializing.
     *
     * @var array<int,string>
     */
    protected $appends = [
        'age',
        'full_name',
    ];

    /**
     * Attribute casts.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'last_visit_at' => 'datetime',
        'is_active' => 'boolean',
        'email' => 'string',
        'phone' => 'string',
        'mobile' => 'string',
        'contact_name' => 'string',
        'contact_phone' => 'string',
        'address_line1' => 'string',
        'address_line2' => 'string',
        'neighborhood' => 'string',
        'postal_code' => 'string',
        'state' => 'string',
        'country' => 'string',
    ];

    /**
     * Age calculated from `date_of_birth`.
     */
    public function getAgeAttribute(): ?int
    {
        if (! $this->date_of_birth) {
            return null;
        }

        return Carbon::parse($this->date_of_birth)->age;
    }

    /**
     * Full name combined.
     */
    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->last_name,
            $this->second_last_name,
        ])));
    }
}
