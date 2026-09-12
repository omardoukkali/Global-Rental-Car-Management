# AI Service Remediation — FastAPI

**Owner:** Unassigned
**Status:** **Not reviewed**

---

## This folder is empty

No remediation files exist for the AI service because **it has never been security reviewed**, not because it was reviewed and found clean.

The review that produced this remediation set (SCRUM-50) was scoped to the Laravel authentication module. The FastAPI service was outside that scope.

---

## What is already known

From the infrastructure audit, without any security assessment:

| Observation | Source |
|---|---|
| Service runs on port 5000, published to the host via `AI_PORT` in `docker-compose.yml` | `docker-compose.yml` |
| Interactive API documentation is served at `/docs` with no authentication — the CI pipeline health check curls it directly | `.github/workflows/ci.yaml` |
| No authentication mechanism is visible on the service | `AI/` directory structure |
| The backend reaches it over the internal Docker network via `AI_SERVICE_URL` | `docker-compose.yml` |
| Container runs as root — no `USER` directive in `AI/Dockerfile` | `AI/Dockerfile` |
| `AI/.dockerignore` does not exclude `.env` | `AI/.dockerignore` |
| Base image `python:3.10-slim` uses a floating tag, not pinned by digest | `AI/Dockerfile` |

None of these have been assessed for exploitability. The last three are container-hardening items that apply to all three services, not just this one.

---

## What is needed

A review ticket covering:

1. **Exposure** — should port 5000 be published to the host at all, or only reachable on the internal network?
2. **Authentication** — does the service need to verify that requests come from the backend, or is network isolation considered sufficient?
3. **Input validation** — what does the service accept, and what happens with malformed or hostile input?
4. **Information disclosure** — is `/docs` intended to be public? It reveals the full API surface.
5. **Dependencies** — `requirements.txt` is scanned by Trivy, but that scan is currently non-blocking (`exit-code: '0'`).

---

## Do not delete this folder

It documents a known scope gap. An empty `ai/` alongside a populated `backend/` could be read as "the AI service is fine" — this README exists to prevent that reading.

Raise with Nizar to get a review ticket created.
