<?php

namespace App\Commands\Reports;

use App\Repositories\Report\PatientReportSaveRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;
use App\Models\PatientReport;
use App\Enums\ReportStatus;
use Illuminate\Support\Facades\Log;
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

        if (preg_match('#^data:image/\w+;base64,#', $data['signature'])) {
            $data['signature'] = substr($data['signature'], strpos($data['signature'], ',') + 1);
        }

        $signaturePath = $this->storeSignature($data['signature'], $report->id);

        if ($signaturePath === null) {
            throw new \RuntimeException('No se pudo almacenar la firma');
        }

        return $this->repo->firmar($id, $signaturePath);
    }

    private function storeSignature(string $base64, int $reportId): ?string
    {
        $decoded = base64_decode($base64, true);
        if ($decoded === false) {
            throw new \RuntimeException('La firma no tiene un formato base64 válido');
        }

        $dir = 'signatures';
        $disk = Storage::disk('local');

        try {
            if (! $disk->directoryExists($dir)) {
                $disk->makeDirectory($dir);
            }
        } catch (\Throwable $e) {
            Log::error('SignReportCommand cannot create signatures directory', [
                'report_id' => $reportId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        $filename = $dir . '/report_' . $reportId . '_' . time() . '.png';
        $written = $disk->put($filename, $decoded);

        if ($written === false) {
            Log::error('SignReportCommand failed to write signature file', [
                'report_id' => $reportId,
                'filename' => $filename,
            ]);
            return null;
        }

        return $filename;
    }
}
