<?php

namespace App\Http\Actions\Patients;

use App\Commands\Admin\Patient\CreatePatientCommand;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CreatePatientAction
{
    public function __construct(private CreatePatientCommand $command) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'medical_record_number' => 'nullable|string|max:255',
            'national_id' => 'nullable|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'second_last_name' => 'nullable|string|max:255',
            'gender' => 'nullable|string|max:10',
            'date_of_birth' => 'nullable|date',
            'city' => 'nullable|string|max:255',
            'insurance_id' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'last_visit_at' => 'nullable|date',
            // Contact
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            // Address
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'neighborhood' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
        ]);

        try {
            $patient = $this->command->execute($validated);
        } catch (\App\Exceptions\PermissionDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[CreatePatientAction] ' . $e->getMessage());
            return response()->json(['message' => 'Internal server error'], 500);
        }

        return response()->json($patient, 201);
    }
}
