# F-02 — Suspended users retain valid tokens indefinitely

| | |
|---|---|
| **Severity** | High |
| **OWASP** | A01:2021 Broken Access Control |
| **Ticket** | SCRUM-49 |
| **Files** | new middleware, `bootstrap/app.php`, `routes/api.php` |
| **Source** | [`../owasp-auth-review.md`](../owasp-auth-review.md) §F-02 |

---

## Problem

`AuthController::login()` rejects a user whose `status` is `suspended`:

```php
if ($user->status === 'suspended') {
    return response()->json([
        'message' => 'Your account has been suspended.',
    ], 403);
}
```

That check runs **only at login**. Once a token has been issued, no middleware re-reads `status` on subsequent requests.

## Risk

An administrator suspends an account, but the account keeps full access to every authenticated route. Because tokens never expire (F-04), that access never ends.

Account suspension is currently non-functional against anyone already logged in. This is the highest-impact finding in the review — it turns an existing administrative control into a no-op.

---

## Change

### 1. Create `backend/app/Http/Middleware/EnsureUserIsActive.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Reject requests from users whose account is no longer active,
     * and revoke their tokens so the next request fails at auth.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if ($user && $user->status !== 'active') {
            $user->tokens()->delete();

            return response()->json([
                'message' => 'Your account is not active.',
            ], 403);
        }

        return $next($request);
    }
}
```

Checking `!== 'active'` rather than `=== 'suspended'` is deliberate — any future status value (`pending`, `banned`, `deleted`) is handled without another code change.

### 2. Register the alias in `backend/bootstrap/app.php`

**Before**

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \App\Http\Middleware\RoleMiddleware::class,
        'agency.approved' => \App\Http\Middleware\EnsureAgencyIsApproved::class,
    ]);
})
```

**After**

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \App\Http\Middleware\RoleMiddleware::class,
        'agency.approved' => \App\Http\Middleware\EnsureAgencyIsApproved::class,
        'user.active' => \App\Http\Middleware\EnsureUserIsActive::class,
    ]);
})
```

### 3. Apply it in `backend/routes/api.php`

Add `user.active` to **every** group that uses `auth:sanctum`. There are five:

```php
// 1. Authenticated routes (/me, /logout)
Route::middleware(['auth:sanctum', 'user.active'])->group(...);

// 2. Client routes
Route::middleware(['auth:sanctum', 'user.active', 'role:client'])->group(...);

// 3. Agency routes
Route::middleware(['auth:sanctum', 'user.active', 'role:agency'])->group(...);

// 4. Approved agency routes
Route::middleware([
    'auth:sanctum',
    'user.active',
    'role:agency',
    'agency.approved',
])->group(...);

// 5. Admin routes
Route::middleware(['auth:sanctum', 'user.active', 'role:admin'])->group(...);
```

**Missing one group leaves a hole.** Count them after editing.

### 4. Revoke tokens at the point of suspension

Wherever an admin sets a user to `suspended`, add:

```php
$user->tokens()->delete();
```

The middleware is the safety net. This is the primary fix — it makes suspension take effect immediately rather than on the user's next request.

---

## Side effects

Adding a middleware to five route groups means every authenticated test now passes through it. Tests using `User::factory()` must produce users with `status: 'active'`, or every authenticated test returns 403.

Check `UserFactory` — if `status` has no default, add one.

---

## Verification

| Step | Expected |
|---|---|
| 1. Log in as a client, keep the token | 200, token returned |
| 2. Set that user's `status` to `suspended` in the database | — |
| 3. `GET /api/me` with the same token | **403** |
| 4. Repeat the same request | **401** (tokens revoked in step 3) |
| 5. Repeat steps 1–3 for an agency and an admin token | 403 each |

---

## Note

Pair this with **F-04** (token expiration). Expiration bounds how long a stolen token is useful; this check bounds how long a revoked *account* is useful. Fixing only one leaves a partial gap.

## Commit

```
SCRUM-49 feat: reject non-active users on authenticated routes (F-02)
```
