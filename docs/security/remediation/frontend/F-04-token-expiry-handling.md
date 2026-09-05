# F-04 (frontend) — Handle token expiry

| | |
|---|---|
| **Severity** | Medium |
| **OWASP** | A07:2021 Identification and Authentication Failures |
| **Ticket** | SCRUM-49 |
| **Owner** | Frontend developer |
| **Files** | `frontend/src/stores/auth.js`, HTTP client, router |
| **Source** | [`../../owasp-auth-review.md`](../../owasp-auth-review.md) §F-04 |
| **Paired with** | [`../backend/F-04-token-expiration.md`](../backend/F-04-token-expiration.md) |

---

## Why this exists

The backend is setting Sanctum tokens to expire after 24 hours. Right now `'expiration' => null`, so tokens are valid forever — which means the SPA has never had to handle an expired token.

Once the backend change lands, any authenticated request can return **401** at any time. The frontend currently has no path for that.

## What happens if this is skipped

A user leaves a tab open overnight. Next morning every request fails with 401. The app shows empty data, broken pages, or spinners that never resolve, while the UI still believes the user is logged in — because the token is in storage and the auth store says authenticated.

The user has no way out except manually clearing storage. This is worse than the security problem it accompanies, which is why both halves must ship together.

---

## Required behaviour

On **any** 401 response from an authenticated request:

1. Clear the stored token
2. Reset the auth store to unauthenticated
3. Redirect to the login route
4. Optionally show a message — "Your session expired, please sign in again"

Do not retry the request. There is no refresh-token mechanism in this API.

---

## Where to implement

**In the HTTP client interceptor, not in each component.** A per-component approach will miss cases.

If the project uses Axios, the shape is:

```js
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      const auth = useAuthStore()
      auth.logout()          // clears token + user state
      router.push({ name: 'login', query: { expired: '1' } })
    }
    return Promise.reject(error)
  }
)
```

Adjust to whatever `frontend/src/services/` actually uses — check `services/agency.js` for the existing client pattern.

### Careful with the login request itself

A failed login also returns 401. The interceptor must not treat that as an expiry and redirect, or the login page will loop.

Either exclude the login endpoint by URL, or set a flag on that request:

```js
if (error.response?.status === 401 && !error.config.url.includes('/login')) {
```

---

## Store considerations

`frontend/src/stores/auth.js` needs a `logout()` action (or equivalent) that:

- Removes the token from wherever it is stored
- Clears the user object
- Does **not** call `POST /api/logout` in this path — the token is already invalid, so that call would itself 401 and recurse

If the existing `logout()` calls the API, add a local-only variant or guard the API call.

---

## Note on token storage

Out of scope for this ticket, but worth raising with the team:

If the token is held in `localStorage`, any XSS in the SPA yields a usable credential. Token expiration reduces the window from permanent to 24 hours, which is a real improvement — but it does not remove the exposure.

The review flagged this as unassessed because the frontend was outside SCRUM-50's scope. It deserves its own ticket.

---

## Verification

Ask the backend developer to temporarily set `SANCTUM_TOKEN_EXPIRATION=1` (one minute) in their local `.env` so this is testable without waiting 24 hours.

| Step | Expected |
|---|---|
| Log in, wait 90 seconds, navigate to a page that loads data | Redirected to login, storage cleared |
| Log in with wrong credentials | Error message on the login page, **no** redirect loop |
| Log in, click logout normally | Normal logout, no double-redirect |
| Reload the app after expiry-triggered logout | Login page, not a broken authenticated view |
| Log in fresh after expiry | Works normally |

---

## Tests

`frontend/src/stores/__tests__/auth.spec.js` has 18 tests. Add:

- A 401 on an authenticated request clears the store
- A 401 on the login request does **not** clear the store or redirect
- `logout()` in the expiry path does not call `POST /api/logout`

Vitest runs as a blocking CI gate since SCRUM-51, so these tests will protect the behaviour.

---

## Commit

```
SCRUM-49 feat(frontend): handle expired token with logout and redirect (F-04)
```
