<?php

namespace App\Http\Actions\Auth;

use App\Commands\Auth\MeCommand;
use Illuminate\Http\Request;

class MeAction
{
    private MeCommand $command;

    public function __construct(MeCommand $command)
    {
        $this->command = $command;
    }

    public function execute(Request $request)
    {
        // Delegar la lógica de negocio (selección de user id desde request/session)
        // al comando para mantener la action delgada.
        $user = $request->user();
        if (! $user) {
            return null;
        }

        return $this->command->execute($user->id);
    }

    public function __invoke(Request $request)
    {
        $result = $this->execute($request);
        return response()->json($result);
    }
}
