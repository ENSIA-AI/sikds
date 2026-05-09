# Project Overview

SIKDS (Secure Institutional Knowledge & Distribution System) is the ministry platform used to distribute official documents to institutions, enforce role-based access control, and provide secure search/RAG-ready indexing over institutional content with complete traceability.

# Tech Stack

| Layer          | Technology               | Version    |
| -------------- | ------------------------ | ---------- |
| Framework      | Laravel                  | 13         |
| Language       | PHP                      | 8.3        |
| Database       | PostgreSQL + pgvector    | 18 + 0.8.2 |
| Cache & Queue  | Redis                    | 8.6        |
| Object Storage | SeaweedFS                | 4.02       |
| Frontend       | Tailwind CSS + Alpine.js | 4.2 + 3.15 |
| Queue Monitor  | Laravel Horizon          | latest     |
| AI / RAG       | Laravel AI SDK (Prism)   | latest     |

# Prerequisites

- Docker Desktop
- Git

# Setup — Step by Step

1. Clone the repository.

   ```bash
   git clone <repository-url>
   cd sikds
   ```

2. Create local environment file.

   ```bash
   cp .env.example .env
   ```

3. Fill in required `.env` values.
   - Mandatory for local dev:
     - `DB_PASSWORD`
     - `AWS_ACCESS_KEY_ID`
     - `AWS_SECRET_ACCESS_KEY`
   - `CLIENT_ID`
   - `CLIENT_SECRET`
   - Can stay default for local dev:
     - `APP_*` except `APP_KEY`, `DB_*` except password, `REDIS_*`, `QUEUE_CONNECTION`, `CACHE_STORE`, `SESSION_DRIVER`
     - `AWS_BUCKET`, `AWS_ENDPOINT`, `AWS_USE_PATH_STYLE_ENDPOINT`
     - `MAIL_*`, `HORIZON_*`, `SEAWEED_*_PORT`

4. Start all services.

   ```bash
   docker compose up -d
   ```

5. Generate app key.

   Each environment should generate its own `APP_KEY`; `.env.example` keeps it empty on purpose.

   ```bash
   docker compose exec app php artisan key:generate
   ```

6. Run migrations.

   ```bash
   docker compose exec app php artisan migrate
   ```

7. Run production-safe seeders.

   ```bash
   docker compose exec app php artisan db:seed
   ```

8. Create local dev admin account.

   ```bash
   docker compose exec app php artisan db:seed --class=DevSeeder
   ```

9. Create storage symlink.

   ```bash
   docker compose exec app php artisan storage:link
   ```

10. Build frontend assets (Vite runs inside Docker).

```bash
docker compose exec app npm install
docker compose exec app npm run dev
```

1. Open the app.

2. Build frontend assets (Vite runs inside Docker).

````bash
docker compose exec app npm install
docker compose exec app npm run dev

1. Open the app.

- <http://localhost>

# Deployment Notes

For deployment, the project can run the normal application services with Docker Compose, and the heavier RAG services can be enabled through the `prod` profile.
- copy the .env.example. fill the needed variables:
   - RAG_LLM_URL and LLM_API_KEY
   - set COMPOSE_PROFILES=prod

```bash
docker compose up -d
````

The `prod` profile starts the services that are not always needed during simple local development:

- `surya`: the OCR service used when a document page does not contain enough extractable text.
- `embeddings`: the local embedding service, served through Infinity with the Nomic embedding model.

These services are handled inside Docker Compose, so there is no separate OCR server or embedding server to install manually. The Laravel app talks to them through the internal Docker network using the service URLs from `.env`, for example `OCR_SERVICE_URL=http://surya:8100` and `RAG_EMBEDDING_URL=http://embeddings:7997`.

On the first deployment, the OCR and embedding containers may take longer to become healthy because they need to download and cache their model weights. After that, the Docker volumes keep the cached files between restarts.

# Authentication

## SSO Login

SSO users authenticate through the ministry identity provider and are auto-provisioned on first successful login using their ministry email profile.

## Dev Login

Use `http://localhost/login/local` with `admin@mesrs.dz / password`. This route is enabled only when `APP_ENV=local`.

# Queue & Horizon

```bash
docker compose exec app php artisan horizon
```

Visit `http://localhost/horizon` to monitor jobs. Access is restricted to Super Admin or users with `audit.view`.

# Running Tests

```bash
docker compose exec app php artisan test
```
