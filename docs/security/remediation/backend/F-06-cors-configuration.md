# F-06 — CORS is unrestricted

| | |
|---|---|
| **Severity** | Medium |
| **OWASP** | A05:2021 Security Misconfiguration |
| **Ticket** | SCRUM-48 |
| **File** | `backend/config/cors.php` — **does not exist** |
| **Source** | [`../owasp-auth-review.md`](../owasp-auth-review.md) §F-06 |

---

## Problem

There is no `config/cors.php` in the repository. Laravel 11 applies the `HandleCors` middleware to API routes by default, so with no published config the framework default applies:

```php
'allowed_origins' => ['*']
```

Any origin on the internet can call the API.

## Risk

Because the SPA authenticates with bearer tokens rather than cookies, this is **not** directly exploitable as CSRF — a malicious page cannot ride an existing session.

The risk is the removal of a control layer:

- A malicious page can freely interact with the API using a token obtained by other means (XSS, a leaked token, a shared device)
- The API is available for use by any third-party site
- If cookie-based auth is ever introduced, this becomes an immediate CSRF vector

---

## Change

### 1. Create `backend/config/cors.php`

```php
<?php

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => explode(',', env(
        'CORS_ALLOWED_ORIGINS',
        'http://localhost:3000'
    )),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
```

### 2. Add to `.env.example` and `backend/.env.example`

```
CORS_ALLOWED_ORIGINS=http://localhost:3000
```

### 3. Set the real value per environment

For production, the deployed frontend origin. Multiple origins are comma-separated:

```
CORS_ALLOWED_ORIGINS=https://app.example.com,https://admin.example.com
```

---

## Rules

- **Never** set `allowed_origins` to `['*']` together with `supports_credentials: true`. Browsers reject the combination, and configuring it signals a misunderstanding of the model.
- `supports_credentials: false` is correct here because the API is stateless bearer-token based. Do not change it without discussing the auth model.
- Keep `paths` scoped to `api/*`. There is no reason to apply CORS to the `web.php` welcome route.

---

## Side effects

**If the frontend runs on a port other than 3000 in any environment, it will break.** Check:

- `docker-compose.yml` — `FRONTEND_PORT` defaults to 3000
- Any teammate running Vite on a different port locally
- The deployed frontend URL used by the CD pipeline

Add every origin the team actually uses to the default, or document that each developer sets `CORS_ALLOWED_ORIGINS` in their local `.env`.

This does not affect tests — PHPUnit requests do not send an `Origin` header.

---

## Verification

| Step | Expected |
|---|---|
| `OPTIONS /api/login` with `Origin: http://localhost:3000` | `Access-Control-Allow-Origin: http://localhost:3000` |
| `OPTIONS /api/login` with `Origin: https://evil.test` | Origin **not** reflected in the response |
| Frontend at localhost:3000 logs in normally | Works |
| `curl -H "Origin: https://evil.test" -X OPTIONS http://localhost:8000/api/login -i` | No allow-origin for that host |

---

## Commit

```
SCRUM-48 fix: restrict CORS to configured origins (F-06)
```
