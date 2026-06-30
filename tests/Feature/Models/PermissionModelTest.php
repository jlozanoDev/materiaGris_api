<?php

namespace Tests\Feature\Models;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Permission;
use App\Models\PermissionCategory;
use App\Models\Role;

class PermissionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_permission(): void
    {
        $permission = Permission::factory()->create([
            'slug' => 'patient.view.test',
            'name' => 'View Patients Test',
        ]);

        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'slug' => 'patient.view.test',
            'name' => 'View Patients Test',
        ]);
    }

    public function test_fillable_fields_can_be_mass_assigned(): void
    {
        $category = PermissionCategory::firstOrCreate(
            ['slug' => 'patients'],
            ['name' => 'Patients', 'description' => 'Patient management']
        );

        $permission = Permission::create([
            'category_id' => $category->id,
            'name' => 'Manage Reports',
            'slug' => 'report.manage.test',
            'action' => 'manage',
            'description' => 'Full report management',
        ]);

        $this->assertEquals('Manage Reports', $permission->name);
        $this->assertEquals('report.manage.test', $permission->slug);
        $this->assertEquals('manage', $permission->action);
        $this->assertEquals('Full report management', $permission->description);
    }

    public function test_belongs_to_category(): void
    {
        $category = PermissionCategory::firstOrCreate(
            ['slug' => 'reports'],
            ['name' => 'Reports', 'description' => 'Report management']
        );
        $permission = Permission::factory()->create(['category_id' => $category->id, 'slug' => 'cat.test.permission']);

        $this->assertInstanceOf(PermissionCategory::class, $permission->category);
        $this->assertEquals($category->id, $permission->category->id);
    }

    public function test_belongs_to_many_roles(): void
    {
        $permission = Permission::factory()->create(['slug' => 'roles.test.view']);
        $role = Role::factory()->create();

        $permission->roles()->attach($role->id, ['grant' => 1]);

        $this->assertCount(1, $permission->roles);
        $this->assertEquals($role->id, $permission->roles->first()->id);
    }

    public function test_role_pivot_includes_grant(): void
    {
        $permission = Permission::factory()->create(['slug' => 'pivot.test.view']);
        $role = Role::factory()->create();

        $permission->roles()->attach($role->id, ['grant' => 1]);

        $this->assertEquals(1, $permission->roles->first()->pivot->grant);
    }
}
