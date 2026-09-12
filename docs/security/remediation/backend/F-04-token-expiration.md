# F-04 — Tokens never expire

| | |
|---|---|
| **Severity** | Medium |
| **OWASP** | A07:2021 Identification and Authentication Failures |
| **Ticket** | SCRUM-49 |
| **Files** | `config/sanctum.php`, `.env.example`, frontend auth store |
| **Source** | [`../owasp-auth-review.md`](../owasp-auth-review.md) §F-04 |

---

## Problem

`backend/config/sanctum.php`:

```php
'expiration' => null,
```

Sanctum issues tokens with `expires_at` unset, so they are valid forever.

## Risk

A token captured from browser storage, a log file, a shared device, or an intercepted request stays valid indefinitely. There is no natural window that limits the value of a stolen credential.

This compounds F-02: without expiration, a suspended user's retained access has no end date at all.

If the frontend stores tokens in `localStorage`, any XSS in the SPA yields a permanent credential rather than a session-length one.

---

## Change

### 1. `backend/config/sanctum.php`

**Before**

```php
'expiration' => null,
```

**After**

```php
'expiration' => env('SANCTUM_TOKEN_EXPIRATION', 1440),
```

### 2. `.env.example` and `backend/.env.example`

```
SANCTUM_TOKEN_EXPIRATION=1440
```

1440 minutes = 24 hours. Reasonable for a rental platform where sessions are task-based rather than always-on. Shorten for production if the team prefers.

---

## Side effects

**This is a breaking change for the frontend.** Once tokens expire, the SPA will start receiving `401` on requests that previously always succeeded.

The frontend must:

1. Intercept `401` on any authenticated request
2. Clear the stored token and user state
3. Redirect to the login screen

Coordinate with whoever owns `frontend/src/stores/auth.js` **before merging**. `frontend/src/stores/__tests__/auth.spec.js` (18 tests) will likely need a case for expired-token handling.

Without the frontend change, users will hit a broken app state after 24 hours rather than a clean re-login.

**Existing tokens are unaffected.** `expiration` applies at validation time based on `created_at`, so tokens already in the database become invalid once they exceed the new window. Anyone currently logged in will be logged out on deploy. Warn the team.

---

## Verification

| Step | Expected |
|---|---|
| Log in, inspect `personal_access_tokens` | `expires_at` non-null, ~24h ahead |
| Set `SANCTUM_TOKEN_EXPIRATION=1`, log in, wait 90s, call `/api/me` | **401** |
| Frontend receives 401 | Redirects to login, storage cleared |
| Reset expiration to 1440, log in, call `/api/me` | 200 |

Write a test that travels time forward past expiry:

```php
$this->travel(25)->hours();
$this->getJson('/api/me')->assertStatus(401);
```

---

## Note

Pair with **F-02**. Expiration bounds how long a stolen token is useful; the active-status check bounds how long a revoked account is useful. Neither substitutes for the other.

## Commit

```
SCRUM-49 feat: set token expiration (F-04)
```
