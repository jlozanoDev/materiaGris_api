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
        } catch (\App\Exceptions\PermissionDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[GetRoleAction] ' . $e->getMessage());
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
