# F-07 — Non-string email causes an unhandled server error

| | |
|---|---|
| **Severity** | Medium |
| **OWASP** | A05:2021 Security Misconfiguration |
| **Ticket** | SCRUM-48 |
| **Files** | three FormRequests in `app/Http/Requests/Auth/` |
| **Source** | [`../owasp-auth-review.md`](../owasp-auth-review.md) §F-07 |

---

## Problem

All three auth FormRequests contain:

```php
protected function prepareForValidation(): void
{
    $this->merge([
        'email' => strtolower(trim($this->email)),
    ]);
}
```

`prepareForValidation()` runs **before** validation. At that point `email` is still raw, unvalidated user input.

Submitting `email` as an array or object means `trim()` receives a non-string and throws a `TypeError`.

## Risk

HTTP **500** instead of a 422 validation error, on three public endpoints, triggered by a single malformed field.

With `APP_DEBUG=true` the response includes a full stack trace exposing absolute file paths, Laravel version, PHP version, and vendor directory structure — useful reconnaissance for an attacker choosing an exploit.

It is also a trivially reachable error-handling flaw that any automated scanner will find.

---

## Change

Apply to **all three** files:

- `backend/app/Http/Requests/Auth/LoginRequest.php`
- `backend/app/Http/Requests/Auth/RegisterClientRequest.php`
- `backend/app/Http/Requests/Auth/RegisterAgencyRequest.php`

**Before**

```php
protected function prepareForValidation(): void
{
    $this->merge([
        'email' => strtolower(trim($this->email)),
    ]);
}
```

**After**

```php
protected function prepareForValidation(): void
{
    if (is_string($this->email)) {
        $this->merge([
            'email' => strtolower(trim($this->email)),
        ]);
    }
}
```

If `email` is not a string it is left untouched, and the `'email'` validation rule rejects it normally with a 422.

---

## Also required

Confirm `APP_DEBUG=false` in every non-local environment.

Check:

- `docker-compose.yml` — currently `APP_DEBUG: ${APP_DEBUG:-true}`, so the fallback is `true`
- The production `.env` on the deployment server
- The CD pipeline in `.github/workflows/cd.yaml`

Debug mode on a public endpoint turns every unhandled exception into an information disclosure. This is independent of the code fix and worth verifying regardless.

---

## Side effects

None. The change only adds a type guard; behaviour for valid string input is identical.

---

## Verification

```json
POST /api/login
{ "email": ["attacker@test.local"], "password": "anything" }
```

| Endpoint | Payload | Expected |
|---|---|---|
| `POST /api/login` | `email` as array | **422**, not 500 |
| `POST /api/register/client` | `email` as array | **422** |
| `POST /api/register/agency` | `email` as array | **422** |
| `POST /api/login` | `email` as object `{"a":1}` | **422** |
| `POST /api/login` | `email` as integer `123` | **422** |
| `POST /api/login` | normal valid email with uppercase and spaces | Still normalized, 200/401 as appropriate |

Add a test for the array case in each of the three endpoints. The existing `LoginTest` covers `login normalizes email case and spaces` — put the new assertions alongside it.

---

## Commit

```
SCRUM-48 fix: guard email normalization against non-string input (F-07)
```
