# F-01 — No rate limiting on authentication endpoints

| | |
|---|---|
| **Severity** | High |
| **OWASP** | A07:2021 Identification and Authentication Failures |
| **Ticket** | SCRUM-48 |
| **File** | `backend/routes/api.php` |
| **Source** | [`../owasp-auth-review.md`](../owasp-auth-review.md) §F-01 |

---

## Problem

No `throttle` middleware on `POST /login`, `POST /register/client`, or `POST /register/agency`. No global API throttle is registered in `bootstrap/app.php` either.

## Risk

Unlimited password guessing against `/login`. The current password policy permits 8 characters with only letters and numbers, so online brute force against common passwords is viable — no offline hash cracking needed.

The registration endpoints can also be used to mass-create accounts or flood the database.

---

## Change

### `backend/routes/api.php`

**Before**

```php
Route::prefix('register')->group(function () {

    Route::post('/client', [
        AuthController::class,
        'registerClient'
    ]);

    Route::post('/agency', [
        AuthController::class,
        'registerAgency'
    ]);
});

Route::post('/login', [
    AuthController::class,
    'login'
]);
```

**After**

```php
Route::prefix('register')
    ->middleware('throttle:3,1')
    ->group(function () {

        Route::post('/client', [
            AuthController::class,
            'registerClient'
        ]);

        Route::post('/agency', [
            AuthController::class,
            'registerAgency'
        ]);
    });

Route::post('/login', [
    AuthController::class,
    'login'
])->middleware('throttle:5,1');
```

`throttle:5,1` means 5 requests per 1 minute, keyed by IP.

Registration gets a tighter limit (3) because a legitimate user registers once.

---

## Side effects

**This will break existing tests.** `LoginTest` chains several failed-login assertions inside single test methods. They will trip the throttle and return 429 instead of 401.

Disable the limiter in the testing environment. In `backend/app/Providers/AppServiceProvider.php`, inside `boot()`:

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

if (app()->environment('testing')) {
    RateLimiter::for('api', fn () => Limit::none());
}
```

Then write **one** dedicated test that clears the limiter and asserts the 429, so the control is still covered by CI. Use `RateLimiter::clear()` in that test's setup.

---

## Verification

| Step | Expected |
|---|---|
| 6 rapid `POST /api/login` with wrong credentials from one IP | 6th returns **429** |
| 4 rapid `POST /api/register/client` | 4th returns **429** |
| Wait 60s, retry login | 401 again, not 429 |
| Full test suite | Green |

---

## Note

**Implement this before F-13.** F-13 adds a bcrypt hash operation on every failed login for a nonexistent email. Without rate limiting, that is a CPU denial-of-service vector.

## Commit

```
SCRUM-48 feat: rate limit authentication endpoints (F-01)
```
