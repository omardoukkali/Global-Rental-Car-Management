# Authentication Security

**Ticket:** SCRUM-52
**Scope:** How authentication and authorization work in this project, what protects them, and what risks the team has accepted.

This is the reference document. For the evidence behind it — findings, severities, risk analysis — see [`owasp-auth-review.md`](owasp-auth-review.md).

---

## 1. Authentication model

**Stateless bearer tokens via Laravel Sanctum.**

The API does **not** use Sanctum's cookie-based SPA mode. `config/sanctum.php` still contains a `stateful` domain list, a `web` guard, and a session middleware block — these are Laravel defaults and are **inactive**. Do not assume cookie-based auth or CSRF protection is in play.

| Step | What happens |
|---|---|
| Register | `POST /api/register/client` or `/register/agency` — creates the user, returns 201. No token issued. |
| Login | `POST /api/login` — validates credentials, returns a plaintext bearer token. |
| Authenticated request | `Authorization: Bearer <token>` header. Sanctum resolves the token to a user. |
| Logout | `POST /api/logout` — deletes the current token only. Other sessions survive. |

Tokens are stored hashed in `personal_access_tokens`. The plaintext value is returned once at login and never again.

---

## 2. Authorization model

Three roles, assigned server-side at registration and never client-supplied: `client`, `agency`, `admin`.

Agencies have a second axis: an `Agency` record carries its own `status`, which starts as `pending` and requires admin approval.

### Middleware

| Alias | Class | Purpose |
|---|---|---|
| `auth:sanctum` | Sanctum | Rejects requests without a valid bearer token |
| `role` | `RoleMiddleware` | Rejects users whose `role` is not in the allowed list. Uses strict comparison. |
| `agency.approved` | `EnsureAgencyIsApproved` | Rejects agencies whose `Agency.status` is not `approved` |

### Route groups

| Group | Middleware | Routes |
|---|---|---|
| Public | none | register (×2), login, `GET /cities` |
| Authenticated | `auth:sanctum` | `/me`, `/logout` |
| Client | `+ role:client` | `/client/test` |
| Agency | `+ role:agency` | agency profile read/update |
| Approved agency | `+ agency.approved` | agency points, cars, car images |
| Admin | `+ role:admin` | agency approve/reject |

**Important limitation:** these middlewares establish *what kind of user* is calling. They do **not** verify that the specific resource being accessed belongs to the calling agency. Object-level ownership has not been reviewed — that is the scope of SCRUM-78 and SCRUM-102.

---

## 3. Controls in the application

| Control | Implementation |
|---|---|
| Password hashing | `User` casts `password` to `hashed`; bcrypt applied by the model. No manual hashing anywhere. |
| Password hash never serialized | `$hidden` includes `password` and `remember_token` |
| Generic login failure | Same message for unknown email and wrong password — no enumeration at `/login` |
| Mass assignment allowlist | `$fillable`, not `$guarded` |
| Server-assigned privilege fields | `role` and `status` hardcoded in both registration methods, never read from the request |
| Atomic agency registration | `DB::transaction()` — no orphaned user if agency creation fails |
| Scoped logout | `currentAccessToken()->delete()` revokes one token, not all sessions |
| Email normalization | Lowercased and trimmed before validation — prevents duplicate accounts differing by case |
| Non-sequential identifiers | `HasUuids` on `User` — no ID enumeration |
| Strict role comparison | `in_array($user->role, $roles, true)` — no PHP type-juggling bypass |
| Password policy | Minimum 8 characters, letters and numbers required |

These are working controls. Do not weaken them without a review.

---

## 4. Controls in the pipeline

Security is enforced at merge time, not only in code.

| Control | Where | Blocking? |
|---|---|---|
| TruffleHog secret scan | `.github/workflows/ci.yaml` | **Yes** |
| Trivy — container image scan (×3) | same | No — report only |
| Trivy — filesystem scan (vuln, secret, misconfig) | same | No — report only |
| Trivy JSON reports as build artifacts, 14-day retention | same | n/a |
| PHPUnit — 73 backend tests | same | **Yes** (SCRUM-51) |
| Vitest — 51 frontend tests | same | **Yes** (SCRUM-51) |
| Service health assertions on `/up` and `/docs` | same | **Yes** |
| `composer validate --strict` | same | **Yes** |
| Reproducible frontend builds — `npm ci` against the lockfile | `frontend/Dockerfile` | n/a |

### Branch protection

Restored during SCRUM-51 after being found absent on both `develop` and `main`. Configured via GitHub Rulesets:

- Pull request required before merging
- 1 approving review (2 on `main`)
- Stale approvals dismissed when new commits are pushed
- `Build, Test & Security Scan` required to pass
- Branches must be up to date before merging
- Force pushes and branch deletions blocked

**This is why the test gate matters.** Before SCRUM-51, 124 tests existed in the repository and CI executed none of them. The pipeline passed green on code nobody had verified.

---

## 5. Accepted residual risks

Known, deliberate, not defects. Each links to its analysis in the review.

| Risk | Why accepted | Reference |
|---|---|---|
| **User enumeration via registration.** `unique:users,email` returns a distinguishable error, so an attacker can confirm whether an email has an account. | Full mitigation requires generic responses plus email confirmation — a product change. Rate limiting (F-01) makes bulk enumeration impractical. | Review §F-08 |
| **Full user model returned in auth responses.** `role`, `status`, `email`, `phone`, timestamps are all exposed. | No high-value secret leaks; `$hidden` protects the password hash. The structural risk is that future columns publish automatically. Response shaping via an API Resource is proposed but not scheduled. | Review §F-09 |
| **No authentication event logging.** Failed and successful logins leave no trace. | Not yet implemented. This is the control that makes every other weakness undetectable in practice — it should be prioritized. | Review §F-10 |
| **No email verification.** Accounts are active immediately; addresses are unverified. | Product decision. Agency impact is limited by admin approval; client accounts are usable at once. Blocks trustworthy password reset later. | Review §F-14 |
| **Dead stateful session config.** Sanctum's cookie-mode settings remain in `config/sanctum.php`. | Harmless but misleading. Documented in §1 above rather than removed. | Review §F-15 |

---

## 6. Open items

Not accepted — pending work.

| Item | Severity | Tracked in |
|---|---|---|
| No rate limiting on login or registration | **High** | SCRUM-122 (F-01) |
| Suspended users retain valid tokens — suspension is non-functional | **High** | SCRUM-123 (F-02) |
| `role` and `status` mass-assignable — privilege escalation path | **High** | SCRUM-123 (F-03) |
| Tokens never expire | Medium | SCRUM-123 (F-04) |
| Tokens carry unrestricted `*` abilities | Medium | SCRUM-123 (F-05) |
| CORS unrestricted — no `config/cors.php` | Medium | SCRUM-122 (F-06) |
| 500 error on non-string email | Medium | SCRUM-122 (F-07) |
| SPA has no handling for expired tokens | Medium | SCRUM-124 |
| Weak password policy — no breached-password check | Low | SCRUM-122 (F-12) |
| Timing-based user enumeration at login | Low | SCRUM-122 (F-13) |
| Sanctum token prefix unset — leaked tokens undetectable by scanners | Low | SCRUM-123 (F-11) |

Per-finding remediation instructions: [`remediation/`](remediation/)

### Not yet reviewed

| Area | Note |
|---|---|
| Object-level authorization on agency and car resources | Role middleware proves *what kind of user*, not *whose resource*. SCRUM-78, SCRUM-102. |
| Frontend token storage | If tokens are in `localStorage`, XSS yields a usable credential. No ticket. |
| AI service (FastAPI) | Never reviewed. `/docs` served publicly with no authentication, no visible auth mechanism, container runs as root. See [`remediation/ai/README.md`](remediation/ai/README.md). No ticket. |
| Container hardening | All three images run as root with floating base image tags. No ticket. |
| Trivy findings are non-blocking | All scans set `exit-code: '0'` — vulnerabilities are reported, never enforced. No ticket. |

---

## 7. Maintenance

- **This document describes the current state.** Update it when a control changes.
- **The review does not get updated.** [`owasp-auth-review.md`](owasp-auth-review.md) is a point-in-time assessment. When findings are fixed, they move from §6 to §3 here; the review keeps its original text as the record of what was found and when.
- **New auth-adjacent code needs a review pass.** The controls in §3 are only as good as their coverage. When endpoints are added, check they inherit the right middleware group.