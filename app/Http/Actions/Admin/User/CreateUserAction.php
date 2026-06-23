<?php

namespace App\Http\Actions\Admin\User;

use App\Commands\Admin\User\CreateUserCommand;
use App\Http\Requests\Admin\CreateUserRequest;
use Illuminate\Http\Request;

class CreateUserAction
{
    private CreateUserCommand $command;

    public function __construct(CreateUserCommand $command)
    {
        $this->command = $command;
    }

    public function execute(array $data)
    {
        $user = $this->command->execute($data);
        return response()->json($user->toArray(), 201);
    }

    public function __invoke(CreateUserRequest $request)
    {
        return $this->execute($request->validated());
    }
}
