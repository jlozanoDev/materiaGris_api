<?php

namespace App\Http\Actions\Admin\Role;

use App\Commands\Admin\Role\CreateRoleCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreateRoleAction
{
    private CreateRoleCommand $command;

    public function __construct(CreateRoleCommand $command)
    {
        $this->command = $command;
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'permissions' => 'nullable|array',
                'permissions.*.id' => 'required|integer|exists:permissions,id',
                'permissions.*.grant' => 'required|integer|in:1,-1,0'
            ]);

            $result = $this->command->execute($data);
            return response()->json($result, 201);
        } catch (\App\Exceptions\PermissionDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[CreateRoleAction] ' . $e->getMessage());
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
