# transcribe-specs Specification

## Purpose

Consolidate `docs/tecnica/backend-transcribe.md` and `docs/tecnica/backend-api-transcribe.md` into a single unified file, and document `SpeakerClassifierService`.

## Requirements

### Requirement: Duplicate Files Merged

The system SHALL merge the two transcribe backend specs into one file in `docs/tecnica/modules/reports/`, removing the two standalone files from `docs/tecnica/`.

#### Scenario: Single unified file exists

- GIVEN two files: `backend-transcribe.md` (168 lines, frontend-oriented) and `backend-api-transcribe.md` (222 lines, backend-oriented)
- WHEN merged into one file
- THEN a single file SHALL exist in `docs/tecnica/modules/reports/modulo-dictado-autocompletado.md`
- AND both standalone files SHALL be removed from `docs/tecnica/`

#### Scenario: Content from both sources preserved

- GIVEN `backend-transcribe.md` has diarization rules, audio MIME types, frontend integration notes
- AND `backend-api-transcribe.md` has endpoint spec, STT config, implementation architecture
- WHEN the unified doc is written
- THEN it SHALL contain: endpoint specification, request/response contracts, error codes, diarization rules, audio format support, STT service config, implementation architecture, and frontend integration notes
- AND no technical content from either source SHALL be lost

### Requirement: SpeakerClassifierService Documented

The system SHALL document `SpeakerClassifierService` in the unified transcribe module, describing its role classification logic (speaker role detection: médico/paciente based on NLP analysis of segments).

#### Scenario: Speaker classifier service section

- GIVEN `SpeakerClassifierService.php` exists in `app/Services/`
- WHEN the transcribe module doc is updated
- THEN it SHALL include a "Speaker Classifier" subsection describing: purpose (classify segments by speaker role), input (segments array), output (segments with `role` field), and integration point in the transcribe flow

### Requirement: Cross-references Updated

The system SHALL update all documentation cross-references that pointed to the old standalone files.

#### Scenario: No broken links

- GIVEN `docs/tecnica/INDICE.md` and other docs may reference `backend-transcribe.md` or `backend-api-transcribe.md`
- WHEN the merge is complete
- THEN all references SHALL point to the new unified file path
- AND no broken cross-links SHALL remain
