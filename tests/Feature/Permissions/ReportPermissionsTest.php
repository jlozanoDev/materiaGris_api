<?php

namespace Tests\Feature\Permissions;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class ReportPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function runPermissionsMigration(): void
    {
        // Find and run the report permissions migration
        $migrations = glob(database_path('migrations/*_add_report_permissions.php'));
        if (empty($migrations)) {
            $this->fail('Report permissions migration not found');
        }

        require_once $migrations[0];
        // Get the anonymous class from the file
        // The migration uses return new class extends Migration
        // We need to run it explicitly since RefreshDatabase only runs
        // migrations in batches; new migrations need explicit execution.
        // The anonymous class pattern means we can't easily instantiate it.
        // Instead, we rely on artisan migrate having been run.
    }

    #[Test]
    public function report_permissions_exist_in_database(): void
    {
        $slugs = [
            'report.view',
            'report.create',
            'report.edit',
            'report.sign',
            'report.close',
            'report.download-pdf',
        ];

        foreach ($slugs as $slug) {
            $exists = DB::table('permissions')->where('slug', $slug)->exists();
            $this->assertTrue($exists, "Permission '{$slug}' should exist in the database");
        }
    }

    #[Test]
    public function report_category_exists(): void
    {
        $category = DB::table('permission_categories')
            ->where('slug', 'informes')
            ->first();

        $this->assertNotNull($category, 'Category "informes" should exist');
        $this->assertEquals('Informes', $category->name);
    }

    #[Test]
    public function all_six_report_permissions_belong_to_report_category(): void
    {
        $category = DB::table('permission_categories')
            ->where('slug', 'informes')
            ->first();

        $this->assertNotNull($category);

        $slugs = [
            'report.view',
            'report.create',
            'report.edit',
            'report.sign',
            'report.close',
            'report.download-pdf',
        ];

        $permissions = DB::table('permissions')
            ->whereIn('slug', $slugs)
            ->get();

        $this->assertCount(6, $permissions);

        foreach ($permissions as $permission) {
            $this->assertEquals(
                $category->id,
                $permission->category_id,
                "Permission '{$permission->slug}' should belong to category 'informes'"
            );
        }
    }

    #[Test]
    public function admin_role_has_all_report_permissions_with_grant(): void
    {
        $adminRole = DB::table('roles')->where('slug', 'admin')->first();
        $this->assertNotNull($adminRole, 'Admin role should exist');

        $slugs = [
            'report.view',
            'report.create',
            'report.edit',
            'report.sign',
            'report.close',
            'report.download-pdf',
        ];

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', $slugs)
            ->pluck('id')
            ->toArray();

        $this->assertCount(6, $permissionIds, 'All 6 report permissions should exist');

        foreach ($permissionIds as $permissionId) {
            $rolePerm = DB::table('role_permissions')
                ->where('role_id', $adminRole->id)
                ->where('permission_id', $permissionId)
                ->first();

            $this->assertNotNull(
                $rolePerm,
                "Admin role should have permission_id={$permissionId}"
            );

            $this->assertEquals(
                1,
                $rolePerm->grant,
                "Permission grant should be 1 for permission_id={$permissionId}"
            );
        }
    }
}
