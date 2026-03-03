# SIKDS: Secure Institutional Knowledge & Distribution System

**SRS Reference:** [docs/SIKDS_SRS.pdf](docs/SIKDS_SRS.pdf)

A centralized platform for the secure distribution of official ministry documents to subordinate institutions (universities), with integrated **Retrieval-Augmented Generation (RAG)** for semantic search and question-answering.

---

## Purpose & Scope

SIKDS addresses fragmented document distribution (email, physical delivery, ad-hoc uploads) by providing:

| Goal | Solution |
|------|----------|
| **Centralized distribution** | Single source of truth for official directives |
| **Accountability** | Watermarking and audit trails for every document access |
| **Intelligence** | RAG-powered semantic search (no manual archive digging) |
| **Security** | Permissions-based access control and comprehensive audit logging |

**In scope:**

- Secure document upload and storage and watermarking.
- User authentication and permissions-based access control
- Document vectorization and RAG (semantic search & Q&A)

---

## System Architecture (High-Level)

```
+-------------------------------------------------------------+
| SIKDS Platform                                              |
+-------------------------------------------------------------+
| Presentation Layer                                          |
|   Web Interface (Laravel Blade + Tailwind CSS) | Admin UI   |
+-------------------------------------------------------------+
| Application Layer                                           |
|   Auth & SSO | Document Mgmt | Watermarking | RAG | Notifs  |
+-------------------------------------------------------------+
| AI/ML Layer (Laravel AI SDK, Queue Jobs)                    |
|   Chunking, OCR | Embeddings | pgvector | LLM Query         |
+-------------------------------------------------------------+
| Data Layer                                                  |
|   PostgreSQL (pgvector) | Redis | MinIO (S3-compatible)     |
+-------------------------------------------------------------+
```

## Technology Stack

| Layer | Technology |
|-------|------------|
| **Core** | Laravel 12 (PHP 8.3), Blade, Tailwind CSS 3.4+, Alpine.js 3.15 |
| **Auth** | Laravel Sanctum; Spatie Laravel Permission |
| **AI/RAG** | Laravel AI SDK (Prism); pgvector; ministry-hosted LLM |
| **Data** | PostgreSQL 17 + pgvector 0.7; Redis 8.6 (queue, cache, sessions); MinIO (S3-compatible storage) |
| **Watermarking** | PHP PDF (XMP); FPDI (text overlay); Imagick (logo — extended release) |

---

## Getting Started

### Prerequisites

- PHP 8.3+
- Composer
- Node.js & npm (for frontend assets)
- PostgreSQL 17 with pgvector extension
- Redis 8.6
- MinIO

## Installation

1.  **Clone the repository**:
    ```bash
    git clone <https://github.com/ENSIA-AI/sikds>
    cd sikds
    ```

2.  **Install PHP dependencies**:
    ```bash
    composer install
    ```

3.  **Install Node.js dependencies**:
    ```bash
    npm install
    ```

4.  **Environment Configuration**:
    Copy the example environment file and configure your database settings:
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    *Update the `.env` file with your database credentials (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD).*

5.  **Database Migration**:
    Run the migrations to set up the database schema:
    ```bash
    php artisan migrate
    ```

6. **Queue worker** (for indexing and notifications)

   ```bash
   php artisan queue:work
   ```

## Development

To start the local development server, which runs both the Laravel server and Vite for asset bundling:

```bash
composer run dev
```
*Alternatively, you can run them separately:*
```bash
php artisan serve
npm run dev
---

## Documentation

- **Software Requirements Specification (SRS):** [docs/SIKDS_SRS.pdf](docs/SIKDS_SRS.pdf)
- **Contributing & conduct:** [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)

---

## Contributing

We use **feature branches** and merge to `main` only at the end of each sprint. See [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) for:

- GitHub workflow (e.g. `feature/user`, `feature/document-upload` → `dev` → `main`)
- Clean code principles and expectations
- How to open issues and submit changes

---

## License

See [LICENSE.md](LICENSE.md).

---

*SIKDS: Secure Institutional Knowledge & Distribution System. Ministry of Higher Education and Scientific Research, People's Democratic Republic of Algeria.*
