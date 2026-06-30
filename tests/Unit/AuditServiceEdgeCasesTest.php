<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\AuditService;
use App\Models\User;

class AuditServiceEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_with_numeric_actor_id(): void
    {
        $service = app(AuditService::class);
        $target = User::factory()->create();

        $audit = $service->record('test.type', 42, $target, [], ['module' => 'tests']);

        $this->assertDatabaseHas('audits', [
            'type' => 'test.type',
            'actor_id' => 42,
            'actor_type' => 'User',
        ]);
    }

    public function test_record_with_array_target_containing_user_id(): void
    {
        $service = app(AuditService::class);
        $actor = User::factory()->create();

        $target = ['user_id' => 99, 'other' => 'data'];

        $audit = $service->record('test.type', $actor, $target, [], ['module' => 'tests']);

        $this->assertDatabaseHas('audits', [
            'type' => 'test.type',
            'user_id' => 99,
        ]);
    }
}
