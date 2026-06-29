<?php

namespace App\Http\Actions\Reports;

use App\Commands\Reports\TranscribeReportCommand;
use App\Exceptions\AiResponseException;
use App\Exceptions\AiTimeoutException;
use App\Exceptions\AiUnavailableException;
use App\Exceptions\PermissionDeniedException;
use App\Http\Requests\Reports\TranscribeReportRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class TranscribeReportAction
{
    public function __invoke(TranscribeReportRequest $request, int $id): JsonResponse
    {
        try {
            $result = app(TranscribeReportCommand::class)->execute(
                reportId: $id,
                audio: $request->file('audio'),
                diarization: $request->boolean('diarization', true),
                language: $request->input('language'),
                user: auth()->user(),
            );
        } catch (AiTimeoutException | AiResponseException $e) {
            return response()->json(['message' => 'Error al procesar el audio'], 500);
        } catch (AiUnavailableException $e) {
            return response()->json(['message' => 'Servicio de transcripción temporalmente no disponible'], 503);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Informe no encontrado'], 404);
        } catch (PermissionDeniedException $e) {
            return response()->json(['message' => 'No tienes permisos'], 403);
        }
        
        return response()->json([
            'data' => [
                'transcript' => $result->transcript,
                'segments' => $result->segments,
                'language' => $result->language,
                'duration_seconds' => $result->durationSeconds,
            ],
            'meta' => [],
            'message' => 'Transcripción completada',
        ]);
    }
}
