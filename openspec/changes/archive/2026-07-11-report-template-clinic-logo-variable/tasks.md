# Tasks: Add `{clinica.logo}` Variable Above Clinic Name in Report Templates Seeder

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~45 (3 × ~15-line field blocks) |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Not needed — single PR |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

## Phase 1: Seeder Insertions

- [x] 1.1 Insert `{clinica.logo}` `fixed_text` field before `uuid-hcg-field-header-clinica-nombre` in `hcgTemplate()` header col-2a (~line 88)
- [x] 1.2 Insert `{clinica.logo}` `fixed_text` field before `uuid-ia-field-header-clinica-nombre` in `iaTemplate()` header col-2a (~line 735)
- [x] 1.3 Insert `{clinica.logo}` `fixed_text` field before `uuid-ci-field-header-clinica-nombre` in `ciTemplate()` header col-2a (~line 1227)

## Phase 2: Verification

- [x] 2.1 Run seeder: `docker compose exec app php artisan db:seed --class=ReportTemplatesSeeder`
- [x] 2.2 Verify no PHP/syntax/JSON errors from seeder output
- [x] 2.3 Verify `{clinica.logo}` field appears first in each template's clinic info column fields array

### Field Template (repeated 3×)

```php
[
    'id' => 'uuid-{PREFIX}-field-header-clinica-logo',
    'key' => '{prefix}_header_clinica_logo',
    'type' => 'fixed_text',
    'label' => 'Logo de la clínica',
    'required' => false,
    'showLabel' => false,
    'text_content' => '{clinica.logo}',
],
```

`{PREFIX}` / `{prefix}`: `hcg`, `ia`, `ci` per template.

### Files Affected

| File | Change |
|------|--------|
| `database/seeders/ReportTemplatesSeeder.php` | 3 × ~15-line insertions |

### Note on TDD

Strict TDD mode is enabled but does not apply here — this is a data-only seeder change. No logic, no new classes, no behavior change. Verification is seeder re-run.
