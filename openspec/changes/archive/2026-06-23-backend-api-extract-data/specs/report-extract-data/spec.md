# report-extract-data Specification

## Requirements

### Requirement: Authorization

The system MUST require JWT auth and `report.edit` permission. The user MUST own or have organizational access to the report.

#### Scenario: Authorized

- GIVEN valid JWT and `report.edit`
- THEN request proceeds

#### Scenario: No JWT → 401

#### Scenario: No `report.edit` → 403

### Requirement: Input Validation

The system MUST validate `transcript` (non-empty string) and `template_id` (existing, active, org-scoped). The report `{id}` MUST exist.

#### Scenario: Valid input — passes

#### Scenario: Empty transcript → 422

#### Scenario: Invalid template_id → 400

#### Scenario: Report not found → 404

### Requirement: LLM Structured Extraction

The system MUST build a prompt using template fields (`field`, `label`, `type`, `ai_help_description`), sanitized transcript, and patient context. The LLM MUST return JSON with keys matching template `field` values.

#### Scenario: Successful extraction

- GIVEN valid transcript and template
- WHEN LLM processes request
- THEN response includes `extracted_data` (keys=matching template fields), `confidence_scores` (0.0-1.0 per field), `warnings` (string[]), `processing_time_ms` (int)

#### Scenario: Missing field

- GIVEN template field with no matching transcript data
- WHEN LLM processes
- THEN field included with value `null`

#### Scenario: Nested template flattening

- GIVEN template with sections→rows→columns hierarchy
- WHEN building prompt
- THEN all columns flattened using `field` as key and `ai_help_description` as description (fallback: `label`)

### Requirement: LLM Error Handling

The system MUST timeout at 30s, retry once on malformed JSON, and return correct HTTP codes.

#### Scenario: LLM timeout → 500

#### Scenario: Malformed JSON → retry once; if valid → normal response

#### Scenario: Malformed JSON after retry → 500

#### Scenario: LLM service unavailable → 503

### Requirement: Prompt Injection Protection

The system MUST separate system/user messages, sanitize transcripts (strip code fences/delimiters), and enforce structured output. User-controlled prompt templates SHALL NOT be accepted.

#### Scenario: Code fence sanitization

- GIVEN transcript with markdown delimiters
- WHEN sanitized
- THEN delimiters removed before LLM send

#### Scenario: Message layer isolation

- GIVEN any transcript
- WHEN building prompt
- THEN transcript placed in user message only; system prompt fixed

### Requirement: LlmInteraction Persistence

The system MUST persist every LLM call to `llm_interactions` (request_payload, response_payload, processing_time_ms, timestamps) — on success AND failure.

#### Scenario: Success persistence

- GIVEN successful extraction
- THEN `LlmInteraction` saved with full payloads

#### Scenario: Failure persistence

- GIVEN failed/timed-out LLM call
- THEN `LlmInteraction` saved with request payload and error in response_payload

### Requirement: Patient Context Enrichment

The system MUST include patient `age` (from `date_of_birth` accessor), `gender`, and last 10 PatientReport values of the same patient in the LLM prompt — WITHOUT names, IDs, or PII.

#### Scenario: Patient demographics

- GIVEN report with associated patient
- WHEN building prompt
- THEN age and gender included; name/ID/MRN excluded

#### Scenario: Historical reports

- GIVEN patient with prior reports
- WHEN building prompt
- THEN last 10 report values included without identifiers

### Requirement: Extra Fields Discarding

The system MUST discard LLM output fields not present in the template.

#### Scenario: Unexpected field

- GIVEN LLM returns field absent from template
- THEN field removed from `extracted_data` and `confidence_scores`

### Requirement: PII/PHI Safety

The system MUST NOT log transcript content or extracted data. Logs SHALL contain only `template_id`, `processing_time_ms`, and transcript character count.

#### Scenario: Log safety

- GIVEN extraction with clinical content
- THEN logs contain only metadata; NO transcript text or extracted values

#### Scenario: LlmInteraction safety

- GIVEN request/response payloads stored in `llm_interactions`
- THEN the design SHOULD support future PII masking without altering stored payloads
