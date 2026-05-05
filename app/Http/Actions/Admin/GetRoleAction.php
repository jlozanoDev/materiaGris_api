<?php

namespace App\Http\Actions\Admin;

use App\Commands\Admin\GetRoleCommand;
use Illuminate\Http\JsonResponse;

class GetRoleAction
{
    private GetRoleCommand $command;

    public function __construct(GetRoleCommand $command)
    {
        $this->command = $command;
    }

    public function __invoke(int $id): JsonResponse
    {
        try {
            $result = $this->command->execute($id);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }
}
