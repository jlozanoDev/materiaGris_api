<?php

namespace App\Http\Actions\Admin\Clinic;

use App\Commands\Admin\Clinic\UploadClinicLogoCommand;
use App\Exceptions\PermissionDeniedException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UploadClinicLogoAction
{
    public function __construct(
        private UploadClinicLogoCommand $command,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'logo' => 'required|file|mimetypes:image/png,image/jpeg,image/svg+xml,image/webp|max:5120',
            ]);

            $clinic = $this->command->execute($data['logo']);
            return response()->json($clinic);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (PermissionDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Clinic not found'], 404);
        } catch (\Exception $e) {
            Log::error('UploadClinicLogoAction error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
