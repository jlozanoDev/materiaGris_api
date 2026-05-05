<?php

namespace App\Http\Actions\Admin;

use App\Commands\Admin\DeleteRoleCommand;
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
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }
}
