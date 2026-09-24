# Deployment Guide — Global Rental Car Management

This document describes how the platform is deployed to Microsoft Azure and how
to reproduce the deployment from scratch.

---

## 1. Architecture

The application runs as three containers on **Azure Container Apps**, backed by a
managed **Azure Database for PostgreSQL Flexible Server**.

| Component | Container app | Ingress | Port | Image |
|---|---|---|---|---|
| Laravel 11 API | `grc-api` | Public | 8000 | `nizarharmach1/grc-api` |
| Vue 3 frontend | `grc-frontend` | Public | 3000 | `nizarharmach1/grc-frontend` |
| FastAPI AI service | `grc-ai` | Internal only | 5000 | `nizarharmach1/grc-ai` |
| PostgreSQL 16 | `psql-grc-nizar` | Public (firewalled) | 5432 | managed service |

Request flow:

```
Browser ──► grc-frontend (Vue dev server)
   │
   └──────► grc-api (Laravel) ──► psql-grc-nizar (PostgreSQL)
                   │
                   └──────────► grc-ai (FastAPI, internal only)
```

The AI service is reachable only from inside the Container Apps environment.
The frontend calls the API directly from the browser, so the API must have
public ingress.

### Azure resources

| Resource | Name |
|---|---|
| Subscription | Azure for Students |
| Resource group | `rg-globalrentalcar` |
| Region | France Central |
| Container Apps environment | `cae-globalrentalcar` (Consumption, no zone redundancy) |
| PostgreSQL server | `psql-grc-nizar` (v16, Burstable B1ms, 32 GiB) |
| Database | `globalrental` |
| Log Analytics workspace | `workspacergglobalrentalcar84b7` |

> **Region note:** the Azure for Students subscription is not permitted to
> provision in West Europe. France Central was used instead. All resources must
> be in the same region.

---

## 2. Prerequisites

- An Azure subscription with permission to create resource groups, container
  apps and an Entra ID app registration
- A Docker Hub account (images are currently hosted under `nizarharmach1`)
- Docker Desktop for local builds
- Write access to the GitHub repository, and admin access to configure secrets

---

## 3. Provisioning the infrastructure

### 3.1 Resource group

Portal → **Resource groups** → **Create**

- Name: `rg-globalrentalcar`
- Region: France Central

### 3.2 PostgreSQL Flexible Server

Portal → **Azure Database for PostgreSQL flexible server** → **Create**

| Setting | Value |
|---|---|
| Server name | `psql-grc-nizar` |
| Region | France Central |
| PostgreSQL version | 16 |
| Workload type | Dev/Test |
| Compute + storage | Burstable, **Standard_B1ms**, 32 GiB |
| High availability | Disabled |
| Authentication method | PostgreSQL authentication only |
| Administrator login | `grcadmin` |

**Networking tab:**

- Connectivity method: **Public access (allowed IP addresses)** — this cannot be
  changed after creation
- Tick **Allow public access from any Azure service within Azure to this server**
  (required for Container Apps, whose egress IPs are not fixed)
- Add your client IP so you can connect with pgAdmin or DBeaver

**Security tab:** leave Data encryption on *Service-managed key*.

After deployment, go to **Settings → Databases → + Add** and create the database
`globalrental` (UTF8 / `en_US.utf8`).

> **Password constraints:** avoid spaces and the characters `@ : / % # ?`.
> Spaces break `.env` parsing; the others break connection-string parsing.
> Restrict to letters, digits and `- _ . ! *`.

### 3.3 Container Apps environment

Portal → **Container Apps** → **Create**

- Environment name: `cae-globalrentalcar`
- Region: France Central
- Plan: **Consumption only**
- Zone redundancy: Disabled

---

## 4. Building and publishing the images

From the repository root:

```bash
docker build -t nizarharmach1/grc-api:<tag> ./backend
docker push nizarharmach1/grc-api:<tag>

docker build -t nizarharmach1/grc-frontend:<tag> ./frontend
docker push nizarharmach1/grc-frontend:<tag>

docker build -t nizarharmach1/grc-ai:<tag> ./AI
docker push nizarharmach1/grc-ai:<tag>
```

Use an immutable tag (the short commit SHA) rather than `latest`, so the running
revision can always be traced back to a commit. The CD pipeline does this
automatically.

> The Docker Hub repositories are **public**. `.env` is excluded from all three
> images via `.dockerignore`; verify this before publishing any new image.

---

## 5. Deploying the container apps

Each app is created the same way: Portal → **Container Apps** → **Create**, with
the resource group `rg-globalrentalcar`, region France Central, and the existing
environment `cae-globalrentalcar`.

On the **Container** tab, set Image source to *Docker Hub or other registries*,
Image type *Public*, registry login server `docker.io`, and allocate
**0.5 CPU / 1 Gi** memory.

### 5.1 `grc-api` (Laravel)

**Image:** `nizarharmach1/grc-api:<tag>`

**Environment variables:**

| Name | Value |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | the app's own FQDN (set after first deploy) |
| `APP_KEY` | output of `php artisan key:generate --show` |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | `psql-grc-nizar.postgres.database.azure.com` |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | `globalrental` |
| `DB_USERNAME` | `grcadmin` |
| `DB_PASSWORD` | the PostgreSQL admin password |
| `DB_SSLMODE` | `require` |
| `SESSION_DRIVER` | `database` |
| `AI_SERVICE_URL` | the internal FQDN of `grc-ai` (see 5.3) |

**Ingress:** Enabled · Accepting traffic from anywhere · HTTP · Transport Auto ·
target port **8000**

`APP_URL` is a chicken-and-egg value: deploy once with a placeholder, copy the
Application Url from the Overview page, then update the variable and save a new
revision.

Migrations run automatically from `docker-entrypoint.sh` on container start. To
seed manually, use **Monitoring → Console**:

```bash
php artisan db:seed --force
```

### 5.2 `grc-frontend` (Vue)

**Image:** `nizarharmach1/grc-frontend:<tag>`

**Environment variables:**

| Name | Value |
|---|---|
| `VITE_API_URL` | `https://<grc-api FQDN>/api` — note the `/api` suffix |

**Ingress:** Enabled · Accepting traffic from anywhere · HTTP · Transport Auto ·
target port **3000**

The image currently runs the Vite dev server, which reads `VITE_` variables at
runtime — so changing the API URL does not require a rebuild. If the image is
ever switched to a production build, `VITE_API_URL` becomes a **build-time**
argument and the image must be rebuilt when it changes.

`vite.config.js` must allow the Azure hostname, otherwise the dev server rejects
every request:

```js
server: {
  allowedHosts: ['.azurecontainerapps.io'],
}
```

### 5.3 `grc-ai` (FastAPI)

**Image:** `nizarharmach1/grc-ai:<tag>`

**Ingress:** Enabled · **Limited to Container Apps Environment** · HTTP ·
Transport Auto · target port **5000**

Because ingress is internal, the app is reachable only from inside the
environment. Its URL contains `.internal.`:

```
https://grc-ai.internal.<environment-suffix>.francecentral.azurecontainerapps.io
```

Set that full URL as `AI_SERVICE_URL` on `grc-api`. Use **https** and **no port
number** — the ingress listens on 443 and forwards to the container's port 5000.
Requests to `http://` are redirected with a 301.

---

## 6. Application configuration notes

Two changes in the codebase exist specifically to support this deployment.

**`backend/docker-entrypoint.sh`** writes `.env` from the container environment
at startup. This is necessary because `php artisan serve` spawns a child
`php -S` process that does not inherit the container environment, so Laravel
reads the file rather than the injected variables. Every value is quoted —
an unquoted value containing a space causes dotenv to fail parsing the entire
file, which surfaces as a misleading `MissingAppKeyException`.

**`backend/config/database.php`** reads `sslmode` from the environment:

```php
'sslmode' => env('DB_SSLMODE', 'prefer'),
```

The default keeps local `docker compose` behaviour unchanged; Azure sets
`DB_SSLMODE=require`.

---

## 7. Continuous deployment

`.github/workflows/cd.yaml` deploys automatically after the CI pipeline passes
on `main`, and can also be triggered manually from the Actions tab.

The workflow:

1. Checks out the commit CI tested
2. Builds and pushes all three images to Docker Hub, tagged with the short SHA
3. Authenticates to Azure via OIDC
4. Records the current revisions for rollback
5. Updates each container app to the new image tag
6. Health-checks `GET /up` on the API and `GET /` on the frontend
7. Reverts to the previous revisions if any step fails

### 7.1 Azure authentication (OIDC)

An Entra ID app registration acts as the identity GitHub Actions authenticates
as. No client secret is stored anywhere.

**Create the app registration:** Portal → **Microsoft Entra ID** →
**App registrations** → **New registration**

- Name: `github-actions-globalrentalcar`
- Account types: Single tenant

**Add a federated credential:** the app registration →
**Certificates & secrets** → **Federated credentials** → **Add credential**

- Scenario: **Other issuer**
- Issuer: `https://token.actions.githubusercontent.com`
- Type: Explicit subject identifier
- Subject: `repo:omardoukkali/Global-Rental-Car-Management:ref:refs/heads/main`
- Audience: `api://AzureADTokenExchange`

> Use the *Other issuer* scenario rather than the GitHub Actions template. The
> template generates a subject in Azure's newer immutable-ID format
> (`repo:org@12345/repo@67890:ref:...`), which does not match the subject claim
> GitHub actually sends.

The subject is scoped to `main`. Runs from any other branch will fail
authentication by design.

**Grant permissions:** `rg-globalrentalcar` → **Access control (IAM)** →
**Add role assignment** → role **Container Apps Contributor** → member
`github-actions-globalrentalcar`.

Scoping to the resource group and to this role keeps the identity limited to
managing container apps in this project and nothing else in the subscription.

### 7.2 GitHub secrets

Repository → **Settings → Secrets and variables → Actions**:

| Secret | Where to find it |
|---|---|
| `DOCKERHUB_TOKEN` | Docker Hub → Account settings → Personal access tokens (Read & Write) |
| `AZURE_CLIENT_ID` | App registration → Overview → Application (client) ID |
| `AZURE_TENANT_ID` | App registration → Overview → Directory (tenant) ID |
| `AZURE_SUBSCRIPTION_ID` | Portal → Subscriptions → Azure for Students |

---

## 8. Operating notes

**Cold starts.** All three apps scale to zero when idle. The first request after
a period of inactivity can take over 20 seconds while the container starts. Warm
each URL a few minutes before a demonstration.

**Cost.** The B1ms PostgreSQL instance at 32 GiB falls within the subscription's
free monthly allowance, and Container Apps consumption is negligible at this
scale. To reduce cost further between demos, stop the PostgreSQL server
(server → Overview → **Stop**); it auto-restarts after 7 days.

**Logs.** Container Apps → the app → **Monitoring → Logs**, or query Log
Analytics directly:

```kusto
ContainerAppConsoleLogs_CL
| where RevisionName_s == "<revision-name>"
| project TimeGenerated, Log_s
| order by TimeGenerated asc
```

Real-time logs are only available while a replica is running; use **Historical**
for a crashed revision.

**Rollback.** The CD pipeline reverts automatically on a failed health check. To
roll back manually: the app → **Revisions and replicas** → select a previous
revision → **Activate**.

---

## 9. Known limitations

These are deliberate trade-offs for a demonstration deployment, not
production-ready choices.

- **Development servers in production.** The backend runs `php artisan serve`
  and the frontend runs the Vite dev server. Both are single-threaded and
  unsuitable for real traffic. A production deployment would use nginx +
  php-fpm and a static build served by nginx.
- **Public container registry.** The Docker Hub repositories are public because
  the free tier allows only one private repository. Azure Container Registry
  (~$5/month) would keep the images private and support managed-identity pulls.
- **Public API ingress.** The API is publicly reachable because browser-side
  code calls it directly. Putting a reverse proxy in front of an internal-only
  API would remove that exposure.
- **`sslmode=require`** guarantees encryption but does not verify the server
  certificate. `verify-full` with a CA certificate would be the stronger choice.
- **The AI service is not health-checked** by the CD pipeline, since its
  internal ingress is unreachable from a GitHub runner.
- **Rollback is not atomic.** If one app deploys successfully and another fails,
  both are reverted, but there is a brief window where the deployed versions are
  mismatched.