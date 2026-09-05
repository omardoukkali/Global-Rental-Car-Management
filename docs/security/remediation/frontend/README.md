# Frontend Remediation — Vue 3 SPA

**Owner:** Frontend developer
**Source review:** [`../../owasp-auth-review.md`](../../owasp-auth-review.md)

---

## Files

| File | Finding | Severity | Ticket | Blocked on |
|---|---|---|---|---|
| [`F-04-token-expiry-handling.md`](F-04-token-expiry-handling.md) | No handling for expired tokens | Medium | SCRUM-49 | Coordinate with [`../backend/F-04-token-expiration.md`](../backend/F-04-token-expiration.md) |

---

## Only one file — why

The review (SCRUM-50) covered the backend authentication module. The frontend was explicitly out of scope, so this folder contains only the frontend half of a finding that spans both components.

**This does not mean the frontend has no security issues.** Two things were noted as unassessed in the review and have no ticket yet:

- **Token storage.** If tokens are held in `localStorage`, any XSS in the SPA yields a usable credential. Backend F-04 reduces the exposure window from permanent to 24 hours but does not remove it.
- **Route guards.** `SCRUM-45` implemented token storage and route guards. Client-side guards are a UX control, not a security boundary — every protected route must also be enforced server-side. Whether that holds for all 25 API routes has not been verified.

A frontend security review ticket should be raised. Mention it to Nizar.

---

## Before pushing

```powershell
docker compose exec frontend npm run test:run
```

All 51 tests must be green. Vitest is a blocking CI gate since SCRUM-51.
