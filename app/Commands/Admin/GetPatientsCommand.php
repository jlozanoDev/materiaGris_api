<?php

namespace App\Commands\Admin;

use App\Repositories\Patient\PatientReadRepository;
use Illuminate\Http\Request;

class GetPatientsCommand
{
    private PatientReadRepository $leer;

    public function __construct(PatientReadRepository $leer)
    {
        $this->leer = $leer;
    }

    public function execute(Request $request)
    {
        $filters = $request->query();
        return $this->leer->buscarPorFiltros($filters);
    }
}
