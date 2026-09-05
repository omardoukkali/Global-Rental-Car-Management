# Global Rental Car Management

A multi-tier car rental management platform built as a containerized monorepo.

| Service | Technology | Port |
|---|---|---|
| Backend | Laravel 11 / PHP 8.2-FPM (Alpine) — REST API | 8000 |
| Frontend | Vue 3 SPA (Vite dev server) | 3000 |
| AI Service | Python FastAPI | 5000 |
| Database | PostgreSQL 15 (Alpine) | 5432 |
| Test database | PostgreSQL 15 (Alpine) — isolated, used by PHPUnit only | 5434 |

All five services run as Docker containers on a shared bridge network (`app_network`),
orchestrated with Docker Compose. Nothing needs to be installed on the host except Docker.

The backend is **API-only**. The Inertia layer was removed — the Vue SPA consumes the
REST API at `/api` and authenticates with Sanctum bearer tokens.

---

## Prerequisites

| Requirement | Minimum | Notes |
|---|---|---|
| Docker Desktop | 4.30+ | Includes Docker Engine and Compose v2 |
| RAM | 8 GB | 16 GB recommended; the stack allocates ~4 GB under load |
| Disk space | 10 GB free | Images total roughly 1.5 GB plus build cache |
| Git | 2.40+ | — |
| OS | Windows 10/11, macOS 12+, or any modern Linux | WSL 2 backend required on Windows |

PHP, Node.js, Composer, and PostgreSQL are **not** required on the host — every
build step runs inside a container.

---

## Quick start

```bash
# 1. Clone the repository
git clone https://github.com/omardoukkali/Global-Rental-Car-Management.git
cd Global-Rental-Car-Management

# 2. Create your environment file from the template
cp .env.example .env

# 3. Build and start all services
docker compose up -d --build

# 4. Confirm everything is healthy
docker compose ps
```

The first build takes 5–10 minutes: it pulls the PHP, Node, Python, and Postgres
base images and installs Composer and npm dependencies.

You should see five containers with status `Up`, with `app_database` and
`app_database_test` marked `(healthy)`.

### Service URLs

| What | URL |
|---|---|
| REST API | http://localhost:8000/api |
| Backend health check | http://localhost:8000/up |
| Frontend SPA | http://localhost:3000 |
| AI service (Swagger UI) | http://localhost:5000/docs |
| PostgreSQL | localhost:5432 |
| PostgreSQL (test) | localhost:5434 |

To stop everything: `docker compose down`

---

## Why there are two databases

`app_database` holds development data. `app_database_test` is a separate,
disposable database used only by the PHPUnit suite.

Laravel feature tests use `RefreshDatabase`, which drops every table and re-runs
migrations on each test run. Without the split, running the test suite would wipe
all development data — registered agencies, cars, and the seeded admin account.

The routing is configured in `backend/phpunit.xml`:

```xml
<env name="DB_HOST" value="database_test" force="true"/>
<env name="DB_DATABASE" value="globalrental_test" force="true"/>
```

`force="true"` means these override `.env`. So `php artisan serve` reaches
`database`, and `php artisan test` reaches `database_test`. No manual switching.

---

## What happens automatically on first boot

The backend container's entrypoint (`backend/docker-entrypoint.sh`) handles setup
so no manual steps are needed:

1. Fixes storage directory permissions
2. Creates the `public/storage` symlink
3. Generates an `APP_KEY` if none is set
4. Waits for PostgreSQL, then runs migrations
5. Seeds the database on first boot only (guarded by a lock file)
6. Starts the application server

> **Note on `APP_KEY`.** `docker-compose.yml` currently supplies a hardcoded
> fallback key when `APP_KEY` is unset in `.env`. The app therefore boots with a
> shared, publicly visible key — acceptable for local development, never for a
> deployed environment. Set a real key:
>
> ```bash
> docker compose exec backend php artisan key:generate --show
> # paste the output into APP_KEY= in your .env, then:
> docker compose restart backend
> ```

---

## Seeded development accounts

The seeder creates test users for local development.

| Email | Role |
|---|---|
| admin@test.com | admin |
| owner@test.com | agency |
| client@test.com | client |

These are **development credentials only** and must never exist in a deployed
environment. The seeder is guarded by a lock file and runs on first boot only.

---

## Environment variables

Copy `.env.example` to `.env` and adjust as needed.

| Variable | Description | Default |
|---|---|---|
| `APP_NAME` | Application display name | Global Rental Car |
| `APP_ENV` | Environment | local |
| `APP_KEY` | Laravel encryption key | see note above |
| `APP_DEBUG` | Verbose error pages | true |
| `APP_URL` | Base URL | http://localhost:8000 |
| `FRONTEND_PORT` | Host port for the Vue SPA | 3000 |
| `BACKEND_PORT` | Host port for Laravel | 8000 |
| `AI_PORT` | Host port for FastAPI | 5000 |
| `DB_PORT` | Host port for PostgreSQL | 5432 |
| `DB_PORT_TEST` | Host port for the test database | 5434 |
| `DB_CONNECTION` | Laravel database driver | pgsql |
| `DB_HOST` | Database hostname (compose service name) | database |
| `DB_DATABASE` | Database name | globalrental |
| `DB_DATABASE_TEST` | Test database name | globalrental_test |
| `DB_USERNAME` | Database user | grader |
| `DB_PASSWORD` | Database password | — |
| `AI_SERVICE_URL` | Internal AI service address | http://ai_service:5000 |

`.env` is gitignored and must never be committed.

---

## Running the tests

Both suites run in CI as blocking gates. Run them locally before pushing.

```bash
# Backend — PHPUnit, 73 tests
docker compose exec backend php artisan test

# Backend — a single suite
docker compose exec backend php artisan test --filter=Auth

# Frontend — Vitest, 51 tests
docker compose exec frontend npm run test:run
```

Use `npm run test:run`, not `npm test` — the latter starts watch mode and will
never exit.

---

## Useful commands

```bash
# View logs for one service (follow mode)
docker compose logs -f backend

# Open a shell inside a container
docker compose exec backend sh

# List all registered routes
docker compose exec backend php artisan route:list

# Rebuild the database from scratch with seed data
docker compose exec backend php artisan migrate:fresh --seed

# Rebuild a single service
docker compose build --no-cache backend

# Full reset (destroys both databases and all volumes)
docker compose down -v && docker compose up -d --build
```

---

## Troubleshooting

**Backend logs loop on "Database not ready, retrying in 2s"**
Normal for the first 10–20 seconds while PostgreSQL initialises. If it persists
beyond a minute, check `docker compose logs database`.

**`ERR_EMPTY_RESPONSE` on port 8000**
The container is still booting — migrations and seeding run before the server
starts. Wait for `Server running on [http://0.0.0.0:8000]` in the logs.

**Frontend tests pass locally but fail in CI**
The `node_modules` volume is stale. Run `docker compose down`, then
`docker volume prune -f`, then `docker compose build --no-cache frontend`.
The Dockerfile uses `npm ci` against the lockfile, so a clean build matches CI
exactly.

**Tests fail to connect to the database**
Confirm `app_database_test` is running and healthy with `docker compose ps`.
The test suite connects to the `database_test` service, not `database`.

**Port already in use**
Change the relevant `*_PORT` value in `.env` and restart.

---

## Repository structure

```
.
├── .github/workflows/     CI and CD pipeline definitions
├── AI/                    Python FastAPI service
├── backend/               Laravel 11 REST API
├── frontend/              Vue 3 SPA
├── docs/
│   ├── postman/           API collection and environment
│   └── security/          Security review, remediation, and reference docs
├── UML/                   Design and modelling artefacts
├── docker-compose.yml     Service orchestration
├── .env.example           Environment variable template
├── .dockerignore          Root build-context exclusions
├── .gitignore             Excludes .env and node_modules
└── README.md
```

---

## Branching and contribution workflow

Two permanent branches, plus short-lived topic branches.

| Branch | Role |
|---|---|
| `main` | Validated, deployable version. Receives merges only from `develop`. |
| `develop` | Integration branch. Accumulates reviewed work. |
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

Branch names include the Jira issue key so the integration links them
automatically:

```
infra/SCRUM-24-dockerize-monorepo-services
feature/SCRUM-31-reservation-payment-flow
```

Commit messages begin with the Jira issue key, followed by a Conventional Commits
type prefix:

```
SCRUM-24 infra: add multi-stage Node build for Vite assets
SCRUM-51 ci: run PHPUnit and Vitest as blocking gates
SCRUM-27 fix: untrack .env and add example template
```

Recognised prefixes: `feat:`, `fix:`, `infra:`, `ci:`, `refactor:`, `docs:`,
`test:`, `chore:`.

### Opening a pull request

1. Branch from `develop`
2. Commit and push — CI runs on every push
3. Open a pull request into `develop`, with the issue key(s) in the title
4. Wait for CI to pass and for one approving review
5. Merge

---

## Continuous Integration

A single pipeline (`.github/workflows/ci.yaml`) handles build verification,
testing, and security scanning. It runs on every push to `develop`, `main`, or a
prefixed topic branch, and on every pull request into `develop` or `main`.

**Build verification**

1. Builds all three application images
2. Starts the full stack
3. Asserts the backend (`/up`) and AI service (`/docs`) respond to HTTP probes
4. Validates `composer.lock` consistency with `composer validate --strict`

**Testing**

5. PHPUnit — 73 backend tests
6. Vitest — 51 frontend tests

**Security scanning**

7. TruffleHog scans the code and full git history for verified live credentials
8. Trivy scans all three built images for CVEs (`CRITICAL,HIGH`, unfixed excluded)
9. Trivy scans the filesystem with `vuln,secret,misconfig` scanners enabled
10. Two JSON reports are generated and uploaded as a downloadable artifact

### What blocks a merge

| Step | Blocking |
|---|---|
| Image build and stack startup | Yes |
| Health probes | Yes |
| `composer validate --strict` | Yes |
| PHPUnit | Yes |
| Vitest | Yes |
| TruffleHog | Yes |
| Trivy (all scans) | **No** — report only |

Trivy runs with `exit-code: 0`, so findings are reported without blocking merges:
Alpine and PHP base images routinely carry unfixable CVEs that would otherwise
prevent every merge. The `ignore-unfixed` flag filters that category on image
scans. **This is a deliberate trade-off, not an oversight** — making the image
scans blocking is an open hardening task.

### Retrieving a scan report

- **Readable tables** — expand any `Trivy scan —` step in the job log
- **JSON reports** — at the bottom of the run summary page, under **Artifacts**,
  named `trivy-reports-<timestamp>`. Retained for 14 days.

---

## Security

Security documentation lives under `docs/security/`.

| Document | Purpose |
|---|---|
| [`authentication-security.md`](docs/security/authentication-security.md) | **Start here.** How authentication and authorization work, what controls are in place, accepted risks, open items. |
| [`owasp-auth-review.md`](docs/security/owasp-auth-review.md) | OWASP Top 10 (2021) review of the authentication module. 15 findings with severity and risk analysis. |
| [`remediation/`](docs/security/remediation/) | Per-finding implementation instructions, split by component (backend / frontend / ai). |

The authentication module has been reviewed. **The agency module, car module,
frontend token storage, and the AI service have not.** See the "Open items"
section of `authentication-security.md` for what is tracked and what has no ticket.

### Known findings

**Application** — three High-severity authentication findings are open and tracked
in Jira (SCRUM-122, SCRUM-123, SCRUM-124). See `owasp-auth-review.md`.

**Container configuration** — all three Dockerfiles fail Trivy check DS-0002
("Image user should not be 'root'"), and all base images use floating tags rather
than digest pins. Adding non-root `USER` directives and pinning digests is an
outstanding hardening task with no ticket.

**Dependencies with available fixes** — from the most recent filesystem scan.
Re-run the pipeline for current figures; this table ages.

| Package | Installed | Fixed in | Status |
|---|---|---|---|
| symfony/http-foundation | 7.4.8 | 7.4.13 | patch available |
| symfony/http-kernel | 7.4.11 | 7.4.12 | patch available |
| symfony/mailer | 7.4.8 | 7.4.12 | patch available |
| symfony/mime | 7.4.9 | 7.4.12 | patch available |
| guzzlehttp/guzzle | 7.10.0 | 7.15.2 | patch available |
| league/commonmark | 2.8.2 | 2.9.0 | patch available |
| nanoid | 3.3.15 | 3.3.18 | patch available |
| postcss | 8.5.16 | 8.5.18 | patch available |
| laravel/framework | 11.51.0 | 12.60.0 | **major upgrade — deferred** |

The Laravel finding (CRLF injection in email validation, CVSS 8.9) requires a
major version upgrade and is deferred pending a planned migration. Several of the
Symfony findings concern the same class of issue in the mail path.

To apply the available patches:

```bash
docker compose exec backend composer update symfony/http-foundation \
  symfony/http-kernel symfony/mailer symfony/mime guzzlehttp/guzzle \
  league/commonmark
docker compose exec frontend npm update postcss nanoid
```

---

## Deployment

`.github/workflows/cd.yaml` deploys to a target server on pushes to `main`. It
connects over SSH, pulls the latest commit, rebuilds the stack, and prunes unused
images.

Required repository secrets: `SERVER_HOST`, `SERVER_USER`, `SERVER_SSH_KEY`.

**Current limitations:**

- The pipeline has not yet executed — no target server has been provisioned
- No rollback mechanism. A failed deploy leaves the server in a broken state
- No post-deploy health check
- Not gated on CI passing — a push to `main` deploys regardless of test results
- `php artisan serve` is a single-threaded development server; production requires
  nginx or Apache in front of PHP-FPM
- The frontend image runs `npm run dev` (Vite dev server), not a production build

None of these are suitable for a real deployment. Address them before provisioning
a server.