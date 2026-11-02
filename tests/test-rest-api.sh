#!/usr/bin/env bash
set -euo pipefail

# REST API verification for Community Master plugin.
# Usage: ./tests/test-rest-api.sh <site-url> <user:app-password>
#
# Example:
#   ./tests/test-rest-api.sh https://meintechblog.de hulki:XXXX-XXXX-XXXX-XXXX

if [[ $# -lt 2 ]]; then
    echo "Usage: $0 <site-url> <user:app-password>"
    echo "  site-url       WordPress site URL (e.g. https://meintechblog.de)"
    echo "  user:app-password  Application Password credentials"
    exit 1
fi

BASE_URL="${1%/}/wp-json/wp/v2/community_project"
AUTH="$2"

PASS=0
FAIL=0

assert_status() {
    local test_name="$1"
    local expected="$2"
    local actual="$3"

    if [[ "$actual" == "$expected" ]]; then
        echo "PASS: $test_name (HTTP $actual)"
        PASS=$((PASS + 1))
    else
        echo "FAIL: $test_name (expected $expected, got $actual)"
        FAIL=$((FAIL + 1))
    fi
}

# ------------------------------------------------------------------
# Test 1: Unauthenticated POST returns 401 (SEC-05)
# ------------------------------------------------------------------
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE_URL" \
    -H "Content-Type: application/json" \
    -d '{"title":"Unauth Test","status":"publish"}')
assert_status "Unauthenticated POST rejected" "401" "$STATUS"

# ------------------------------------------------------------------
# Test 2: Create project with all meta fields (API-01, API-04)
# ------------------------------------------------------------------
RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$BASE_URL" \
    -u "$AUTH" \
    -H "Content-Type: application/json" \
    -d '{
        "title": "REST Test Project",
        "status": "publish",
        "meta": {
            "_community_master_description": "A test project created via REST API",
            "_community_master_github_url": "https://github.com/meintechblog/test-project",
            "_community_master_installer": "curl -sSL https://example.com/install.sh | bash"
        },
        "menu_order": 5
    }')
STATUS=$(echo "$RESPONSE" | tail -1)
BODY=$(echo "$RESPONSE" | sed '$d')
assert_status "Create project with meta fields" "201" "$STATUS"

POST_ID=$(echo "$BODY" | grep -o '"id":[0-9]*' | head -1 | cut -d: -f2)
if [[ -z "$POST_ID" ]]; then
    echo "FATAL: Could not extract post ID from create response. Aborting."
    exit 1
fi
echo "  Created post ID: $POST_ID"

# ------------------------------------------------------------------
# Test 3: Read project and verify meta fields (API-04)
# ------------------------------------------------------------------
RESPONSE=$(curl -s -w "\n%{http_code}" -X GET "$BASE_URL/$POST_ID" -u "$AUTH")
STATUS=$(echo "$RESPONSE" | tail -1)
BODY=$(echo "$RESPONSE" | sed '$d')
assert_status "Read project" "200" "$STATUS"

# Verify meta fields are present in response
for field in _community_master_description _community_master_github_url _community_master_installer menu_order; do
    if echo "$BODY" | grep -q "$field"; then
        echo "  Field present: $field"
    else
        echo "  WARNING: Field missing from response: $field"
    fi
done

# ------------------------------------------------------------------
# Test 4: Update project via PATCH (API-02)
# ------------------------------------------------------------------
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X PATCH "$BASE_URL/$POST_ID" \
    -u "$AUTH" \
    -H "Content-Type: application/json" \
    -d '{"meta":{"_community_master_description":"Updated description"}}')
assert_status "Update project via PATCH" "200" "$STATUS"

# ------------------------------------------------------------------
# Test 5: GitHub URL validation rejects non-github.com URL (SEC-05)
# ------------------------------------------------------------------
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X PATCH "$BASE_URL/$POST_ID" \
    -u "$AUTH" \
    -H "Content-Type: application/json" \
    -d '{"meta":{"_community_master_github_url":"https://evil.com/malware"}}')
assert_status "GitHub URL validation rejects non-github.com" "400" "$STATUS"

# ------------------------------------------------------------------
# Test 6: Update menu_order via PATCH
# ------------------------------------------------------------------
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X PATCH "$BASE_URL/$POST_ID" \
    -u "$AUTH" \
    -H "Content-Type: application/json" \
    -d '{"menu_order":10}')
assert_status "Update menu_order via PATCH" "200" "$STATUS"

# ------------------------------------------------------------------
# Test 7: Delete project (API-03) -- moves to trash
# ------------------------------------------------------------------
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X DELETE "$BASE_URL/$POST_ID" \
    -u "$AUTH")
assert_status "Delete project (trash)" "200" "$STATUS"

# ------------------------------------------------------------------
# Test 8: Force delete (cleanup)
# ------------------------------------------------------------------
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X DELETE "$BASE_URL/$POST_ID?force=true" \
    -u "$AUTH")
assert_status "Force delete project" "200" "$STATUS"

# ------------------------------------------------------------------
# Test 9: Unauthenticated DELETE returns 401 (SEC-05, API-05)
# ------------------------------------------------------------------
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X DELETE "$BASE_URL/999999")
assert_status "Unauthenticated DELETE rejected" "401" "$STATUS"

# ------------------------------------------------------------------
# Summary
# ------------------------------------------------------------------
echo ""
echo "========================================"
echo "Results: $PASS passed, $FAIL failed"
echo "========================================"

if [[ "$FAIL" -gt 0 ]]; then
    exit 1
fi
