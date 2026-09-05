# Security Remediation

Action items from the security review of the authentication module ([`../owasp-auth-review.md`](../owasp-auth-review.md)).

Organized by the component that owns the change. Each file is self-contained: problem, risk, exact code change, side effects, verification.

---

## Structure

```
remediation/
├── backend/     Laravel API — 10 files
├── frontend/    Vue 3 SPA — 1 file
└── ai/          FastAPI service — not yet reviewed
```

| Folder | Owner | Files | Status |
|---|---|---|---|
| [`backend/`](backend/) | Backend developer | 10 | Ready to implement |
| [`frontend/`](frontend/) | Frontend developer | 1 | Blocked on backend F-04 |
| [`ai/`](ai/) | — | 0 | **Out of scope so far — see below** |

---

## Read this first

**`ai/` is empty because the AI service has not been reviewed, not because it is secure.**

The review covered the authentication module only (SCRUM-50). The FastAPI service on port 5000 was never examined. Two things are already known from the infrastructure audit and are not yet tracked by any ticket:

- The service publishes its interactive API documentation at `/docs`, reachable without authentication — the CI pipeline health check curls it directly
- No authentication mechanism appears on the service at all; the backend calls it over the internal Docker network via `AI_SERVICE_URL`

Neither has been assessed for exploitability. A separate review ticket is needed. Raise with the team before the service is exposed outside the compose network.

---

## Order of work

1. **`backend/F-02`** and **`backend/F-03`** — the two High findings in SCRUM-49
2. **`backend/F-01`** — the High finding in SCRUM-48
3. Remaining backend Medium findings: F-04, F-05, F-06, F-07
4. **`frontend/F-04-token-expiry-handling`** — must land with or before backend F-04
5. Remaining Low findings: F-11, F-12, F-13

---

## Rules

1. **One commit per finding**, ticket key and finding ID in the message:
   ```
   SCRUM-48 feat: rate limit authentication endpoints (F-01)
   ```
2. **A test for every fix.** CI runs PHPUnit and Vitest as blocking gates since SCRUM-51. An untested fix can be silently reverted later.
3. **Remove the `// SECURITY:` comment** from the source file as you fix its finding. A stale marker on patched code trains people to ignore them.
4. **Read the "Side effects" section.** Five of these eleven break existing tests, factories, or the frontend if applied blindly.

---

## Cross-component dependencies

- **backend/F-04 and frontend/F-04 are one change split across two repos.** Merging the backend half alone means users hit a broken app state after 24 hours instead of a clean re-login. Coordinate.
- **backend/F-13 requires backend/F-01.** The constant-time login path adds a bcrypt operation on every failed login for an unknown email; without rate limiting that is a CPU denial-of-service vector.
- **backend/F-02 and backend/F-04 are paired.** Expiration bounds how long a stolen token is useful; the status check bounds how long a revoked account is useful. Neither substitutes for the other.

---

## Findings with no code change under these tickets

| Finding | Severity | Routed to |
|---|---|---|
| F-08 user enumeration via registration | Medium | Mitigated by F-01; residual risk documented in SCRUM-52 |
| F-09 excessive data in auth responses | Medium | SCRUM-52 |
| F-10 no authentication event logging | Low | SCRUM-52 |
| F-14 no email verification | Low | SCRUM-52 — product decision |
| F-15 dead stateful session config | Info | SCRUM-52 |

---

## Questions

Ping Nizar (security) before deviating from a proposed remediation.
