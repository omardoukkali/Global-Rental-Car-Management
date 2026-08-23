# Global Rental Car Management

A multi-tier car rental management platform built as a containerized monorepo.

| Service | Technology | Port |
|---|---|---|
| Backend | Laravel 11 / PHP 8.2-FPM (Alpine) + Inertia | 8000 |
| Frontend | Vue 3 SPA (Vite dev server) | 3000 |
| AI Service | Python FastAPI | 5000 |
| Database | PostgreSQL 15 (Alpine) | 5432 |

All four services run as Docker containers on a shared bridge network (`app_network`),
orchestrated with Docker Compose. Nothing needs to be installed on the host except Docker.

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

# 3. Build and start all four services
docker compose up -d --build

# 4. Confirm everything is healthy
docker compose ps
```

The first build takes 5–10 minutes: it pulls the PHP, Node, Python, and Postgres
base images, installs Composer and npm dependencies, and compiles the frontend
assets with Vite.

You should see four containers with status `Up`, and `app_database` marked
`(healthy)`.

### Service URLs

| What | URL |
|---|---|
| Backend application (Inertia UI) | http://localhost:8000 |
| Backend health check | http://localhost:8000/up |
| REST API | http://localhost:8000/api |
| Frontend SPA (Vite dev server) | http://localhost:3000 |
| AI service (Swagger UI) | http://localhost:5000/docs |
| PostgreSQL | localhost:5432 |

To stop everything: `docker compose down`

---

## What happens automatically on first boot

The backend container's entrypoint (`backend/docker-entrypoint.sh`) handles setup
so no manual steps are needed:

1. Fixes storage directory permissions
2. Creates the `public/storage` symlink
3. Generates an `APP_KEY` if none is set in `.env`
4. Waits for PostgreSQL, then runs migrations
5. Seeds the database on first boot only (guarded by a lock file)
6. Starts the application server

Because of step 3, the app boots successfully even with a blank `APP_KEY`.
The generated key is **ephemeral** — it is regenerated whenever the container is
recreated, which invalidates existing sessions and any encrypted column values.
For any persistent or production deployment, set a fixed `APP_KEY` in `.env`:

```bash
docker compose exec backend php artisan key:generate --show
# paste the output into APP_KEY= in your .env, then:
docker compose restart backend
```

---

## Important: refreshing frontend assets

The backend's compiled Vite assets live in a named Docker volume
(`backend_public_build`) so that the host's empty `public/build` directory cannot
shadow the assets baked into the image.

Docker only populates a named volume from the image **the first time the volume is
created**. This means that after changing any file under `backend/resources/js/`,
a plain rebuild will **not** update what the container serves. To pick up asset
changes:

```bash
docker compose down -v
docker compose up -d --build
```

Note that `-v` also destroys the `db_data` volume, so migrations and seeders will
re-run from scratch.

---

## Seeded development accounts

The seeder creates test users. Password for all three: `123456`

| Email | Role |
|---|---|
| admin@test.com | admin |
| owner@test.com | agency_owner |
| client@test.com | client |

These are **development credentials only** and must never be used in a deployed
environment.

---

## Environment variables

Copy `.env.example` to `.env` and adjust as needed. `APP_KEY` and `DB_PASSWORD`
are intentionally left blank in the template.

| Variable | Description | Default |
|---|---|---|
| `APP_NAME` | Application display name | Global Rental Car |
| `APP_ENV` | Environment | local |
| `APP_KEY` | Laravel encryption key | generated at boot if blank |
| `APP_DEBUG` | Verbose error pages | true |
| `APP_URL` | Base URL | http://localhost:8000 |
| `FRONTEND_PORT` | Host port for the Vue SPA | 3000 |
| `BACKEND_PORT` | Host port for Laravel | 8000 |
| `AI_PORT` | Host port for FastAPI | 5000 |
| `DB_PORT` | Host port for PostgreSQL | 5432 |
| `DB_CONNECTION` | Laravel database driver | pgsql |
| `DB_HOST` | Database hostname (compose service name) | database |
| `DB_DATABASE` | Database name | globalrental |
| `DB_USERNAME` | Database user | grader |
| `DB_PASSWORD` | Database password | — |
| `AI_SERVICE_URL` | Internal AI service address | http://ai_service:5000 |

`.env` is gitignored and must never be committed.

---

## Useful commands

```bash
# View logs for one service (follow mode)
docker compose logs -f backend

# Open a shell inside a container
docker compose exec backend sh

# Run the test suite
docker compose exec backend php artisan test

# List all registered routes
docker compose exec backend php artisan route:list

# Rebuild the database from scratch with seed data
docker compose exec backend php artisan migrate:fresh --seed

# Rebuild a single service
docker compose build --no-cache backend

# Full reset (destroys database and volumes)
docker compose down -v && docker compose up -d --build
```

---

## Troubleshooting

**`ViteManifestNotFoundException`**
The compiled assets are missing from the `backend_public_build` volume. Run
`docker compose down -v && docker compose up -d --build`.

**Backend logs loop on "Database not ready, retrying in 2s"**
Normal for the first 10–20 seconds while PostgreSQL initialises. If it persists
beyond a minute, check `docker compose logs database`.

**`ERR_EMPTY_RESPONSE` on port 8000**
The container is still booting — migrations and seeding run before the server
starts. Wait for `Server running on [http://0.0.0.0:8000]` in the logs.

**Composer install times out (Windows)**
Dependencies are installed during the Docker build into a named volume rather
than across the host filesystem, so this should not occur. If you install
manually, run it inside the container rather than on the host.

**Port already in use**
Change the relevant `*_PORT` value in `.env` and restart.

---

## Repository structure

```
.
├── .github/workflows/     CI and CD pipeline definitions
├── AI/                    Python FastAPI service
├── backend/               Laravel 11 API and Inertia application
├── frontend/              Vue 3 SPA
├── docker-compose.yml     Four-service orchestration
├── .env.example           Environment variable template
├── .dockerignore          Root build-context exclusions
├── .gitignore             Excludes .env and node_modules
└── README.md
```

---

## Continuous Integration

Every push to `develop`, `main`, `feature/**`, `infra/**`, `refactor/**`, or
`hotfix/**` triggers the CI pipeline, which:

1. Builds all three application images
2. Starts the full stack
3. Asserts the backend and AI service respond to HTTP health probes
4. Validates `composer.lock` consistency
5. Confirms the Vite manifest was built into the image
6. Runs Trivy vulnerability scans on all three images and on the filesystem

`main` and `develop` are protected: direct pushes are blocked, and merges require
one approving review plus a passing pipeline.
## Testing environment

The project uses an **isolated PostgreSQL container** (`app_database_test`) on port `5434` for tests, keeping your development data safe.

### Initial setup (one time)

Start the test database container:
```bash
docker compose up -d database_test
```

Run migrations on the test database:
```bash
docker exec -it -e DB_HOST=database_test -e DB_DATABASE=globalrental_test app_backend php artisan migrate --env=testing
```

### Running tests

Once set up, simply run:
```bash
docker exec -it app_backend php artisan test
```

Run a specific test class:
```bash
docker exec -it app_backend php artisan test --filter=AuthTest
```

Reset the test database (if migrations change):
```bash
docker exec -it -e DB_HOST=database_test -e DB_DATABASE=globalrental_test app_backend php artisan migrate:fresh --env=testing
```

### Configuration files

- `docker-compose.yml` — `database_test` service (port 5434, DB: `globalrental_test`)
- `backend/.env.testing` — Laravel testing environment variables
- `backend/phpunit.xml` — PHPUnit config with forced DB variables
