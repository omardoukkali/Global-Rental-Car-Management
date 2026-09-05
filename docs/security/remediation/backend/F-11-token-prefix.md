# F-11 — Sanctum token prefix not set

| | |
|---|---|
| **Severity** | Low |
| **OWASP** | A05:2021 Security Misconfiguration |
| **Ticket** | SCRUM-49 |
| **Files** | `.env.example`, `backend/.env.example`, all environments |
| **Source** | [`../owasp-auth-review.md`](../owasp-auth-review.md) §F-11 |

---

## Problem

`backend/config/sanctum.php`:

```php
'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),
```

The environment variable is unset, so the default empty string applies. Issued tokens have no recognizable prefix.

## Risk

GitHub secret scanning, TruffleHog, Trivy's secret scanner, and similar tooling detect leaked credentials by matching known prefix patterns. Without a prefix, a Sanctum token committed to the repository looks like a random string and will not be flagged.

This project has already had credentials committed in a tracked `.env` file. The CI pipeline runs TruffleHog on every push specifically to catch that class of mistake — and it currently cannot catch this one.

---

## Change

No code change is needed. `config/sanctum.php` already reads the value from the environment.

### 1. Add to `.env.example` and `backend/.env.example`

```
SANCTUM_TOKEN_PREFIX=grcm_
```

### 2. Add to `docker-compose.yml` under the `backend` service environment block

```yaml
SANCTUM_TOKEN_PREFIX: ${SANCTUM_TOKEN_PREFIX:-grcm_}
```

### 3. Set it in the production environment

Same value. The prefix must be consistent, or scanners will only match a subset of tokens.

`grcm_` = Global Rental Car Management. Any distinctive short string works; what matters is that it is stable and unlikely to appear in ordinary text.

---

## Side effects

**Existing tokens keep their old format.** The prefix is applied at creation time only. Tokens already in `personal_access_tokens` are unaffected and continue to work.

Once F-04 (token expiration) is in place, the old prefix-less tokens age out naturally within the expiration window.

No test changes expected — tests do not assert on token format. Verify with the full suite anyway.

---

## Verification

| Step | Expected |
|---|---|
| Log in, inspect the `token` value in the response | Starts with `grcm_` |
| Inspect `token` column in `personal_access_tokens` | Hashed, as before — the prefix is on the plaintext |
| Old token issued before the change | Still authenticates |
| Full test suite | Green |

---

## Follow-up worth considering

Once a prefix is in place, a custom Trivy or TruffleHog rule can be added to the CI pipeline to detect `grcm_` in the codebase. That closes the loop — the prefix only helps if something is looking for it.

Raise with Nizar if you want this added to `.github/workflows/ci.yaml`.

## Commit

```
SCRUM-49 chore: set Sanctum token prefix for secret scanning (F-11)
```
