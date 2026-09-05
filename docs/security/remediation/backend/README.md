# Backend Remediation — Laravel API

**Owner:** Backend developer
**Source review:** [`../../owasp-auth-review.md`](../../owasp-auth-review.md)
**Tickets:** SCRUM-48, SCRUM-49

All ten findings below are in `backend/`. Work top to bottom within each ticket.

---

## SCRUM-49 — Secure token generation and validation

Do this ticket first. It contains the two highest-impact findings in the review.

| File | Finding | Severity | OWASP | Files touched |
|---|---|---|---|---|
| [`F-02-suspended-user-tokens.md`](F-02-suspended-user-tokens.md) | Suspended users keep working tokens | **High** | A01 | new middleware, `bootstrap/app.php`, `routes/api.php` |
| [`F-03-mass-assignable-role.md`](F-03-mass-assignable-role.md) | `role` and `status` mass-assignable | **High** | A01 | `User.php`, `AuthController.php`, factories |
| [`F-04-token-expiration.md`](F-04-token-expiration.md) | Tokens never expire | Medium | A07 | `config/sanctum.php`, `.env.example` |
| [`F-05-token-abilities.md`](F-05-token-abilities.md) | Tokens carry unrestricted `*` abilities | Medium | A01 | `AuthController.php` |
| [`F-11-token-prefix.md`](F-11-token-prefix.md) | Sanctum token prefix not set | Low | A05 | `.env.example`, `docker-compose.yml` |

## SCRUM-48 — Validate and sanitize authentication inputs

| File | Finding | Severity | OWASP | Files touched |
|---|---|---|---|---|
| [`F-01-rate-limiting.md`](F-01-rate-limiting.md) | No rate limiting on auth endpoints | **High** | A07 | `routes/api.php`, `AppServiceProvider.php` |
| [`F-06-cors-configuration.md`](F-06-cors-configuration.md) | CORS unrestricted | Medium | A05 | new `config/cors.php`, `.env.example` |
| [`F-07-non-string-email.md`](F-07-non-string-email.md) | 500 error on non-string email | Medium | A05 | three FormRequests |
| [`F-12-password-policy.md`](F-12-password-policy.md) | Weak password policy | Low | A07 | two FormRequests |
| [`F-13-timing-enumeration.md`](F-13-timing-enumeration.md) | Timing-based user enumeration | Low | A07 | `AuthController.php` |

---

## Files that will change, grouped

Useful for planning the branch:

| File | Findings |
|---|---|
| `routes/api.php` | F-01, F-02 |
| `app/Http/Controllers/Auth/AuthController.php` | F-03, F-05, F-13 |
| `app/Models/User.php` | F-03 |
| `app/Http/Requests/Auth/LoginRequest.php` | F-07 |
| `app/Http/Requests/Auth/RegisterClientRequest.php` | F-07, F-12 |
| `app/Http/Requests/Auth/RegisterAgencyRequest.php` | F-07, F-12 |
| `app/Http/Middleware/EnsureUserIsActive.php` | F-02 — new file |
| `bootstrap/app.php` | F-02 |
| `config/sanctum.php` | F-04 |
| `config/cors.php` | F-06 — new file |
| `app/Providers/AppServiceProvider.php` | F-01 |
| `.env.example` | F-04, F-06, F-11 |
| `docker-compose.yml` | F-11 |
| Factories and seeders | F-03 |

---

## Ordering constraints

- **F-01 before F-13.** F-13 adds a bcrypt operation on every failed login for an unknown email. Without rate limiting, that is a cheap CPU denial-of-service.
- **F-04 needs the frontend.** See [`../frontend/F-04-token-expiry-handling.md`](../frontend/F-04-token-expiry-handling.md). Do not merge backend F-04 without it.
- **F-05 optional follow-up interacts with F-02.** Read the note in that file before implementing the `RoleMiddleware` change.

---

## Findings that will break existing tests

Budget time for these. All four are flagged in their own files.

| Finding | What breaks |
|---|---|
| F-01 | `LoginTest` chains failed logins and will hit the throttle → 429 instead of 401 |
| F-02 | Every authenticated test passes through the new middleware; factory users need `status: active` |
| F-03 | `User::factory()->create(['role' => ...])` silently stops setting the role → 403 everywhere |
| F-12 | `uncompromised()` makes an outbound HTTP call; test fixtures using weak passwords start failing |

---

## Before pushing

```powershell
docker compose exec backend php artisan test
```

All 73 tests must be green. Then push and confirm the CI run is green — the pipeline gate is blocking on `develop` and `main`.
