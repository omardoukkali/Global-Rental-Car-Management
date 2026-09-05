# OWASP Top 10 Review — Authentication Flow

**Ticket:** SCRUM-50
**Scope:** Authentication and session management
**Standard:** OWASP Top 10 (2021)
**Method:** Static code review
**Status:** Complete — findings feed SCRUM-48, SCRUM-49, SCRUM-52

---

## 1. Scope

Files reviewed:

| File | Role |
|---|---|
| `routes/api.php` | Route definitions and middleware assignment |
| `app/Http/Controllers/Auth/AuthController.php` | Registration, login, logout |
| `app/Http/Requests/Auth/LoginRequest.php` | Login input validation |
| `app/Http/Requests/Auth/RegisterClientRequest.php` | Client registration validation |
| `app/Http/Requests/Auth/RegisterAgencyRequest.php` | Agency registration validation |
| `app/Http/Middleware/RoleMiddleware.php` | Role-based authorization |
| `app/Http/Middleware/EnsureAgencyIsApproved.php` | Agency approval gate |
| `app/Models/User.php` | User model, mass assignment, hidden attributes |
| `bootstrap/app.php` | Middleware registration |
| `config/sanctum.php` | Token configuration |

Endpoints in scope:

- `POST /api/register/client` — public
- `POST /api/register/agency` — public
- `POST /api/login` — public
- `GET /api/me` — `auth:sanctum`
- `POST /api/logout` — `auth:sanctum`

Out of scope: agency module (SCRUM-78 to 83), car module (SCRUM-102 to 104), frontend token storage.

---

## 2. Summary of findings

| Severity | Count |
|---|---|
| High | 3 |
| Medium | 6 |
| Low | 5 |
| Informational | 1 |
| **Total** | **15** |

---

## 3. What the implementation does correctly

Recorded so the review is balanced, and so these controls are not weakened by later changes.

- **Password hashing** — `User` casts `password` to `hashed`, so bcrypt is applied by the model. No plaintext storage, no manual hashing.
- **Generic login failure message** — `login()` returns the same `Invalid email or password.` for both an unknown email and a wrong password. Prevents account enumeration at the login endpoint.
- **Password hash never serialized** — `$hidden` includes `password` and `remember_token`.
- **Mass assignment uses an allowlist** — `$fillable`, not `$guarded`.
- **Role and status are server-assigned at registration** — `registerClient()` and `registerAgency()` hardcode `role` and `status` rather than taking them from the request.
- **Atomic agency registration** — `DB::transaction()` prevents an orphaned user record if agency creation fails.
- **Scoped logout** — `currentAccessToken()->delete()` revokes only the token in use, not every session.
- **Email normalization** — `prepareForValidation()` lowercases and trims, preventing duplicate accounts differing only by case.
- **UUID primary keys** — `HasUuids` removes sequential-ID enumeration of user records.
- **Strict role comparison** — `in_array($user->role, $roles, true)` uses strict mode, avoiding PHP type-juggling bypasses.

---

## 4. Findings

### F-01 — No rate limiting on authentication endpoints
**Severity:** High
**OWASP:** A07:2021 Identification and Authentication Failures
**Location:** `routes/api.php` — `POST /login`, `POST /register/client`, `POST /register/agency`

No `throttle` middleware is applied to any authentication route, and no global API throttle is registered in `bootstrap/app.php`.

**Risk:** Unlimited password guessing against `/login`. With the password policy at 8 characters requiring only letters and numbers, offline-strength guessing is not needed — online brute force is viable. The registration endpoints can also be used to mass-create accounts or to flood the database.

**Remediation:** Apply `throttle` to all three routes. Suggested starting point: 5 attempts per minute per IP on login, 3 per minute on registration. Consider throttling by email as well as IP so distributed attempts against a single account are also limited.

**Ticket:** SCRUM-48

---

### F-02 — Suspended users retain valid tokens indefinitely
**Severity:** High
**OWASP:** A01:2021 Broken Access Control
**Location:** `AuthController::login()`, `bootstrap/app.php`

`login()` rejects a user whose `status` is `suspended`. That check runs **only at login**. Once a token exists, no middleware re-checks `status` on subsequent requests.

**Risk:** An administrator suspends an account, but the account continues to have full access to every authenticated route until the token is manually revoked. Since tokens never expire (F-04), that access is permanent. Suspension is effectively non-functional against any user already logged in.

**Remediation:** Add middleware that rejects any request where `$request->user()->status !== 'active'`, applied to the `auth:sanctum` group. Additionally, revoke all tokens for the user at the point of suspension.

**Ticket:** SCRUM-49

---

### F-03 — `role` and `status` are mass-assignable
**Severity:** High
**OWASP:** A01:2021 Broken Access Control
**Location:** `app/Models/User.php` — `$fillable`

Both `role` and `status` appear in `$fillable`.

**Risk:** No current exploit path exists — the two registration methods build the create array field by field and hardcode both values. The finding is that the model permits privilege escalation if any future code passes request data wholesale, for example `User::create($request->validated())` or a profile-update method using `$user->update($request->all())`. A single such line elsewhere in the codebase would allow a client to register or update themselves as `role: admin`. This is a latent flaw with a high impact ceiling, guarded only by developer discipline.

**Remediation:** Remove `role` and `status` from `$fillable`. Assign them explicitly via `$user->role = 'client'` before save, or via `forceFill()`. This preserves current behaviour while removing the escalation path permanently.

**Ticket:** SCRUM-49

---

### F-04 — Tokens never expire
**Severity:** Medium
**OWASP:** A07:2021 Identification and Authentication Failures
**Location:** `config/sanctum.php` — `'expiration' => null`

**Risk:** A token captured from browser storage, a log file, a shared device, or a proxy remains valid forever. There is no natural window that limits the value of a stolen credential. This compounds F-02: without expiration, a suspended user's access has no end date.

**Remediation:** Set `'expiration'` to a finite value in minutes. For a rental platform, 1440 (24 hours) is a reasonable starting point. Note that this requires the frontend to handle 401 responses by redirecting to login.

**Ticket:** SCRUM-49

---

### F-05 — Tokens are issued with unrestricted abilities
**Severity:** Medium
**OWASP:** A01:2021 Broken Access Control
**Location:** `AuthController::login()` — `$user->createToken('auth_token')`

No abilities array is passed, so Sanctum defaults to `['*']` — every token can do everything, and `tokenCan()` always returns true.

**Risk:** No defense in depth. Authorization rests entirely on `RoleMiddleware`; a single missing middleware declaration on a future route means full access for any authenticated user. Also prevents ever issuing limited-scope tokens (mobile, integration, read-only) without reworking login.

**Remediation:** Issue role-scoped abilities at login, e.g. `createToken('auth_token', ['client'])` or a per-role ability list, and check them in policies alongside the role middleware.

**Ticket:** SCRUM-49

---

### F-06 — CORS is unrestricted
**Severity:** Medium
**OWASP:** A05:2021 Security Misconfiguration
**Location:** `config/cors.php` — file does not exist

Laravel 11 applies `HandleCors` to API routes by default. With no published config, the framework default of `'allowed_origins' => ['*']` applies.

**Risk:** Any origin can call the API. Because the SPA authenticates with bearer tokens rather than cookies, this is not directly exploitable as CSRF, but it removes a layer of control: a malicious page can freely interact with the API using a token obtained by other means, and the API is available for use by any third-party site.

**Remediation:** Publish `config/cors.php` and set `allowed_origins` to the explicit frontend origins per environment. Avoid `'*'` in combination with `supports_credentials`.

**Ticket:** SCRUM-48

---

### F-07 — Non-string email causes an unhandled server error
**Severity:** Medium
**OWASP:** A05:2021 Security Misconfiguration
**Location:** `prepareForValidation()` in `LoginRequest`, `RegisterClientRequest`, `RegisterAgencyRequest`

All three call `strtolower(trim($this->email))`. `prepareForValidation()` runs **before** validation, so `email` is still raw user input. Submitting `email` as an array or object causes `trim()` to receive a non-string and throw a `TypeError`.

**Risk:** HTTP 500 instead of a 422 validation error. With `APP_DEBUG=true` this returns a stack trace exposing file paths, framework version, and vendor structure. It is also a trivially reachable denial-of-service and error-handling flaw on three public endpoints.

**Remediation:** Guard the cast — only merge when `is_string($this->email)`. Independently, ensure `APP_DEBUG=false` in any non-local environment.

**Ticket:** SCRUM-48

---

### F-08 — User enumeration via registration
**Severity:** Medium
**OWASP:** A07:2021 Identification and Authentication Failures
**Location:** `RegisterClientRequest` / `RegisterAgencyRequest` — `'unique:users,email'`

The login endpoint correctly avoids enumeration, but registration returns a distinguishable validation error when an email is already registered.

**Risk:** An attacker can confirm whether any given email has an account, building a target list for credential stuffing or phishing. The protection at `/login` is undermined by the disclosure at `/register`.

**Remediation:** Full mitigation requires returning a generic success response and sending a confirmation email, which is a product decision. Given that, the pragmatic mitigation is the rate limiting in F-01, which makes bulk enumeration impractical. Document the accepted residual risk in SCRUM-52.

**Ticket:** SCRUM-48

---

### F-09 — Excessive data in authentication responses
**Severity:** Medium
**OWASP:** A01:2021 Broken Access Control
**Location:** `AuthController::registerClient()`, `registerAgency()`, `login()`, and the `/me` closure in `routes/api.php`

All four return the full `$user` model. `$hidden` protects the password hash, but the response still includes `role`, `status`, `email`, `phone`, `id`, and all timestamps.

**Risk:** No high-value secret is exposed, and the client legitimately needs `role`. The concern is structural: returning whole models means any column added later is published automatically. A future `internal_notes`, `admin_flag`, or verification token column would leak on the next deploy with no code change.

**Remediation:** Introduce an API Resource (`UserResource`) that declares the fields exposed, and return it from all four locations.

**Ticket:** SCRUM-52 (documented) — implementation optional in SCRUM-48

---

### F-10 — No authentication event logging
**Severity:** Low
**OWASP:** A09:2021 Security Logging and Monitoring Failures
**Location:** `AuthController::login()`, `logout()`

No `Log::` call or event dispatch on successful login, failed login, or logout.

**Risk:** A brute-force attempt against `/login` leaves no trace. Post-incident, there is no way to establish when an account was accessed or from where. This is the finding that makes every other authentication weakness undetectable in practice.

**Remediation:** Log failed logins with email and source IP at `warning`, and successful logins at `info`. Do not log passwords or tokens.

**Ticket:** SCRUM-52

---

### F-11 — Sanctum token prefix not set
**Severity:** Low
**OWASP:** A05:2021 Security Misconfiguration
**Location:** `config/sanctum.php` — `'token_prefix' => env('SANCTUM_TOKEN_PREFIX', '')`

**Risk:** Tokens have no recognizable prefix, so GitHub secret scanning and similar tooling cannot detect one committed to a repository. Given that this project already had a credentials incident in a tracked `.env`, the mitigation is directly relevant.

**Remediation:** Set `SANCTUM_TOKEN_PREFIX` to a distinctive value, e.g. `grcm_`.

**Ticket:** SCRUM-49

---

### F-12 — Weak password policy
**Severity:** Low
**OWASP:** A07:2021 Identification and Authentication Failures
**Location:** `RegisterClientRequest`, `RegisterAgencyRequest` — `Password::min(8)->letters()->numbers()`

Eight characters with letters and numbers permits `password1`, `abcd1234`, and similar. A07 explicitly cites permitting weak passwords.

**Risk:** Combined with F-01, common passwords are guessable online. Rate limiting reduces this but does not remove it.

**Remediation:** Add `->uncompromised()` to check against the Have I Been Pwned corpus, and consider raising the minimum to 10. Note that `uncompromised()` makes an external HTTP call, so tests should fake it.

**Ticket:** SCRUM-48

---

### F-13 — Timing-based user enumeration at login
**Severity:** Low
**OWASP:** A07:2021 Identification and Authentication Failures
**Location:** `AuthController::login()`

`Hash::check()` runs only when a user record is found. A request for a nonexistent email therefore returns measurably faster than one for an existing email with a wrong password.

**Risk:** The generic error message can be bypassed by measuring response time. Requires many samples and is noisy over a network, which is why severity is Low.

**Remediation:** Perform a dummy `Hash::check()` against a fixed hash when no user is found, equalizing the work done on both paths.

**Ticket:** SCRUM-48

---

### F-14 — No email verification
**Severity:** Low
**OWASP:** A07:2021 Identification and Authentication Failures
**Location:** `app/Models/User.php` — `MustVerifyEmail` import is commented out

Accounts are created with `status: 'active'` and can log in immediately without proving control of the email address.

**Risk:** Accounts can be registered against addresses the registrant does not own. For the agency flow the impact is limited by the admin approval gate, but client accounts are usable at once. Also blocks password reset from being trustworthy later.

**Remediation:** A product decision rather than a defect to fix under this ticket. Record as accepted residual risk in SCRUM-52 unless the team decides to implement verification.

**Ticket:** SCRUM-52 (documented)

---

### F-15 — Dead stateful session configuration
**Severity:** Informational
**OWASP:** A05:2021 Security Misconfiguration
**Location:** `config/sanctum.php` — `'guard' => ['web']`, `'stateful'`, `'middleware'`

The SPA authenticates with bearer tokens, not Sanctum's cookie-based SPA mode. The stateful domains list, the `web` guard, and the session middleware block are therefore inactive.

**Risk:** No direct risk. It is misleading configuration: a future developer may believe cookie-based auth and CSRF protection are active when they are not.

**Remediation:** Leave as-is, but state explicitly in SCRUM-52 that the API uses stateless bearer-token authentication and that the stateful configuration is unused.

**Ticket:** SCRUM-52

---

## 5. Remediation plan by ticket

### SCRUM-48 — Validate and sanitize authentication inputs
- F-01 rate limiting on login and both registration endpoints
- F-06 publish `config/cors.php` with explicit origins
- F-07 guard `prepareForValidation()` against non-string email
- F-08 mitigated by F-01; residual risk documented
- F-12 add `->uncompromised()` to the password rule
- F-13 constant-time login path

### SCRUM-49 — Secure token generation and validation
- F-02 middleware rejecting non-active users on authenticated routes
- F-03 remove `role` and `status` from `$fillable`
- F-04 set token expiration
- F-05 issue role-scoped token abilities
- F-11 set `SANCTUM_TOKEN_PREFIX`

### SCRUM-52 — Document security measures
- F-09 response shaping decision
- F-10 authentication event logging
- F-14 accepted residual risk — no email verification
- F-15 clarify stateless bearer-token model
- Record controls listed in section 3 so they are not regressed

---

## 6. Runtime verification checklist

To be executed against a running stack after SCRUM-48 and SCRUM-49 are implemented. Each item should also become an automated test so the CI gate added in SCRUM-51 protects it.

| # | Check | Expected |
|---|---|---|
| 1 | 10 rapid failed logins from one IP | 429 after the configured limit |
| 2 | 5 rapid registration attempts | 429 after the configured limit |
| 3 | `POST /login` with `email` as a JSON array | 422, not 500 |
| 4 | Login, then set user `status` to `suspended`, then call `/me` with the existing token | 403 |
| 5 | Inspect a fresh token's `expires_at` in `personal_access_tokens` | Non-null |
| 6 | Inspect a fresh token's `abilities` column | Role-scoped, not `["*"]` |
| 7 | `POST /register/client` with `role: admin` in the body | User created with `role: client` |
| 8 | Preflight `OPTIONS` from a disallowed origin | Origin not reflected in `Access-Control-Allow-Origin` |
| 9 | Login with a known-breached password, e.g. `password1` | 422 |
| 10 | Token issued before expiry change, used after `expiration` is set | 401 |
| 11 | Failed login, then inspect `storage/logs/laravel.log` | Warning entry with email and IP, no password |
| 12 | Compare response times for unknown email vs wrong password over 50 samples | No consistent difference |

---

## 7. Notes and limitations

- This is a static review. Findings F-01 through F-15 are derived from reading source, not from executing attacks. Section 6 exists to confirm them at runtime.
- The agency and car modules were not reviewed. Their routes sit behind `role:agency` and `agency.approved` but object-level ownership checks were not examined — that is the scope of SCRUM-78 and SCRUM-102, and broken object-level authorization there is a plausible and separate concern.
- Frontend token storage was not reviewed. If tokens are held in `localStorage`, any XSS in the SPA yields a permanent credential given F-04.
- `RoleMiddleware` and `EnsureAgencyIsApproved` were reviewed and are correctly implemented for their stated purpose. `EnsureAgencyIsApproved` duplicates the role check already performed by `RoleMiddleware`, which is harmless redundancy rather than a defect.