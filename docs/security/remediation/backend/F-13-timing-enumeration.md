# F-13 — Timing-based user enumeration at login

| | |
|---|---|
| **Severity** | Low |
| **OWASP** | A07:2021 Identification and Authentication Failures |
| **Ticket** | SCRUM-48 |
| **File** | `app/Http/Controllers/Auth/AuthController.php` |
| **Source** | [`../owasp-auth-review.md`](../owasp-auth-review.md) §F-13 |
| **Depends on** | **F-01 must be implemented first** |

---

## Problem

`AuthController::login()`:

```php
$user = User::where('email', $validated['email'])->first();

if (!$user || !Hash::check($validated['password'], $user->password)) {
    return response()->json([
        'message' => 'Invalid email or password.',
    ], 401);
}
```

PHP short-circuits `||`. When `$user` is null, `Hash::check()` never runs.

`Hash::check()` performs a bcrypt comparison, which takes tens of milliseconds by design. So:

- **Unknown email** → database lookup only → fast
- **Known email, wrong password** → lookup + bcrypt → measurably slower

## Risk

The login endpoint correctly returns an identical error message for both cases — that control is already in place and working. This finding is that the timing difference leaks the same information the message was designed to hide.

An attacker can determine whether any given email has an account by measuring response times, then use that list for credential stuffing or targeted phishing.

Severity is Low because it requires many samples per email and is noisy over a network. But it silently undermines a control the team deliberately implemented.

---

## Change

### `backend/app/Http/Controllers/Auth/AuthController.php`

**Before**

```php
$user = User::where('email', $validated['email'])->first();

if (!$user || !Hash::check($validated['password'], $user->password)) {
    return response()->json([
        'message' => 'Invalid email or password.',
    ], 401);
}
```

**After**

```php
$user = User::where('email', $validated['email'])->first();

// Hash against a dummy value when no user exists so both branches
// perform equivalent work and cannot be distinguished by timing.
// See docs/security/remediation/F-13-timing-enumeration.md
$hash = $user?->password ?? self::DUMMY_HASH;

$passwordValid = Hash::check($validated['password'], $hash);

if (!$user || !$passwordValid) {
    return response()->json([
        'message' => 'Invalid email or password.',
    ], 401);
}
```

Note the ordering: `Hash::check()` is assigned to a variable **before** the conditional, so it always executes. Putting `!$user` first inside the `if` is then safe.

### Add the constant to the class

```php
class AuthController extends Controller
{
    /**
     * A valid bcrypt hash of a value no user can submit, used to keep
     * the failed-login path constant-time. See F-13.
     */
    private const DUMMY_HASH = '$2y$12$abcdefghijklmnopqrstuvwxyz012345678901234567890123456789';
```

**Generate a real hash rather than copying the placeholder above.** Run:

```powershell
docker compose exec backend php artisan tinker
>>> Hash::make(Str::random(64))
```

Copy the output. It must be a syntactically valid bcrypt hash or `Hash::check()` will return early and defeat the purpose.

Using a constant rather than `Hash::make()` at request time matters — `Hash::make()` is roughly as expensive as `Hash::check()`, so calling it per request would add cost without being needed, and would itself introduce a timing difference relative to the found-user path.

---

## Side effects

**Do not implement this without F-01.**

Every failed login for a nonexistent email now costs a full bcrypt operation. Without rate limiting, an attacker can force the server to burn CPU at will — a cheap denial-of-service. With `throttle:5,1` in place, the cost is bounded.

Verify F-01 is merged and active before starting this one.

No test changes expected — the response and status code are unchanged. Run the suite to confirm.

---

## Verification

| Step | Expected |
|---|---|
| Login with a nonexistent email | 401, same message as before |
| Login with a real email and wrong password | 401, same message |
| Login with correct credentials | 200 |
| 50 samples of each failure case, compare mean response time | No consistent difference beyond noise |
| Full test suite | Green |

Quick timing comparison:

```powershell
1..20 | ForEach-Object {
  Measure-Command {
    curl -s -X POST http://localhost:8000/api/login `
      -H "Content-Type: application/json" `
      -d '{"email":"nobody@test.local","password":"x"}'
  } | Select-Object -ExpandProperty TotalMilliseconds
}
```

Run the same against a known-existing email with a wrong password and compare the averages.

---

## Commit

```
SCRUM-48 fix: constant-time login path to prevent timing enumeration (F-13)
```
