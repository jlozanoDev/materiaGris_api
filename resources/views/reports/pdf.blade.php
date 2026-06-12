<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe #{{ $report->id }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; margin: 40px; }
        h1 { font-size: 18px; margin-bottom: 5px; }
        .meta { color: #666; margin-bottom: 20px; font-size: 10px; }
        .section { margin-bottom: 20px; border: 1px solid #ddd; padding: 15px; }
        .section h2 { font-size: 14px; margin: 0 0 10px 0; padding-bottom: 5px; border-bottom: 1px solid #eee; }
        .field { margin-bottom: 8px; }
        .field-label { font-weight: bold; font-size: 10px; color: #555; }
        .field-value { font-size: 12px; margin-top: 2px; padding: 4px 0; border-bottom: 1px solid #f0f0f0; }
        .signature-box { margin-top: 40px; }
        .signature-box img { max-width: 200px; max-height: 80px; }
        .status-badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 10px; font-weight: bold; }
        .status-signed { background: #d4edda; color: #155724; }
        .status-closed { background: #cce5ff; color: #004085; }
        .status-draft { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <h1>Informe #{{ $report->id }}</h1>
    <div class="meta">
        <strong>Plantilla:</strong> {{ $report->template->name ?? 'N/A' }}<br>
        <strong>Paciente:</strong> {{ $report->patient->name ?? 'N/A' }} {{ $report->patient->lastname ?? '' }}<br>
        <strong>Autor:</strong> {{ $report->user->name ?? 'N/A' }} {{ $report->user->lastname ?? '' }}<br>
        <strong>Estado:</strong>
        <span class="status-badge status-{{ $report->status->value }}">
            {{ match($report->status->value) { 'draft' => 'Borrador', 'signed' => 'Firmado', 'closed' => 'Cerrado', default => $report->status->value } }}
        </span><br>
        <strong>Fecha:</strong> {{ $report->created_at->format('d/m/Y H:i') }}
        @if($report->signed_at)
            <br><strong>Firmado:</strong> {{ $report->signed_at->format('d/m/Y H:i') }}
        @endif
        @if($report->closed_at)
            <br><strong>Cerrado:</strong> {{ $report->closed_at->format('d/m/Y H:i') }}
        @endif
    </div>

    @php
        $structure = is_array($report->template_structure_snapshot) ? $report->template_structure_snapshot : [];
        $sections = $structure['sections'] ?? [];
        $values = is_array($report->values) ? $report->values : [];
    @endphp

    @foreach($sections as $section)
        <div class="section">
            <h2>{{ $section['title'] ?? 'Sección' }}</h2>
            @php $rows = $section['rows'] ?? []; @endphp
            @foreach($rows as $row)
                @php $columns = $row['columns'] ?? []; @endphp
                <table width="100%" style="margin-bottom: 5px;">
                    <tr>
                        @foreach($columns as $column)
                            <td width="{{ 100 / max(count($columns), 1) }}%" style="vertical-align: top; padding: 5px;">
                                <div class="field">
                                    <div class="field-label">{{ $column['label'] ?? $column['field'] ?? 'Campo' }}</div>
                                    <div class="field-value">
                                        @php
                                            $key = $column['field'] ?? null;
                                            $val = $key ? ($values[$key] ?? '—') : '—';
                                        @endphp
                                        @if(is_array($val))
                                            {{ json_encode($val, JSON_UNESCAPED_UNICODE) }}
                                        @else
                                            {{ $val }}
                                        @endif
                                    </div>
                                </div>
                            </td>
                        @endforeach
                    </tr>
                </table>
            @endforeach
        </div>
    @endforeach

    @if($report->signature_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($report->signature_path))
        <div class="signature-box">
            <strong>Firma:</strong><br>
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('local')->path($report->signature_path) }}" alt="Firma">
        </div>
    @endif
</body>
</html>
