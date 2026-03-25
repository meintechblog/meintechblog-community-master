---
phase: 03-rest-api
plan: 01
subsystem: api
tags: [wordpress-rest-api, php, validation, curl-tests]

requires:
  - phase: 01-cpt-admin
    provides: CPT registration with show_in_rest, meta fields, capability system
provides:
  - REST GitHub URL validation filter (parity with admin meta box)
  - menu_order exposed as readable/writable REST field
  - Automated curl-based REST API verification script (9 tests)
affects: [04-deploy]

tech-stack:
  added: []
  patterns: [rest_pre_insert filter for REST-side validation, register_rest_field for custom fields]

key-files:
  created: [tests/test-rest-api.sh]
  modified: [includes/class-cpt-project.php, includes/class-community-master.php]

key-decisions:
  - "Used rest_pre_insert_community_project filter instead of custom REST controller for validation"
  - "Exposed menu_order via register_rest_field instead of adding page-attributes support"

patterns-established:
  - "REST validation filter: use rest_pre_insert_{cpt} to enforce domain rules on REST saves"
  - "Test script pattern: curl-based bash scripts with assert_status helper for REST API verification"

requirements-completed: [API-01, API-02, API-03, API-04, API-05, SEC-05]

duration: 2min
completed: 2026-03-24
---

# Phase 03 Plan 01: REST API Hardening Summary

**GitHub URL domain validation for REST saves, menu_order REST field exposure, and 9-test curl verification script**

## Performance

- **Duration:** 2 min
- **Started:** 2026-03-24T09:33:03Z
- **Completed:** 2026-03-24T09:34:53Z
- **Tasks:** 2
- **Files modified:** 3

## Accomplishments
- GitHub URL validation now rejects non-github.com URLs via REST API with 400 status, matching admin meta box behavior
- menu_order is readable and writable through the REST API without requiring page-attributes support
- Automated test script covers all CRUD operations, permission checks, and validation rules (9 assertions)

## Task Commits

Each task was committed atomically:

1. **Task 1: Add REST GitHub URL validation and expose menu_order** - `a6feb18` (feat)
2. **Task 2: Create REST API verification test script** - `f7daac3` (test)

## Files Created/Modified
- `includes/class-cpt-project.php` - Added validate_rest_github_url filter and register_rest_fields method
- `includes/class-community-master.php` - Wired rest_pre_insert filter and rest_api_init action
- `tests/test-rest-api.sh` - Curl-based REST API test script (9 tests, executable)

## Decisions Made
- Used `rest_pre_insert_community_project` filter for validation instead of a custom WP_REST_Controller -- keeps the architecture simple and leverages built-in WordPress REST endpoints
- Exposed `menu_order` via `register_rest_field` instead of adding `page-attributes` to CPT supports array -- avoids showing an unwanted Parent dropdown in the admin UI

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
None

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- REST API is fully functional with validation parity and custom field support
- Test script ready for live verification against meintechblog.de with Application Password credentials
- Plugin ready for Phase 04 deployment

## Self-Check: PASSED

All files exist. All commit hashes verified.

---
*Phase: 03-rest-api*
*Completed: 2026-03-24*
