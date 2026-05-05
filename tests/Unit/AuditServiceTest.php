<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\AuditService;
use App\Models\User;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_creates_audit(): void
    {
        $service = app(AuditService::class);

        $actor = User::factory()->create();
        $target = User::factory()->create();

        $payload = ['foo' => 'bar'];
        $meta = ['module' => 'tests'];

        $audit = $service->record('test.type', $actor, $target, $payload, $meta);

        $this->assertDatabaseHas('audits', [
            'type' => 'test.type',
            'module' => 'tests',
        ]);

        $this->assertEquals($payload, $audit->payload);
    }
}
