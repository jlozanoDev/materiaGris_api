<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        return [
            'name'   => ucfirst($name),
            'slug'   => Str::slug($name),
            'action' => fake()->randomElement(['view', 'create', 'update', 'delete']),
        ];
    }
}
