<?php

namespace App\Http\Actions\Admin;

use App\Commands\Admin\CreateRoleCommand;
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
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }
}
