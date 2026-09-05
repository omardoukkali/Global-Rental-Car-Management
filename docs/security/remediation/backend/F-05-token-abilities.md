# F-05 — Tokens are issued with unrestricted abilities

| | |
|---|---|
| **Severity** | Medium |
| **OWASP** | A01:2021 Broken Access Control |
| **Ticket** | SCRUM-49 |
| **File** | `app/Http/Controllers/Auth/AuthController.php` |
| **Source** | [`../owasp-auth-review.md`](../owasp-auth-review.md) §F-05 |

---

## Problem

`AuthController::login()`:

```php
$token = $user->createToken('auth_token')->plainTextToken;
```

No abilities array is passed, so Sanctum defaults to `['*']`. Every token can do everything, and `tokenCan()` always returns `true` regardless of argument.

## Risk

No defence in depth. Authorization rests entirely on `RoleMiddleware` being present on every route. A single route added without its middleware declaration means full access for any authenticated user — a client token would reach admin endpoints.

Given the route file is growing (25 routes now, from 5 at the last review), the chance of a missed middleware declaration is not negligible.

It also blocks ever issuing limited-scope tokens — mobile app, third-party integration, read-only — without reworking login.

---

## Change

### `backend/app/Http/Controllers/Auth/AuthController.php`

**Before**

```php
$token = $user->createToken('auth_token')->plainTextToken;
```

**After**

```php
$token = $user->createToken(
    'auth_token',
    ["role:{$user->role}"]
)->plainTextToken;
```

Each token gets exactly one ability naming its role: `role:client`, `role:agency`, or `role:admin`.

---

## Optional follow-up — discuss before implementing

Once abilities exist, `RoleMiddleware` can check the token in addition to the user record:

```php
public function handle(Request $request, Closure $next, ...$roles): Response
{
    $user = $request->user();

    if (!$user || !in_array($user->role, $roles, true)) {
        return response()->json([
            'message' => 'You are not authorized to access this resource.',
        ], 403);
    }

    if (!$request->user()->tokenCan("role:{$user->role}")) {
        return response()->json([
            'message' => 'Token scope does not match account role.',
        ], 403);
    }

    return $next($request);
}
```

This catches the case where a user's role changes after their token was issued — the token still claims the old role and gets rejected.

**Do not implement this without discussing it first.** It interacts with F-02, and it will invalidate every token issued before F-05 lands, because those have `["*"]` and no `role:` ability. That is a forced logout for all users.

---

## Side effects

Tokens created directly in tests via `createToken('test')` will have no `role:` ability. If the optional `RoleMiddleware` change above is implemented, those tests break. Without it, they are unaffected.

The base change (adding abilities at login) is backward-compatible on its own — nothing reads the abilities yet.

---

## Verification

| Step | Expected |
|---|---|
| Log in as a client, inspect `abilities` in `personal_access_tokens` | `["role:client"]` |
| Log in as an agency | `["role:agency"]` |
| Log in as an admin | `["role:admin"]` |
| No token should show | `["*"]` |
| Full test suite | Green |

---

## Commit

```
SCRUM-49 feat: issue role-scoped token abilities (F-05)
```
