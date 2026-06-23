<?php

namespace App\Http\Actions\Admin\User;

use App\Commands\Admin\User\GetUserCommand;
use Illuminate\Http\Request;

class GetUserAction
{
    private GetUserCommand $command;

    public function __construct(GetUserCommand $command)
    {
        $this->command = $command;
    }

    public function execute(int $id)
    {
        $user = $this->command->execute($id);
        if (! $user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        return response()->json($user);
    }

    public function __invoke(Request $request, $id)
    {
        return $this->execute((int) $id);
    }
}
