# database-schema Specification

## Purpose

Update `docs/tecnica/estructura-base-datos.md` from 19 tables to 22 tables, adding `patient_reports`, `report_templates`, and `llm_interactions`.

## Requirements

### Requirement: patient_reports Table Documented

The system SHALL document `patient_reports` table with columns: `id`, `patient_id` (FK→patients), `template_id` (FK→report_templates), `content` (json), `status` (enum: draft/signed/closed), `transcript_text` (nullable), `signed_by` (FK→users, nullable), `signed_at`, `closed_at`, timestamps, soft delete.

#### Scenario: Table schema complete

- GIVEN the migration creates `patient_reports`
- WHEN the DB doc is updated
- THEN the table SHALL be listed with all columns, types, FKs, and cascade rules
- AND the PatientReport model reference SHALL be included

### Requirement: report_templates Table Documented

The system SHALL document `report_templates` table with columns: `id`, `name`, `structure` (json — sections→rows→columns), `is_active`, `organization_id` (nullable), timestamps, soft delete.

#### Scenario: Template structure explained

- GIVEN the `structure` column stores nested JSON
- WHEN the template table is documented
- THEN the JSON shape SHALL be explained: sections→rows→columns, each column has `field`, `label`, `type`, `ai_help_description`
- AND the ReportTemplate model reference SHALL be included

### Requirement: llm_interactions Table Documented

The system SHALL document `llm_interactions` table with columns: `id`, `patient_report_id` (FK→patient_reports), `request_payload` (json), `response_payload` (json, nullable), `processing_time_ms`, timestamps.

#### Scenario: Interaction table documented

- GIVEN the migration creates `llm_interactions`
- WHEN the DB doc is updated
- THEN the table SHALL show FK to `patient_reports` with CASCADE delete
- AND the LlmInteraction model reference SHALL be included

### Requirement: Summary Updated

The system SHALL update the header from "19 tablas en total" to "22 tablas en total" and renumber tables 10-19 as 13-22.

#### Scenario: Count and numbering correct

- GIVEN 3 new tables are added
- WHEN the doc is finalized
- THEN "22 tablas" SHALL be stated
- AND existing table numbers SHALL shift: addresses from 10→13, patients from 11→14, etc.
