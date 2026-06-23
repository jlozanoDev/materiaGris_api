<?php

namespace App\Http\Actions\Admin\Role;

use App\Commands\Admin\Role\DeleteRoleCommand;
use Illuminate\Http\JsonResponse;

class DeleteRoleAction
{
    private DeleteRoleCommand $command;

    public function __construct(DeleteRoleCommand $command)
    {
        $this->command = $command;
    }

    public function __invoke(int $id): JsonResponse
    {
        try {
            $this->command->execute($id);
            return response()->json(null, 204);
        } catch (\App\Exceptions\PermissionDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[DeleteRoleAction] ' . $e->getMessage());
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
