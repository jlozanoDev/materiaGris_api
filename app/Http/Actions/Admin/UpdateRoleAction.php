<?php

namespace App\Http\Actions\Admin;

use App\Commands\Admin\UpdateRoleCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateRoleAction
{
    private UpdateRoleCommand $command;

    public function __construct(UpdateRoleCommand $command)
    {
        $this->command = $command;
    }

    public function __invoke(Request $request, int $id): JsonResponse
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'permissions' => 'nullable|array',
                'permissions.*.id' => 'required|integer|exists:permissions,id',
                'permissions.*.grant' => 'required|integer|in:1,-1,0'
            ]);

            $result = $this->command->execute($id, $data);
            return response()->json($result);
        } catch (\App\Exceptions\PermissionDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[UpdateRoleAction] ' . $e->getMessage());
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
