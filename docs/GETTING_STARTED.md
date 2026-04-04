# SIKDS — Getting Started

This guide covers everything a developer needs to set up the project locally from scratch.

---

## Prerequisites

| Tool | Version | Link |
|------|---------|------|
| Git | Latest | https://git-scm.com/ |
| PHP | 8.3+ | https://www.php.net/downloads |
| Composer | 2.x | https://getcomposer.org/ |
| Docker Desktop | Latest | https://www.docker.com/products/docker-desktop/ |
| Node.js | 18+ | https://nodejs.org/ |
| pgAdmin *(optional)* | Latest | https://www.pgadmin.org/download/ |

> **PHP 8.3 is required.** Verify your version before starting:
> ```bash
> php --version
> ```

---

## Setup Steps

### 1. Clone the repository
```bash
git clone <repo-url>
cd sikds
```

### 2. Install PHP dependencies
```bash
composer install
```

### 3. Install Node dependencies
```bash
npm install
```

### 4. Configure environment
```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` and review the following:

- **Database:** pre-configured to connect to the Docker PostgreSQL container — no changes needed for local dev.
- **SSO variables:** leave blank for local development. Fill them in only if you have access to the ministry SSO server.
- **Queue/Cache:** pre-configured for Redis running in Docker.

### 5. Start Docker services

Docker Compose starts **PostgreSQL**, **Redis**, and **Mailpit** (see `compose.yaml`). The Laravel app itself runs on your machine (`php artisan serve`); containers only provide dependencies.

**Mailpit** catches outgoing mail in development: point Laravel’s mailer at SMTP **127.0.0.1:1025** and open **http://localhost:8025** to read captured emails (no real messages are sent).

**MinIO** (S3-compatible object storage for documents) is **not set up in Docker yet**. It will be added when document upload/storage is wired to the app. Until then, local dev does not run a MinIO container.

```bash
docker compose up -d
```

| Service | Description | Port |
|---------|-------------|------|
| PostgreSQL 17 + pgvector | Primary database | 5433 |
| Redis | Queue and cache | 6379 |
| Mailpit | Dev mail inbox (SMTP + web UI) | 1025 (SMTP), **8025** (dashboard) |

To verify all containers are running:
```bash
docker compose ps
```

### 6. Run migrations
```bash
php artisan migrate
```

This creates all 26 database tables including documents, users, permissions, audit logs, and more.

### 7. Seed the database
```bash
php artisan db:seed
```

This inserts the base data required for the system to function:

| Data | Details |
|------|---------|
| Institutions | 5 institutions (ministry + 4 universities) |
| Permissions | 26 permissions as defined in the SRS |
| Roles | Super Administrateur role with all permissions |
| Tags | 8 predefined tags (4 document types + 4 priority levels) |
| Super Admin | 1 local admin account for development |

### 8. Build frontend assets
```bash
npm run dev
```

### 9. Start the development server
```bash
php artisan serve
```

Visit `http://localhost:8000` in your browser.

---

## Logging In (Local Development)

The system uses SSO authentication in production. For local development, a local login form is available at the bottom of the login page when `APP_ENV=local`.

| Field | Value |
|-------|-------|
| Email | admin@mesrs.dz |
| Password | Admin@SIKDS2026! |
| Role | Super Administrateur |

> ⚠️ This account and the local login form exist for development only. In production all authentication goes through the ministry SSO.

Will share the .ENV file with all creds once development starts 

---

## Project Structure
```
app/
├── Domain/          # Business logic by domain (Documents, Users, Tags, Audit...)
├── Http/
│   ├── Controllers/ # HTTP controllers
│   └── Middleware/  # Request middleware
database/
├── migrations/      # All migrations in dependency order
└── seeders/         # Base data seeders
resources/
└── views/           # Blade templates and Livewire components
routes/
├── web.php          # Main route file
├── auth.php         # Authentication routes
├── common.php       # Shared routes
└── functionalities.php # Feature routes
docs/                # Project documentation and SRS
```

---

## Running Tests
```bash
php artisan test
```

16 tests should pass on a clean setup.

---

## Docker Commands

| Command | Effect |
|---------|--------|
| `docker compose up -d` | Start all services in background |
| `docker compose ps` | Check running containers |
| `docker compose down` | Stop all services |
| `docker compose down -v` | Stop and delete all data (full reset) |