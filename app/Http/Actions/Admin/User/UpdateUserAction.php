<?php

namespace App\Http\Actions\Admin\User;

use App\Commands\Admin\User\UpdateUserCommand;
use App\Http\Requests\Admin\UpdateUserRequest;
use Illuminate\Http\Request;

class UpdateUserAction
{
    private UpdateUserCommand $command;

    public function __construct(UpdateUserCommand $command)
    {
        $this->command = $command;
    }

    public function execute(int $id, array $data)
    {
        $user = $this->command->execute($id, $data);
        if (! $user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }
        return response()->json($user);
    }

    public function __invoke(UpdateUserRequest $request, $id)
    {
        return $this->execute((int) $id, $request->validated());
    }
}
