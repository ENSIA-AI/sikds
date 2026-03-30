# Architecture Overview

## System Context

The **SIKDS** (Système d'Information de Kit de Distribution Sécurisée) application serves as a centralized platform for secure distribution of official ministry documents with integrated RAG-based search and question-answering capabilities.

## Technology Stack

### Core Framework
- **Framework**: Laravel 12.46.0 (PHP 8.3+)
- **Frontend**: Blade + Tailwind CSS 4.0 + Alpine.js 3.15
- **Database**: PostgreSQL 17 + pgvector 0.7
- **Cache/Queue**: Redis 8.6+
- **Storage**: MinIO (S3-compatible)

## Engineering Patterns

We follow **Domain-Driven Design (DDD)** principles to ensure maintainability and separation of concerns:

-   **Bounded Contexts**: Located in `app/Domain/{Context}/`
-   **Action Pattern**: Encapsulates single business use cases (e.g., `CreateDocumentAction`).
-   **Repository Pattern**: Abstracts data access logic.
-   **Service Layer**: Handles complex business logic and external integrations.

### Directory Structure

```
app/
├── Domain/           # DDD Bounded Contexts (Users, Documents, Audit, etc.)
├── Http/             # Controllers, Middleware, Requests
├── Infrastructure/   # External service implementations
├── Providers/        # Service providers
├── Support/          # Cross-cutting utilities
```

## Security & Data Integrity

### 1. Authentication (SSO First)
- Primary authentication via **Ministry OAuth 2.0 SSO**.
- Automatic user provisioning on first login.
- Support for multiple auth domains (e.g., `mesrs.dz`, `ensia.edu.dz`).

### 2. Permissions (RBAC)
- Fine-grained Role-Based Access Control using **Spatie Laravel Permission**.
- Predefined system roles (Super Admin) and granular capabilities (e.g., `document.view.assigned`).

### 3. Database Security
- **Audit Traceability**: Every security event is logged to an **immutable** `audit_logs` table (PostgreSQL triggers prevent UPDATE/DELETE).
- **Download Traceability**: Every document download is logged with a unique watermark UUID and IP address.
- **Data Integrity**: Enforced via foreign key constraints, unique indexes, and SHA-256 file hashing.

### 4. RAG Pipeline
- Document content is chunked and vectorized using `pgvector`.
- Semantic search allows users to query documents using natural language.
- Permission-aware retrieval ensures users only "see" chunks they are authorized to access.
