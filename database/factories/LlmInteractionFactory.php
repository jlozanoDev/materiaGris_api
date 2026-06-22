<?php

namespace Database\Factories;

use App\Models\LlmInteraction;
use App\Models\PatientReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LlmInteraction>
 */
class LlmInteractionFactory extends Factory
{
    protected $model = LlmInteraction::class;

    public function definition(): array
    {
        return [
            "patient_report_id" => PatientReport::factory(),
            "request_payload" => [
                "model" => "gpt-4o",
                "messages" => [
                    ["role" => "system", "content" => "Extract clinical data."],
                    ["role" => "user", "content" => "Patient presents with cough."],
                ],
            ],
            "response_payload" => null,
            "processing_time_ms" => null,
        ];
    }
}
