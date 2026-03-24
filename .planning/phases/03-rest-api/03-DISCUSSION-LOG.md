# Phase 3: REST API - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-03-24
**Phase:** 03-rest-api
**Areas discussed:** None (user delegated all decisions to Claude)

---

## Gray Areas Presented

| Option | Description | Selected |
|--------|-------------|----------|
| API-Ansatz | Standard WP REST API vs. custom Endpoints | |
| Featured Image | Logo-Upload per API — separater Schritt | |
| You decide | Claude entscheidet den besten API-Ansatz | ✓ |

**User's choice:** You decide — full discretion to Claude
**Notes:** User trusts Claude to choose the pragmatic approach. Standard WP REST API preferred since CPT already has show_in_rest.

---

## Claude's Discretion

All REST API decisions:
- Use built-in WP REST API (no custom endpoints)
- Application Passwords for auth
- GitHub URL validation filter for REST saves
- Standard media upload for logos
- Test script/curl examples for verification

## Deferred Ideas

None
