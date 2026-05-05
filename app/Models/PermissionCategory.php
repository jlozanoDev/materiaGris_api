<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'order',
        'parent_id',
    ];

    public function parent()
    {
        return $this->belongsTo(PermissionCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(PermissionCategory::class, 'parent_id');
    }

    public function permissions()
    {
        return $this->hasMany(Permission::class, 'category_id');
    }

    public function getFullNameAttribute()
    {
        if ($this->parent) {
            return $this->parent->full_name . ' > ' . $this->name;
        }
        return $this->name;
    }
}
