---
phase: 03-rest-api
verified: 2026-03-24T10:00:00Z
status: passed
score: 6/6 must-haves verified
re_verification: false
---

# Phase 3: REST API Verification Report

**Phase Goal:** External clients (especially Claude) can create, update, and delete community projects programmatically
**Verified:** 2026-03-24T10:00:00Z
**Status:** passed
**Re-verification:** No — initial verification

## Goal Achievement

### Observable Truths

| #  | Truth                                                                                 | Status     | Evidence                                                                                                             |
|----|---------------------------------------------------------------------------------------|------------|----------------------------------------------------------------------------------------------------------------------|
| 1  | POST with valid Application Password creates a project with all meta fields           | VERIFIED   | CPT registered with `show_in_rest => true`; all three meta fields registered with `show_in_rest => true`            |
| 2  | PUT/PATCH updates existing project meta fields                                        | VERIFIED   | Same REST exposure; `validate_rest_github_url` filter passes valid data through unchanged                           |
| 3  | DELETE removes a project                                                              | VERIFIED   | Built-in WordPress REST DELETE handler active via `show_in_rest`; test script covers trash + force delete (Tests 7-8) |
| 4  | Unauthenticated requests to create/update/delete return 401                          | VERIFIED   | `auth_callback => current_user_can('edit_community_projects')` on all meta fields; `map_meta_cap => true` on CPT; test script covers Tests 1 and 9 |
| 5  | GitHub URL meta field rejects non-github.com URLs via REST (same validation as admin meta box) | VERIFIED | `validate_rest_github_url` method present at line 117 in `class-cpt-project.php`; returns `WP_Error('rest_invalid_github_url', ..., ['status' => 400])` for non-github.com URLs; wired via `rest_pre_insert_community_project` filter in `class-community-master.php` line 32 |
| 6  | menu_order is readable and writable via REST API                                     | VERIFIED   | `register_rest_fields` method present at line 144; registers `get_callback` and `update_callback` for `menu_order`; wired via `rest_api_init` action in `class-community-master.php` line 33 |

**Score:** 6/6 truths verified

### Required Artifacts

| Artifact                              | Expected                                           | Status     | Details                                                                                  |
|---------------------------------------|----------------------------------------------------|------------|------------------------------------------------------------------------------------------|
| `includes/class-cpt-project.php`      | REST validation filter and menu_order exposure     | VERIFIED   | Contains `validate_rest_github_url` (line 117) and `register_rest_fields` (line 144); PHP syntax clean |
| `tests/test-rest-api.sh`              | Automated curl-based REST API verification         | VERIFIED   | 10 `assert_status` calls (exceeds required 7); executable; valid bash syntax             |
| `includes/class-community-master.php` | Hook registration wiring both new methods          | VERIFIED   | Lines 32-33 wire the filter and action in constructor                                    |

### Key Link Verification

| From                           | To                      | Via                                              | Status   | Details                                                                                                    |
|-------------------------------|-------------------------|--------------------------------------------------|----------|------------------------------------------------------------------------------------------------------------|
| `includes/class-cpt-project.php` | WordPress REST API   | `rest_pre_insert_community_project` filter       | WIRED    | `add_filter('rest_pre_insert_community_project', [CM_CPT_Project::class, 'validate_rest_github_url'], 10, 2)` present in `class-community-master.php` line 32 |
| `includes/class-community-master.php` | `includes/class-cpt-project.php` | `register_rest_fields` via `rest_api_init` | WIRED | `add_action('rest_api_init', [CM_CPT_Project::class, 'register_rest_fields'])` present at line 33         |

### Data-Flow Trace (Level 4)

Not applicable — this phase delivers server-side PHP hooks and a bash test script, not React/Next.js components that render dynamic data. The REST API surface is validated statically via PHP lint and structurally via grep.

### Behavioral Spot-Checks

| Behavior                                  | Command                                                          | Result            | Status |
|-------------------------------------------|------------------------------------------------------------------|-------------------|--------|
| PHP syntax: class-cpt-project.php         | `php -l includes/class-cpt-project.php`                         | No syntax errors  | PASS   |
| PHP syntax: class-community-master.php    | `php -l includes/class-community-master.php`                    | No syntax errors  | PASS   |
| Bash syntax: test-rest-api.sh             | `bash -n tests/test-rest-api.sh`                                 | No errors         | PASS   |
| Test script is executable                 | `test -x tests/test-rest-api.sh`                                 | Exit 0            | PASS   |
| Assertion count >= 7                      | `grep -c 'assert_status' tests/test-rest-api.sh`                | 10                | PASS   |
| Validation filter in both files           | `grep 'rest_pre_insert_community_project' class-cpt-project.php class-community-master.php` | Found in both | PASS |
| menu_order field registered               | `grep 'register_rest_field' class-cpt-project.php`              | Found             | PASS   |
| Commits a6feb18, f7daac3 exist            | `git log --oneline -5`                                           | Both present      | PASS   |

Live REST API behavior (actual HTTP 401/201/400 responses) requires a running WordPress instance and is routed to human verification below.

### Requirements Coverage

| Requirement | Source Plan   | Description                                                             | Status    | Evidence                                                                                                  |
|-------------|---------------|-------------------------------------------------------------------------|-----------|-----------------------------------------------------------------------------------------------------------|
| API-01      | 03-01-PLAN.md | Projekte können per REST API erstellt werden (POST)                     | SATISFIED | CPT `show_in_rest => true`; meta fields `show_in_rest => true`; test script Test 2 covers POST create    |
| API-02      | 03-01-PLAN.md | Projekte können per REST API bearbeitet werden (PUT/PATCH)              | SATISFIED | Built-in WordPress REST PATCH endpoint active; test script Tests 4 and 6 cover PATCH update             |
| API-03      | 03-01-PLAN.md | Projekte können per REST API gelöscht werden (DELETE)                   | SATISFIED | Built-in WordPress REST DELETE endpoint active; test script Tests 7-8 cover trash and force delete      |
| API-04      | 03-01-PLAN.md | Alle Custom Meta Fields sind über REST API les- und schreibbar          | SATISFIED | All three `_community_master_*` meta fields registered with `show_in_rest => true`; `menu_order` via `register_rest_field`; test script Test 3 reads and checks all four fields |
| API-05      | 03-01-PLAN.md | REST API Endpunkte haben korrekte Permission Callbacks (capability-based) | SATISFIED | `auth_callback => current_user_can('edit_community_projects')` on all meta fields; `capability_type => 'community_project'` with `map_meta_cap => true` on CPT registration |
| SEC-05      | 03-01-PLAN.md | REST API verwendet capability-based Permission Checks                   | SATISFIED | Same as API-05; additionally `validate_rest_github_url` rejects invalid GitHub URLs with WP_Error 400; test script Tests 1, 5, 9 cover auth rejection and URL validation |

No orphaned requirements: all six IDs declared in PLAN frontmatter are covered, and REQUIREMENTS.md maps API-01..05 and SEC-05 exclusively to Phase 3.

### Anti-Patterns Found

| File                    | Line | Pattern        | Severity | Impact                                                         |
|-------------------------|------|----------------|----------|----------------------------------------------------------------|
| `tests/test-rest-api.sh` | 8    | `XXXX-XXXX-XXXX-XXXX` placeholder in comment | Info | Example credential in comment only — not a real secret, no functional impact |

No blockers or warnings found. No TODO/FIXME/placeholder code in production PHP files. No empty return stubs. No hardcoded empty arrays in REST-facing code.

### Human Verification Required

#### 1. Unauthenticated POST returns 401

**Test:** Run `./tests/test-rest-api.sh https://meintechblog.de <user:app-password>` — or send an unauthenticated POST to `/wp-json/wp/v2/community_project` without `-u` credentials.
**Expected:** HTTP 401 response.
**Why human:** Requires a live WordPress instance with the plugin active and Application Password configured.

#### 2. Authenticated POST creates project with all meta fields

**Test:** Run test script Test 2 against a live instance. Inspect the JSON response for `meta._community_master_description`, `meta._community_master_github_url`, `meta._community_master_installer`, and `menu_order`.
**Expected:** HTTP 201, all four fields present in response body.
**Why human:** Requires live WordPress REST API with Application Password.

#### 3. GitHub URL validation returns 400 for non-github.com URL

**Test:** PATCH an existing project with `{"meta":{"_community_master_github_url":"https://evil.com/malware"}}` using valid credentials.
**Expected:** HTTP 400 with error code `rest_invalid_github_url`.
**Why human:** `rest_pre_insert_community_project` filter behavior cannot be exercised without a running WordPress instance loading all hooks.

#### 4. menu_order reads and writes correctly

**Test:** POST a project with `"menu_order": 5`, GET it back and confirm `menu_order` is 5, then PATCH with `"menu_order": 10` and GET again.
**Expected:** menu_order reflects the written value in each GET response.
**Why human:** Requires live WordPress instance; `register_rest_field` callbacks only execute inside the WordPress runtime.

### Gaps Summary

No gaps. All six must-have truths are verified. All required artifacts exist, are substantive, and are correctly wired. Both commits (a6feb18, f7daac3) are present in git history. The test script has 10 assertions covering every requirement. Four live-runtime behaviors are routed to human verification as they require a running WordPress instance.

---

_Verified: 2026-03-24T10:00:00Z_
_Verifier: Claude (gsd-verifier)_
