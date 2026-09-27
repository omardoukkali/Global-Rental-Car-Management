# Global Rental Car Management

A multi-tier car rental platform connecting rental agencies and clients, with AI-assisted
vehicle recommendation. Built as a containerized monorepo.

| Service | Technology | Port |
|---|---|---|
| Backend | Laravel 11 / PHP 8.2 (Alpine) — REST API, 70 endpoints | 8000 |
| Frontend | Vue 3 SPA (Vite) | 3000 |
| AI Service | Python FastAPI — SmartDrive recommendation | 5000 |
| Database | PostgreSQL 15 (Alpine) | 5432 |
| Test database | PostgreSQL 15 (Alpine) — used by PHPUnit only | 5434 |

All five services run as Docker containers on a shared bridge network (`app_network`).
Nothing needs to be installed on the host except Docker.

The backend is **API-only**. The Inertia layer was removed; the Vue SPA consumes the REST
API at `/api` and authenticates with Sanctum bearer tokens.

> **Note on the backend runtime.** The base image is `php:8.2-fpm-alpine`, but PHP-FPM is
> never started — the container runs `php artisan serve`. This matters for configuration
> (see *Environment variables*) and for deployment (see *Known limitations*).

---

## Prerequisites

| Requirement | Minimum | Notes |
|---|---|---|
| Docker Desktop | 4.30+ | Includes Docker Engine and Compose v2 |
| RAM | 8 GB | 16 GB recommended |
| Disk space | 10 GB free | Images total roughly 1.5 GB plus build cache |
| Git | 2.40+ | — |
| OS | Windows 10/11, macOS 12+, modern Linux | WSL 2 backend required on Windows |

PHP, Node.js, Composer and PostgreSQL are **not** required on the host — every build step
runs inside a container.

---

## Quick start

```bash
git clone https://github.com/omardoukkali/Global-Rental-Car-Management.git
cd Global-Rental-Car-Management

cp .env.example .env
docker compose up -d --build
docker compose ps
```

First build takes 5–10 minutes. You should see five containers `Up`, with `app_database`
and `app_database_test` marked `(healthy)`.

### Service URLs

| What | URL |
|---|---|
| Frontend SPA | http://localhost:3000 |
| REST API base | http://localhost:8000/api |
| Backend health check | http://localhost:8000/up |
| AI service (Swagger UI) | http://localhost:5000/docs |
| PostgreSQL | localhost:5432 |
| PostgreSQL (test) | localhost:5434 |

`/api` is a route prefix, not an endpoint — opening it returns 404. Try
`http://localhost:8000/api/cities`.

Stop everything with `docker compose down`.

---

## Why there are two databases

`app_database` holds development data. `app_database_test` is a disposable instance used
only by PHPUnit.

Laravel feature tests use `RefreshDatabase`, which drops every table and re-runs migrations
on each run. Without the split, running the suite would wipe all development data. The
routing is configured in `backend/phpunit.xml` with `force="true"` overrides, so
`php artisan serve` reaches `database` and `php artisan test` reaches `database_test`. No
manual switching.

---

## What happens on first boot

`backend/docker-entrypoint.sh` handles setup in this order:

1. Fixes storage directory permissions
2. Recreates the `public/storage` symlink
3. Generates an ephemeral `APP_KEY` if none is supplied
4. **Writes `backend/.env` from the container environment**
5. Retries `php artisan migrate --force` until PostgreSQL accepts connections
6. Seeds the database on first boot only, guarded by `storage/app/seeder.lock`
7. Starts `php artisan schedule:work` in the background
8. Starts the application server

### Why step 4 exists

`php artisan serve` spawns a child `php -S` process that handles every HTTP request, and
**that child does not inherit the container environment**. Verified via `/proc/<pid>/environ`:
the wrapper process has the full environment, the request-handling process has only
`APP_ENV`. Every variable `docker-compose.yml` injects is invisible to the code that
actually serves requests, so `env('DB_CONNECTION')` returns null and Laravel falls back to
its built-in SQLite default.

The entrypoint therefore writes `.env` from the container environment at startup.
`backend/.env` is generated, never edited by hand, and gitignored. Change values in the
root `.env`, not in `backend/.env`.

This constraint disappears if the backend moves to PHP-FPM, which passes environment to its
workers correctly.

> **`APP_KEY`.** `docker-compose.yml` supplies a hardcoded fallback when `APP_KEY` is unset.
> Acceptable for local development, never for a deployed environment:
>
> ```bash
> docker compose exec backend php artisan key:generate --show
> # paste into APP_KEY= in your .env, then:
> docker compose restart backend
> ```

---

## Seeded development accounts

Password for every seeded account: `password`

| Email | Role |
|---|---|
| admin@example.com | admin |
| client@example.com | client |
| agency@example.com | agency — Demo Rent Cars, Tangier, approved |
| hassan@agency.ma | agency — Atlas Cars, Casablanca, approved |

The seeders also create 29 generated clients, 10 further agencies (one `pending`, one
`rejected`), cars with images, agency points, and reservations in every status with their
payments, refunds and reviews.

Seeder order, defined in `DatabaseSeeder`:

```
City → User → Agency → AgencyPoint → Car → CarImage → Reservation → Payment → Refund → Review
```

**Development credentials only.** To reseed from scratch:

```bash
docker compose exec backend php artisan migrate:fresh --seed
```

---

## Environment variables

Copy `.env.example` to `.env` at the repository root. Compose reads that file and passes
the values into the containers; the backend entrypoint writes them into `backend/.env`.

| Variable | Description | Default |
|---|---|---|
| `APP_NAME` | Application display name | Global Rental Car |
| `APP_ENV` | Environment | local |
| `APP_KEY` | Laravel encryption key | see note above |
| `APP_DEBUG` | Verbose error pages | true |
| `APP_URL` | Backend base URL | http://localhost:8000 |
| `FRONTEND_URL` | Address used in e-mail links | http://localhost:3000 |
| `FRONTEND_PORT` | Host port for the SPA | 3000 |
| `BACKEND_PORT` | Host port for Laravel | 8000 |
| `AI_PORT` | Host port for FastAPI | 5000 |
| `DB_PORT` | Host port for PostgreSQL | 5432 |
| `DB_PORT_TEST` | Host port for the test database | 5434 |
| `DB_CONNECTION` | Database driver | pgsql |
| `DB_HOST` | Database hostname (compose service name) | database |
| `DB_DATABASE` | Database name | globalrental |
| `DB_DATABASE_TEST` | Test database name | globalrental_test |
| `DB_USERNAME` | Database user | grader |
| `DB_PASSWORD` | Database password | secret |
| `DB_SSLMODE` | PostgreSQL SSL mode (required by Azure) | prefer |
| `AI_SERVICE_URL` | Internal AI service address | http://ai_service:5000 |
| `CORS_ALLOWED_ORIGINS` | Comma-separated origins allowed to call the API | http://localhost:3000,http://localhost:3333 |
| `SANCTUM_TOKEN_EXPIRATION` | Token lifetime in minutes | 1440 |
| `SANCTUM_TOKEN_PREFIX` | Token prefix, so secret scanners can detect leaks | grcm_ |
| `MAIL_MAILER` | `log` (written to the log file) or `smtp` (really sent) | log |
| `MAIL_HOST` / `MAIL_PORT` | SMTP server | — / 587 |
| `MAIL_USERNAME` / `MAIL_PASSWORD` | SMTP credentials | — |
| `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` | Sender address and name | no-reply@globalrental.local |

`.env` is gitignored and must never be committed.

### Sending real e-mails

By default `MAIL_MAILER=log`: no e-mail leaves the machine, the message is written to
`backend/storage/logs/laravel.log` — search for `reset-password?token=` or `email/verify`.

To really send them, add SMTP settings to your `.env` and restart the backend:

```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=<SMTP login>
MAIL_PASSWORD=<SMTP key>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=<a verified sender>
MAIL_FROM_NAME="Global Rental Car"
```

Ask the team for the shared credentials — **never commit them**. The CI secret scan blocks
any push containing them.

---

## Running the tests

Both suites run in CI as blocking gates. Run them locally before pushing.

```bash
# Backend — PHPUnit, 195 tests (~2 minutes)
docker compose exec backend php artisan test

# Backend — a single suite
docker compose exec backend php artisan test --filter=Auth

# Frontend — Vitest, 99 tests across 16 files
docker compose exec frontend npm run test:run
```

Use `npm run test:run`, not `npm test` — the latter starts watch mode and never exits.

---

## Useful commands

```bash
# Follow the logs of one service
docker compose logs -f backend

# Shell into a container
docker compose exec backend sh

# List all API routes
docker compose exec backend php artisan route:list --path=api

# Rebuild the database with seed data
docker compose exec backend php artisan migrate:fresh --seed

# Rebuild a single service
docker compose build --no-cache backend

# Full reset (destroys both databases and all volumes)
docker compose down -v && docker compose up -d --build
```

---

## Troubleshooting

**Backend logs loop on "Database not ready, retrying in 2s"**
Normal for the first 10–20 seconds. If it persists beyond a minute, check
`docker compose logs database`.

**`ERR_EMPTY_RESPONSE` on port 8000**
The container is still booting — migrations and seeding run before the server starts. Wait
for `Server running on [http://0.0.0.0:8000]`.

**An endpoint returns an empty array**
The query succeeded and matched nothing. Usually the database was wiped by
`docker compose down -v` while `storage/app/seeder.lock` survived, so seeding was skipped.
Run `php artisan migrate:fresh --seed`.

**Frontend tests pass locally but fail in CI**
The `node_modules` volume is stale. `docker compose down`, then `docker volume prune -f`,
then `docker compose build --no-cache frontend`. The Dockerfile uses `npm ci` against the
lockfile, so a clean build matches CI exactly.

**Tests fail to connect to the database**
Confirm `app_database_test` is healthy with `docker compose ps`. The suite connects to
`database_test`, not `database`.

**Port already in use**
Change the relevant `*_PORT` value in `.env` and restart.

---

## Repository structure

```
.
├── .github/workflows/     CI and CD pipeline definitions
├── AI/                    Python FastAPI service + ML training data
├── backend/               Laravel 11 REST API
├── frontend/              Vue 3 SPA
├── docs/
│   ├── api/               SmartDrive endpoint documentation
│   ├── deployment/        Azure provisioning and deployment guide
│   ├── postman/           API collection and environment
│   └── security/          Security review, remediation, reference docs
├── UML/                   Design and modelling artefacts
├── docker-compose.yml     Service orchestration
├── .env.example           Environment variable template
└── README.md
```

---

## Branching and contribution workflow

| Branch | Role |
|---|---|
| `main` | Validated, deployable version. Receives merges only from `develop`. |
| `develop` | Integration branch. |
| `feature/**` | New functionality |
| `infra/**` | Infrastructure, Docker, CI/CD |
| `refactor/**` | Restructuring without behavioural change |
| `hotfix/**` | Urgent corrections |

`main` and `develop` are protected by GitHub Rulesets:

- Direct pushes blocked — all changes arrive via pull request
- One approving review required (two on `main`)
- Stale approvals dismissed when new commits are pushed
- `Build, Test & Security Scan` must pass before merge
- Branches must be up to date before merging
- Force pushes and branch deletions blocked

### Naming and commit conventions

Branch names carry the Jira key so the integration links them automatically:

```
infra/SCRUM-24-dockerize-monorepo-services
feature/SCRUM-31-reservation-payment-flow
```

Commit messages begin with the Jira key, then a Conventional Commits type:

```
SCRUM-24 infra: add multi-stage Node build for Vite assets
SCRUM-51 ci: run PHPUnit and Vitest as blocking gates
SCRUM-27 fix: untrack .env and add example template
```

Recognised prefixes: `feat:`, `fix:`, `infra:`, `ci:`, `refactor:`, `docs:`, `test:`,
`chore:`.

### Opening a pull request

1. Branch from `develop`
2. Commit and push — CI runs on every push
3. Open a pull request into `develop`, with the issue key(s) in the title
4. Wait for CI to pass and for one approving review
5. Merge

---

## Continuous Integration

`.github/workflows/ci.yaml` — a single job, `build-test-security`, running on every push to
a permanent or prefixed branch and on every pull request into `develop` or `main`.

**Build verification**

1. Checkout with full git history
2. Build all three application images
3. Start the full stack
4. Assert the backend (`/up`) and AI service (`/docs`) respond to HTTP probes
5. `composer validate --strict`

**Security scanning**

6. TruffleHog — credentials in source and full git history
7. Semgrep — static analysis of PHP and JavaScript against the OWASP Top 10 ruleset
8. Trivy — CVEs in all three built images
9. Trivy — filesystem scan with `vuln,secret,misconfig` scanners
10. Two JSON scan reports uploaded as a build artifact

**Testing**

11. PHPUnit — 195 backend tests
12. Vitest — 99 frontend tests

### Security scanning coverage

| Category | Tool | Examines |
|---|---|---|
| Secret detection | TruffleHog | Credentials in source and git history |
| SAST | Semgrep | OWASP Top 10 patterns in our own PHP and JS |
| SCA | Trivy | Known vulnerabilities in dependencies |
| Container scanning | Trivy | Vulnerabilities and misconfiguration in built images |

### What blocks a merge

| Step | Blocking |
|---|---|
| Image build and stack startup | Yes |
| Health probes | Yes |
| `composer validate --strict` | Yes |
| PHPUnit | Yes |
| Vitest | Yes |
| TruffleHog | Yes |
| Semgrep | **No** — report only |
| Trivy (all invocations) | **No** — report only |

Trivy runs with `exit-code: 0` and Semgrep with `continue-on-error`. Alpine and PHP base
images routinely carry unfixable CVEs that would otherwise prevent every merge, and the
initial SAST findings are still being triaged. **These are deliberate trade-offs, not
oversights** — making both blocking is an open hardening task.

### Retrieving a scan report

- **Readable tables** — expand any `Trivy scan —` or `Semgrep` step in the job log
- **JSON reports** — at the bottom of the run summary page, under **Artifacts**, named
  `trivy-reports-<timestamp>`. Retained for 14 days.

---

## Security

Documentation lives under `docs/security/`.

| Document | Purpose |
|---|---|
| [`authentication-security.md`](docs/security/authentication-security.md) | How authentication and authorization work, controls in place, accepted risks |
| [`owasp-auth-review.md`](docs/security/owasp-auth-review.md) | OWASP Top 10 (2021) review of the authentication module — 15 findings |
| [`remediation/`](docs/security/remediation/) | Per-finding implementation instructions, split by component |

### Implemented controls

| Control | Implementation |
|---|---|
| Rate limiting | Login, registration, password reset, e-mail verification |
| Session revocation | Account status verified on every request; tokens revoked on failure |
| Privilege escalation | `role` and `status` excluded from mass assignment |
| Token lifetime | Expiry via `SANCTUM_TOKEN_EXPIRATION` |
| Token scope | Tokens carry an ability naming the account role |
| Cross-origin access | Restricted to `CORS_ALLOWED_ORIGINS` |
| Input handling | Type-guarded normalisation in the auth FormRequests |
| Password strength | Breached-password corpus check at registration |
| Credential leak detection | `SANCTUM_TOKEN_PREFIX` so secret scanners can match |

### Not yet reviewed

The authentication module has been reviewed. These have not:

- **Object-level authorization** on agency, car, reservation, payment and review resources.
  Role middleware proves *what kind of user* is calling, not *whose resource* they touch.
- **Frontend token storage** — if tokens sit in `localStorage`, XSS yields a credential.
- **The AI service** — `/docs` is served publicly with no authentication. See
  `docs/security/remediation/ai/README.md`.
- **Container hardening** — all three images run as root with floating base image tags.

### Dependency vulnerabilities

Current figures are in the latest CI run's `trivy-reports-<timestamp>` artifact. A static
table here goes stale within a sprint, so it is not reproduced.

One finding worth naming: `laravel/framework` carries a CRLF injection in email validation
(CVSS 8.9) fixed only in Laravel 12. That is a major-version upgrade, deferred pending a
planned migration.

---

## Deployment

`.github/workflows/cd.yaml` deploys to **Azure Container Apps**.

It triggers on `workflow_run` when the CI Pipeline completes successfully on `main`, and can
be run manually via `workflow_dispatch`. **Deployment is gated on CI passing.**

1. Checks out the exact commit CI verified — not the branch tip
2. Builds and pushes `grc-api`, `grc-frontend` and `grc-ai` to Docker Hub, tagged with the
   short commit SHA
3. Authenticates to Azure via OIDC federated identity — no stored cloud credential
4. Records the current active revision of each container app
5. Runs `az containerapp update` for each app
6. Health-checks the API `/up` and the frontend
7. Rolls back via `az containerapp revision activate` if a check fails

The AI service has internal-only ingress and is deployed but not health-checked from the
runner.

**Required repository secrets:** `DOCKERHUB_TOKEN`, `AZURE_CLIENT_ID`, `AZURE_TENANT_ID`,
`AZURE_SUBSCRIPTION_ID`. The three Azure values are identifiers, not credentials —
authentication uses a short-lived OIDC token restricted by subject claim to this repository
and branch.

**Azure resources:** resource group `rg-globalrentalcar`, France Central. PostgreSQL
Flexible Server plus a Container Apps environment. Full provisioning steps in
[`docs/deployment/deployment.md`](docs/deployment/deployment.md).

### Known limitations

- **`php artisan serve` is a single-threaded development server.** Production needs nginx or
  Apache in front of PHP-FPM. This also forces the `.env` generation described above.
- **The frontend image runs `npm run dev`** — the Vite dev server, not a production build.
  No minification, no asset hashing.
- **No queue worker runs.** `QUEUE_CONNECTION=database` is configured but nothing executes
  `queue:work`, so queued jobs — including verification and password-reset e-mails —
  accumulate in the `jobs` table unprocessed.
- **All three containers run as root.**
- **Trivy and Semgrep findings do not block deployment.**