<?php

namespace App\Commands\Reports;

use App\Repositories\Report\PatientReportSaveRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;
use App\Models\PatientReport;
use App\Enums\ReportStatus;
use Illuminate\Support\Facades\Storage;

class SignReportCommand
{
    public function __construct(
        private PatientReportSaveRepository $repo,
        private PermissionService $permissionService,
    ) {}

    public function execute(int $id, array $data): PatientReport
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($user, 'report.sign');

        $report = PatientReport::findOrFail($id);

        if ($report->status !== ReportStatus::Draft) {
            throw new \RuntimeException('Solo se pueden firmar informes en estado borrador');
        }

        if ($report->user_id !== $user->id) {
            throw new PermissionDeniedException('Solo el autor puede firmar este informe');
        }

        $signaturePath = $this->storeSignature($data['signature'], $report->id);

        return $this->repo->firmar($id, $signaturePath);
    }

    private function storeSignature(string $base64, int $reportId): string
    {
        $decoded = base64_decode($base64, true);
        if ($decoded === false) {
            // Try stripping data URI prefix
            if (preg_match('#^data:image/\w+;base64,#', $base64)) {
                $base64 = substr($base64, strpos($base64, ',') + 1);
                $decoded = base64_decode($base64, true);
            }
        }

        if ($decoded === false) {
            throw new \RuntimeException('La firma no tiene un formato base64 válido');
        }

        $filename = 'signatures/report_' . $reportId . '_' . time() . '.png';
        Storage::disk('local')->put($filename, $decoded);

        return $filename;
    }
}
