# BPS Kabupaten Jember Integration Plan for NP Analytics

## 1. Purpose and scope

This plan integrates official BPS Kabupaten Jember data into NP Analytics while preserving the existing offline-first Laravel 11 + Livewire/Blade + SQLite + NativePHP architecture and the backend admin pull/push sync model.

Primary scope:

- Keep BPS API tokens only in backend storage.
- Add a normalized multi-period observation model without deleting existing indicator rows.
- Implement a paginated BPS catalog scanner with run status, counts, errors, and raw response versioning.
- Map priority domains first: PDRB/growth, poverty, inflation/CPI, IPM, employment.
- Then PODES, agriculture, education/health, tourism, and full catalog metadata/history.
- Preserve sync compatibility for mobile clients.

Non-goals for phase 1:

- No direct BPS token in the mobile app.
- No replacement of existing indicators rows.
- No destructive migration.

## 2. Verified codebase baseline

Relevant existing assets:

- app/Models/Category.php, Indicator.php, Phenomenon.php
- app/Http/Controllers/Api/SyncController.php
- app/Http/Middleware/EnsureSyncKey.php
- app/Services/SyncService.php
- config/sync.php
- database/migrations/2026_02_07_000002_create_indicators_table.php
- database/migrations/2026_10_22_000003_add_provenance_metadata.php
- tests/Feature/MasterStrategicDataJemberTest.php
- tests/Feature/SyncKeyTest.php

Current security behavior:

- EnsureSyncKey allows empty key only in local.
- Outside local, an empty key returns HTTP 503.
- Provided keys are compared with hash_equals.
- Mobile SyncService refuses an empty SYNC_API_KEY.

This already satisfies the reject empty production sync key requirement. Documentation should state that production must configure SYNC_API_KEY.

## 3. BPS source baseline

Target domain:

- BPS domain code for Kabupaten Jember: 3509.
- BPS WebAPI base: https://webapi.bps.go.id/v1/api/...
- Authentication: user key token passed as key.
- Responses: JSON with status, data-availability, and data arrays containing pagination metadata and rows.

Catalog endpoints to validate before implementation:

1. Domain lookup:
   - GET /v1/api/domain?type=kab&prov=35&key={token}
   - Confirm Jember appears as domain 3509.

2. Subject list:
   - GET /v1/api/list?model=subject&domain=3509&lang=ind&page={n}&key={token}
   - Used to build the subject catalog.

3. Static table list:
   - GET /v1/api/list?model=statictable&domain=3509&subject={id}&page={n}&perpage={n}&key={token}

4. Dynamic data list:
   - GET /v1/api/list?model=data&domain=3509&subject={id}&page={n}&perpage={n}&key={token}

5. Detail endpoints:
   - GET /v1/api/view?model=statictable&domain=3509&id={id}&key={token}
   - GET /v1/api/view?model=data&domain=3509&id={id}&key={token}

6. Supporting metadata:
   - Publication list/detail
   - Press release list/detail
   - Infographic and derived period endpoints as needed.

Implementation must treat endpoint field names as unstable and validate them against live responses before mapping.

## 4. Security and secret handling

Rules:

- Store BPS_API_KEY only in backend .env.
- Never send BPS_API_KEY to mobile clients.
- Mobile continues to use only SYNC_API_KEY for backend sync.
- Backend admin ingestion endpoints require an admin session or a separate admin token.
- Log BPS requests without query tokens.
- Store raw responses with token query strings stripped.
- Reject empty production sync keys, already implemented in EnsureSyncKey.
- Add a backend config validation command that fails in non-local environments when SYNC_API_KEY or BPS_API_KEY is empty.

Recommended backend config:

```php
// config/bps.php
return [
    'base_url' => env('BPS_API_BASE_URL', 'https://webapi.bps.go.id/v1/api'),
    'api_key' => env('BPS_API_KEY'),
    'domain_code' => env('BPS_DOMAIN_CODE', '3509'),
    'timeout' => (int) env('BPS_TIMEOUT', 30),
    'retry_times' => (int) env('BPS_RETRY_TIMES', 3),
    'retry_sleep_ms' => (int) env('BPS_RETRY_SLEEP_MS', 500),
    'per_page' => (int) env('BPS_PER_PAGE', 100),
    'raw_storage_disk' => env('BPS_RAW_STORAGE_DISK', 'local'),
];
```

## 5. Target data model

Add tables without changing existing indicators semantics. Existing rows remain valid legacy series rows.

### 5.1 data_sources

Purpose: register each external source, initially one row for BPS.

Columns:

- id
- uuid
- code unique, e.g. bps-3509
- name
- base_url
- domain_code
- auth_type (api_key)
- secret_reference (config key name, not secret value)
- status (active, disabled, error)
- last_catalog_scan_at
- last_observation_sync_at
- metadata JSON
- timestamps, soft deletes

### 5.2 source_releases

Purpose: version official releases or API snapshots.

Columns:

- id
- data_source_id
- external_release_id nullable
- title
- published_at
- period_start, period_end nullable
- raw_response_hash
- storage_path
- status (draft, published, superseded, failed)
- metadata JSON
- timestamps

### 5.3 ingestion_runs

Purpose: audit every scan/import.

Columns:

- id
- data_source_id
- source_release_id nullable
- run_type (catalog_scan, observation_import, quality_check)
- status (running, completed, failed, partial)
- started_at, finished_at
- records_scanned
- records_created
- records_updated
- records_skipped
- error_count
- error_summary text nullable
- metadata JSON
- timestamps

### 5.4 data_quality_checks

Columns:

- id
- ingestion_run_id
- check_code
- status (passed, warning, failed)
- message
- details JSON
- timestamps

### 5.5 geographies

Columns:

- id
- uuid
- code unique, e.g. 3509 or district code
- name
- level (national, province, regency, district, village)
- parent_code nullable
- metadata JSON
- timestamps, soft deletes

### 5.6 dimensions and dimension_values

Purpose: represent BPS variables such as gender, sector, education level.

dimensions:

- id, uuid, code, name, data_type, metadata, timestamps

dimension_values:

- id, dimension_id, code, label, sort_order, metadata, timestamps

### 5.7 datasets

Purpose: one logical BPS table/subject series group.

Columns:

- id
- uuid
- data_source_id
- external_subject_id nullable
- external_table_id nullable
- title
- description nullable
- frequency (annual, quarterly, monthly, semiannual, irregular)
- unit
- preferred_direction nullable
- region_level
- status (draft, active, deprecated)
- metadata JSON
- timestamps, soft deletes

### 5.8 series

Purpose: one measurable variable within a dataset. This can gradually replace direct use of indicators, but existing indicators remain.

Columns:

- id
- uuid
- dataset_id
- code
- title
- unit
- preferred_direction nullable
- metadata JSON
- timestamps, soft deletes

### 5.9 observations

Purpose: multi-period values.

Columns:

- id
- uuid
- series_id
- geography_id
- source_release_id nullable
- ingestion_run_id nullable
- period_start date
- period_end date
- frequency
- value decimal(18,4) nullable
- unit
- revision string nullable
- quality_status (raw, validated, suspect, rejected)
- raw_payload JSON nullable
- timestamps

Unique constraint:

- series_id + geography_id + period_start + period_end + coalesce(revision, '')

### 5.10 observation_dimension_values

Pivot:

- observation_id
- dimension_value_id
- unique pair

### 5.11 Existing table changes

Add nullable provenance columns to indicators only if needed for compatibility:

- data_source_uuid nullable
- dataset_uuid nullable
- series_uuid nullable
- geography_code nullable
- ingestion_run_id nullable

Do not remove or rename existing columns. Existing tests must continue to pass.

## 6. Migration strategy

Rules:

1. All new tables use new migrations; no edits to old migrations.
2. Existing indicators rows are not transformed in the same migration.
3. Additive columns are nullable.
4. Seed one data_sources row for BPS in a seeder, not in a migration where possible.
5. Backfill existing indicators into datasets/series only in a separate optional command:
   - php artisan bps:backfill-legacy-indicators
6. Keep rollback support for new tables.
7. Test migrations with RefreshDatabase and the existing master seeder.

## 7. Catalog scanner design

Command:

php artisan bps:scan-catalog {--domain=3509} {--subject=} {--page=1} {--per-page=100}

Behavior:

1. Create an ingestion_runs row with status running.
2. Fetch domain metadata and verify domain code.
3. Fetch subject list with pagination.
4. For each subject, fetch static and dynamic table lists with pagination.
5. Persist normalized catalog rows in datasets.
6. Store every raw page under:
   - storage/app/bps/raw/{run_id}/{endpoint_slug}/page-{n}.json
7. Record counts:
   - subjects scanned
   - tables scanned
   - pages fetched
   - rows upserted
   - rows skipped
8. Record errors per page without aborting the whole run when possible.
9. Update data_sources.last_catalog_scan_at.
10. Mark run completed, partial, or failed.
11. Run data quality checks.

Idempotency:

- Upsert by data_source_id + external_subject_id + external_table_id.
- Re-running the same scan must not create duplicate datasets.
- Raw responses are versioned by run ID and content hash.

Pagination consistency checks:

- Compare page, pages, per_page, count, and total.
- Stop when page > pages.
- Detect gaps or duplicate pages.
- Record a quality check if totals change during a run.

## 8. Observation import design

Command:

php artisan bps:import-observations {--dataset=} {--domain=3509} {--from=} {--to=}

Behavior:

1. Resolve dataset and BPS table ID.
2. Fetch detail endpoint with period parameters.
3. Parse dimensions, geography, periods, units, and values.
4. Upsert observations idempotently.
5. Attach dimension values.
6. Create or update source_releases.
7. Run quality checks.

Mapping rules:

- BPS period strings must be converted to period_start and period_end.
- Annual period 2025 becomes 2025-01-01 to 2025-12-31.
- Monthly period 2026-09 becomes 2026-09-01 to 2026-09-30.
- Semiannual period must be mapped explicitly.
- Geography code must be normalized to geographies.code.
- Units must be preserved exactly and compared during quality checks.
- Missing values must be stored as null, not zero.
- Revisions must be preserved; a changed revision creates a new observation or updates according to source release policy.

## 9. Backend admin and sync integration

Backend remains the ingestion owner.

New backend components:

- BpsWebApiClient
- BpsCatalogScanner
- BpsObservationImporter
- BpsQualityChecker
- admin routes protected by admin auth, not sync key:
  - POST /api/admin/bps/scan-catalog
  - POST /api/admin/bps/import-observations
  - GET /api/admin/bps/ingestion-runs
  - GET /api/admin/bps/ingestion-runs/{id}

Mobile sync integration:

Option A, recommended initially:

- Keep mobile indicators as the presentation model.
- Backend exports selected BPS-derived indicators through the existing sync payload.
- Mobile remains unchanged except for new optional fields.

Option B, later:

- Extend sync payload with datasets, series, observations, and geographies.
- Update mobile SyncService and models accordingly.

For phase 1, use Option A to reduce risk. For phase 2, implement Option B with backward-compatible payload additions.

## 10. Quality checks

Minimum checks:

1. Domain code exists and name contains Jember.
2. Subject list pagination totals are internally consistent.
3. No duplicate dataset external IDs.
4. Every observation has a series, geography, period, and unit.
5. No duplicate observation unique keys.
6. Missing values are explicitly null.
7. Unit consistency per series.
8. Period ordering is valid.
9. Imported counts match parsed row counts.
10. Stale status detection:
    - A dataset with no successful import in the configured threshold is marked stale.
11. Token exposure check:
    - Raw stored responses and logs must not contain key= token values.

## 11. Verification tests

Backend tests:

- BpsWebApiClientTest
  - builds URL with domain and key
  - paginates until page > pages
  - retries on 5xx
  - never logs token

- BpsCatalogScannerTest
  - creates one run
  - stores raw pages
  - upserts datasets idempotently
  - records counts and status

- ObservationImportTest
  - maps annual/monthly/semiannual periods
  - preserves null values
  - prevents duplicate observations

- DataQualityCheckTest
  - detects pagination mismatch
  - detects duplicate keys
  - detects token leakage

Mobile/backend sync tests:

- Existing SyncKeyTest remains green.
- Empty production sync key returns 503.
- hash_equals comparison remains in place.
- Sync pull/push still works with existing fixtures.

Migration tests:

- RefreshDatabase migration succeeds.
- Existing seeder tests remain green.
- Legacy indicators remain count-stable after new migrations.

## 12. Phased roadmap

Phase 0, security and config:

- Add backend config/bps.php.
- Add environment variables.
- Add config validation command.
- Update docs.

Phase 1, schema:

- Add migrations for all new tables.
- Add Eloquent models and factories.
- Add seeders for data_sources and base geographies.
- Run existing tests.

Phase 2, catalog scanner:

- Implement client and scanner.
- Add raw response storage.
- Add pagination and quality checks.
- Add tests with mocked HTTP.

Phase 3, observation import:

- Implement parser and importer.
- Add period/geography/unit mapping.
- Add idempotency and quality checks.
- Add tests.

Phase 4, priority data:

- Import PDRB/growth, poverty, inflation/CPI, IPM, employment.
- Confirm ambiguous figures against official BPS publications.
- Add presentation indicators through existing sync.

Phase 5, extended domains:

- PODES, agriculture, education/health, tourism.
- Full catalog metadata/history.

Phase 6, mobile sync evolution:

- Add new sync entities if needed.
- Update mobile models and UI.
- Add end-to-end sync tests.

## 13. Risks and mitigations

1. BPS API availability or rate limits:
   - Mitigation: retry with backoff, pagination checkpoints, resumable runs.

2. Endpoint schema changes:
   - Mitigation: raw response versioning and mapper tests against fixtures.

3. Ambiguous or revised official figures:
   - Mitigation: store revisions and source release IDs; confirm against publications.

4. SQLite growth:
   - Mitigation: store raw responses on disk, not DB; archive old runs.

5. Sync payload bloat:
   - Mitigation: phase 1 exports selected indicators; defer full observation sync.

6. Token leakage:
   - Mitigation: strip query tokens, redact logs, automated quality check.

## 14. Immediate next actions

1. Validate BPS domain lookup for 3509 using the provided token outside the app.
2. Save one raw subject-list response as a fixture.
3. Add config/bps.php and the data_sources migration.
4. Implement BpsWebApiClient with mocked tests.
5. Implement catalog scanner only after fixture validation.
6. Keep all existing tests green after each phase.

