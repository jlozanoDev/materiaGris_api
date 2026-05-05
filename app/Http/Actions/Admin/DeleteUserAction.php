<?php

namespace App\Http\Actions\Admin;

use App\Commands\Admin\DeleteUserCommand;
use Illuminate\Http\Request;

class DeleteUserAction
{
    private DeleteUserCommand $command;

    public function __construct(DeleteUserCommand $command)
    {
        $this->command = $command;
    }

    public function execute(int $id)
    {
        $ok = $this->command->execute($id);
        if (! $ok) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }
        return response()->json(null, 204);
    }

    public function __invoke(Request $request, $id)
    {
        return $this->execute((int) $id);
    }
}
