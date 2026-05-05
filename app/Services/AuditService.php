<?php

namespace App\Services;

use App\Models\Audit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Record an audit entry.
     *
     * @param string $type
     * @param mixed $actor (User model or id)
     * @param mixed $target (model or id)
     * @param array $payload
     * @param array $meta
     * @return Audit
     */
    public function record(string $type, $actor = null, $target = null, array $payload = [], array $meta = [])
    {
        $actorId = null;
        $actorType = null;
        if (is_object($actor) && method_exists($actor, 'getKey')) {
            $actorId = $actor->getKey();
            $actorType = get_class($actor);
        } elseif (is_numeric($actor)) {
            $actorId = (int) $actor;
            $actorType = 'User';
        }

        $userId = null;
        if (is_object($target) && method_exists($target, 'getKey')) {
            $userId = $target->getKey();
        } elseif (is_array($target) && isset($target['user_id'])) {
            $userId = $target['user_id'];
        }

        $audit = Audit::create([
            'type' => $type,
            'module' => $meta['module'] ?? null,
            'actor_id' => $actorId,
            'actor_type' => $actorType ?? 'User',
            'user_id' => $userId,
            'target_type' => is_object($target) ? get_class($target) : ($meta['target_type'] ?? null),
            'target_id' => is_object($target) && method_exists($target, 'getKey') ? $target->getKey() : ($meta['target_id'] ?? null),
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
            'payload' => $payload ?: null,
            'meta' => $meta ?: null,
            'trace_id' => $meta['trace_id'] ?? null,
            'created_at' => now(),
        ]);

        // emit event if needed
        event('audit.logged', $audit);

        return $audit;
    }
}
