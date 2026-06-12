<?php

namespace App\Repositories\Report;

use App\Models\PatientReport;
use App\Models\ReportTemplate;
use App\Enums\ReportStatus;

class PatientReportSaveRepository
{
    public function iniciar(array $data): PatientReport
    {
        $template = ReportTemplate::findOrFail($data['template_id']);

        return PatientReport::create([
            'patient_id' => $data['patient_id'],
            'user_id' => auth()->id(),
            'template_id' => $template->id,
            'status' => ReportStatus::Draft,
            'template_structure_snapshot' => $template->structure,
            'values' => [],
        ]);
    }

    public function actualizarValores(int $id, array $values): PatientReport
    {
        $report = PatientReport::findOrFail($id);
        $report->values = $values;
        $report->save();

        return $report->fresh(['patient', 'user', 'template']);
    }

    public function firmar(int $id, string $signaturePath): PatientReport
    {
        $report = PatientReport::findOrFail($id);
        $report->status = ReportStatus::Signed;
        $report->signature_path = $signaturePath;
        $report->signed_at = now();
        $report->save();

        return $report->fresh(['patient', 'user', 'template']);
    }

    public function cerrar(int $id, string $pdfPath): PatientReport
    {
        $report = PatientReport::findOrFail($id);
        $report->status = ReportStatus::Closed;
        $report->pdf_path = $pdfPath;
        $report->closed_at = now();
        $report->save();

        return $report->fresh(['patient', 'user', 'template']);
    }
}
