<?php

namespace App\Repositories;

use App\Models\HealthStatus;
use Illuminate\Database\Connection;

class HealthRepository
{
    private ?Connection $db;

    public function __construct(?Connection $db = null)
    {
        $this->db = $db;
    }

    public function getStatus(): HealthStatus
    {
        // En un caso real el repositorio consultaría dependencias externas a través de $this->db u otros clientes.
        return new HealthStatus('ok');
    }
}
