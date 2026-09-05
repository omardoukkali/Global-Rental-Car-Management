# F-12 — Weak password policy

| | |
|---|---|
| **Severity** | Low |
| **OWASP** | A07:2021 Identification and Authentication Failures |
| **Ticket** | SCRUM-48 |
| **Files** | `RegisterClientRequest.php`, `RegisterAgencyRequest.php` |
| **Source** | [`../owasp-auth-review.md`](../owasp-auth-review.md) §F-12 |

---

## Problem

Both registration FormRequests use:

```php
Password::min(8)
    ->letters()
    ->numbers(),
```

This accepts `password1`, `abcd1234`, `qwerty12`, `football1` — all of which appear in the top ranks of every breached-password list.

## Risk

A07 explicitly cites "permits weak or well-known passwords". Combined with F-01 (no rate limiting), the most common passwords are guessable online in a handful of attempts.

Rate limiting reduces this substantially, which is why the severity is Low rather than Medium — but it does not remove it. A patient attacker at 5 attempts per minute still gets 7,200 attempts per day per IP.

---

## Change

Apply to **both** files:

- `backend/app/Http/Requests/Auth/RegisterClientRequest.php`
- `backend/app/Http/Requests/Auth/RegisterAgencyRequest.php`

**Before**

```php
'password' => [
    'required',
    'confirmed',
    Password::min(8)
        ->letters()
        ->numbers(),
],
```

**After**

```php
'password' => [
    'required',
    'confirmed',
    Password::min(8)
        ->letters()
        ->numbers()
        ->uncompromised(),
],
```

`uncompromised()` checks the password against the Have I Been Pwned corpus. It uses k-anonymity — only the first 5 characters of the SHA-1 hash are sent, never the password itself. This is safe to use.

### Optionally also raise the minimum

```php
Password::min(10)
```

Discuss with the team. Longer minimums reduce weak passwords more than character-class rules do, but it is a UX decision, not purely a security one.

---

## Side effects

**`uncompromised()` makes an outbound HTTP call to the HIBP API on every registration.**

This affects tests directly. Any test that registers a user will hit the network — slow, flaky, and it will fail entirely in a CI runner without egress to `api.pwnedpasswords.com`.

Two options:

**Option A — skip the check in testing**

```php
Password::min(8)
    ->letters()
    ->numbers()
    ->when(
        !app()->environment('testing'),
        fn ($rule) => $rule->uncompromised()
    ),
```

**Option B — fake the HTTP call in `TestCase::setUp()`**

```php
Http::fake([
    'api.pwnedpasswords.com/*' => Http::response('', 200),
]);
```

Option B is better because the rule stays active in tests. Option A is simpler. Either is acceptable — pick one and note it in the commit.

Keep **one** test that asserts a known-breached password is rejected, so the control is actually verified in CI. With Option A that test needs the environment check bypassed; with Option B, fake a matching HIBP response.

Also check: existing test fixtures using passwords like `password123` will start failing. Grep the test suite for password literals.

---

## Verification

| Step | Expected |
|---|---|
| Register with `password1` | **422** with a compromised-password message |
| Register with `abcd1234` | **422** |
| Register with a strong random password | 201 |
| Register with 7 characters | **422** (existing `min:8`) |
| Full test suite | Green |
| CI run | Green — confirm no network timeouts |

---

## Note

If the team decides the HIBP dependency is unacceptable in CI, an alternative is a local denylist of the top 10,000 passwords via a custom rule. More work, no network dependency. Raise with Nizar if Option A and B both feel wrong.

## Commit

```
SCRUM-48 feat: reject compromised passwords at registration (F-12)
```
