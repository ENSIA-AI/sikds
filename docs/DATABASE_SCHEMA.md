# SIKDS Database Architecture

## 1. Authentication & User Management

### 1.1 Institutions Table

```sql
CREATE TABLE institutions (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL, -- 'MESRS', 'ENSIA', etc.
    name TEXT NOT NULL,
    type VARCHAR(20) NOT NULL DEFAULT 'ministry',
    domain VARCHAR(100) UNIQUE, -- 'mesrs.dz' and extended domains like 'ensia.edu.dz'

    contact_email VARCHAR(255),
    contact_phone VARCHAR(50),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    CONSTRAINT chk_institution_type
        CHECK (type IN ('ministry', 'university'))
);

CREATE INDEX idx_institutions_type ON institutions (type);
CREATE INDEX idx_institutions_domain ON institutions (domain);
CREATE INDEX idx_institutions_active ON institutions (is_active);
```

### 1.2 Users Table

- `sso_user_id` - External SSO identifier
- `username` - SSO username
- `auth_type` - `'sso'` or `'local'` (local is a base for extension)
- `auth_domain`

```sql
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    sso_user_id VARCHAR(100) UNIQUE,
    username VARCHAR(100) UNIQUE,

    -- Profile data
    email VARCHAR(255) UNIQUE NOT NULL,
    full_name TEXT NOT NULL,
    institution_id BIGINT NOT NULL REFERENCES institutions(id)
        ON DELETE RESTRICT,

    auth_type VARCHAR(20) NOT NULL DEFAULT 'sso',
    auth_domain VARCHAR(100),
    password VARCHAR(255), -- Only for 'local' auth
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_login_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    created_by BIGINT REFERENCES users(id) ON DELETE SET NULL,

    CONSTRAINT chk_auth_type
        CHECK (auth_type IN ('sso', 'local')),
    CONSTRAINT chk_sso_no_password
        CHECK (
            (auth_type = 'sso' AND password IS NULL) OR
            (auth_type = 'local')
        )
);

CREATE UNIQUE INDEX idx_users_email ON users (email);
CREATE INDEX idx_users_institution ON users (institution_id);
CREATE INDEX idx_users_auth_type ON users (auth_type);
CREATE INDEX idx_users_auth_domain ON users (auth_domain);
CREATE INDEX idx_users_sso_id ON users (sso_user_id);
CREATE INDEX idx_users_active ON users (is_active);
CREATE INDEX idx_users_email_active ON users (email, is_active);
```

---

## 2. Permissions-Based Access Control (RBAC)

**Implementation (Spatie Laravel Permission):** The database uses Spatie’s table names: `role_has_permissions` (logical equivalent of `role_permissions` below) and `model_has_roles` (logical equivalent of `user_roles`, with polymorphic `model_type` / `model_id` pointing at `users`). Spatie’s `permissions.name` is populated with the same value as `code` for compatibility with `HasRoles` / `syncPermissions()`. The `guard_name` column (typically `web`) is required by Spatie and is not shown in the SRS-oriented snippets below.

### 2.1 Permissions Table

```sql
CREATE TABLE permissions (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(100) UNIQUE NOT NULL, -- 'document.create', 'user.manage', etc.
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(50) NOT NULL, -- 'documents', 'users', 'rag', 'audit'
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE UNIQUE INDEX idx_permissions_code ON permissions (code);
CREATE INDEX idx_permissions_category ON permissions (category);
```

### 2.2 Roles Table

```sql
CREATE TABLE roles (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    is_system_role BOOLEAN DEFAULT FALSE, -- Super Admin is seeded
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    created_by BIGINT REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_roles_is_system ON roles (is_system_role);
CREATE UNIQUE INDEX idx_roles_slug ON roles (slug);
```

### 2.3 Role-Permission Pivot Table

```sql
CREATE TABLE role_permissions (
    role_id BIGINT NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    permission_id BIGINT NOT NULL REFERENCES permissions(id) ON DELETE CASCADE,
    assigned_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    PRIMARY KEY (role_id, permission_id)
);

CREATE INDEX idx_role_permissions_role ON role_permissions (role_id);
CREATE INDEX idx_role_permissions_permission ON role_permissions (permission_id);
```

### 2.4 User-Role Pivot Table

```sql
CREATE TABLE user_roles (
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role_id BIGINT NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    assigned_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    assigned_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    PRIMARY KEY (user_id, role_id)
);

CREATE INDEX idx_user_roles_user ON user_roles (user_id);
CREATE INDEX idx_user_roles_role ON user_roles (role_id);
CREATE INDEX idx_user_roles_assigned_by ON user_roles (assigned_by);
```

---

## 3. Document Management

### 3.1 Documents Table

- `documents` stores only the current/latest version of each document.
- `document_versions` stores archived historical versions.

```sql
CREATE TABLE documents (
    id BIGSERIAL PRIMARY KEY,
    reference_number VARCHAR(20) UNIQUE NOT NULL, -- YYYY-NNNN format
    title VARCHAR(200) NOT NULL, -- SRS max 200 chars
    description TEXT,

    -- File info
    file_path VARCHAR(500) NOT NULL, -- SeaweedFS S3 object path
    file_hash VARCHAR(64) NOT NULL, -- SHA-256
    file_size BIGINT NOT NULL, -- bytes

    -- Dates
    issue_date DATE NOT NULL,
    effective_date DATE,
    expiration_date DATE,

    -- Status lifecycle
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    indexing_status VARCHAR(20) DEFAULT 'pending',

    -- Audience targeting
    target_audience VARCHAR(30) NOT NULL DEFAULT 'all',

    -- Versioning (current version only)
    version_number INT NOT NULL DEFAULT 1,

    uploaded_by BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,

    -- Soft delete
    deleted_at TIMESTAMPTZ,

    -- Audit timestamps
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    CONSTRAINT chk_document_status
        CHECK (status IN ('draft', 'active', 'archived', 'soft_deleted')),
    CONSTRAINT chk_indexing_status
        CHECK (indexing_status IN ('pending', 'processing', 'indexed', 'failed')),
    CONSTRAINT chk_target_audience
        CHECK (target_audience IN ('all', 'specific_institutions', 'specific_roles', 'specific_users')),
    CONSTRAINT chk_document_dates
        CHECK (
            (effective_date IS NULL OR effective_date >= issue_date) AND
            (expiration_date IS NULL OR expiration_date > issue_date)
        )
);

CREATE UNIQUE INDEX idx_documents_reference ON documents (reference_number);
CREATE INDEX idx_documents_status ON documents (status) WHERE deleted_at IS NULL;
CREATE INDEX idx_documents_uploaded_by ON documents (uploaded_by);
CREATE INDEX idx_documents_issue_date ON documents (issue_date);
CREATE INDEX idx_documents_indexing_status ON documents (indexing_status);
CREATE INDEX idx_documents_target_audience ON documents (target_audience);
CREATE INDEX idx_documents_deleted_at ON documents (deleted_at);
CREATE INDEX idx_documents_status_date ON documents (status, issue_date DESC)
    WHERE deleted_at IS NULL;
```

### 3.2 Document Versions Table

```sql
CREATE TABLE document_versions (
    id BIGSERIAL PRIMARY KEY,
    document_id BIGINT NOT NULL REFERENCES documents(id) ON DELETE CASCADE,
    version_number INT NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_hash VARCHAR(64) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'archived',
    metadata JSONB,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    created_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE (document_id, version_number)
);

CREATE INDEX idx_document_versions_document ON document_versions (document_id);
CREATE INDEX idx_document_versions_created_at ON document_versions (created_at);
```

### 3.3 Document Targets Tables (Audience)

```sql
-- Target specific institutions
CREATE TABLE document_institution_targets (
    id BIGSERIAL PRIMARY KEY,
    document_id BIGINT NOT NULL REFERENCES documents(id) ON DELETE CASCADE,
    institution_id BIGINT NOT NULL REFERENCES institutions(id) ON DELETE CASCADE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (document_id, institution_id)
);

CREATE INDEX idx_doc_inst_targets_doc ON document_institution_targets (document_id);
CREATE INDEX idx_doc_inst_targets_inst ON document_institution_targets (institution_id);

-- Target specific roles
CREATE TABLE document_role_targets (
    id BIGSERIAL PRIMARY KEY,
    document_id BIGINT NOT NULL REFERENCES documents(id) ON DELETE CASCADE,
    role_id BIGINT NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (document_id, role_id)
);

CREATE INDEX idx_doc_role_targets_doc ON document_role_targets (document_id);
CREATE INDEX idx_doc_role_targets_role ON document_role_targets (role_id);

-- Target specific users
CREATE TABLE document_user_targets (
    id BIGSERIAL PRIMARY KEY,
    document_id BIGINT NOT NULL REFERENCES documents(id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (document_id, user_id)
);

CREATE INDEX idx_doc_user_targets_doc ON document_user_targets (document_id);
CREATE INDEX idx_doc_user_targets_user ON document_user_targets (user_id);
```

---

## 4. Document Tagging System

### 4.1 Tags Table

```sql
CREATE TABLE tags (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    color VARCHAR(7), -- Hex color like '#3B82F6'
    category VARCHAR(50), -- 'type', 'priority', 'custom'
    parent_id BIGINT REFERENCES tags(id) ON DELETE SET NULL,
    is_predefined BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    created_by BIGINT REFERENCES users(id) ON DELETE SET NULL
);

CREATE UNIQUE INDEX idx_tags_slug ON tags (slug);
CREATE INDEX idx_tags_category ON tags (category);
CREATE INDEX idx_tags_parent ON tags (parent_id);
CREATE INDEX idx_tags_is_predefined ON tags (is_predefined);
```

### 4.2 Document-Tag Pivot Table

```sql
CREATE TABLE document_tags (
    document_id BIGINT NOT NULL REFERENCES documents(id) ON DELETE CASCADE,
    tag_id BIGINT NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
    assigned_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    assigned_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    PRIMARY KEY (document_id, tag_id)
);

CREATE INDEX idx_document_tags_document ON document_tags (document_id);
CREATE INDEX idx_document_tags_tag ON document_tags (tag_id);
CREATE INDEX idx_document_tags_assigned_by ON document_tags (assigned_by);
```

---

## 5. RAG Pipeline (Vector Storage)

### 5.1 Document Chunks Table with pgvector

### WARNING


Embedding dimension depends on the selected embedding model. `1536` is a placeholder and must be adjusted.

```sql
CREATE EXTENSION IF NOT EXISTS vector;

CREATE TABLE document_chunks (
    id BIGSERIAL PRIMARY KEY,
    document_id BIGINT NOT NULL REFERENCES documents(id) ON DELETE CASCADE,
    chunk_index INT NOT NULL, -- 0, 1, 2, ...
    content TEXT NOT NULL,
    embedding vector(1536),
    token_count INT,
    metadata JSONB, -- e.g. {"section":"Article 5", "page":12}
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (document_id, chunk_index)
);

CREATE INDEX idx_document_chunks_document ON document_chunks (document_id);
```

---

## 6. Download Traceability & Audit Logging

### 6.1 Download Logs Table

```sql
CREATE TABLE download_logs (
    id BIGSERIAL PRIMARY KEY,
    document_id BIGINT NOT NULL REFERENCES documents(id) ON DELETE RESTRICT,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    watermark_uuid UUID UNIQUE NOT NULL DEFAULT gen_random_uuid(),
    ip_address INET NOT NULL,
    user_agent TEXT,
    downloaded_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_download_logs_document ON download_logs (document_id);
CREATE INDEX idx_download_logs_user ON download_logs (user_id);
CREATE INDEX idx_download_logs_downloaded_at ON download_logs (downloaded_at);
CREATE UNIQUE INDEX idx_download_logs_uuid ON download_logs (watermark_uuid);
```

### 6.2 Audit Logs Table (Immutable)

```sql
CREATE TABLE audit_logs (
    id BIGSERIAL PRIMARY KEY,
    event_type VARCHAR(100) NOT NULL, -- 'SSO_LOGIN', 'DOCUMENT_DOWNLOAD', etc.
    user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    user_email VARCHAR(255),
    resource_type VARCHAR(100), -- 'document', 'user', 'role'
    resource_id BIGINT,
    metadata JSONB,
    result VARCHAR(20), -- 'success', 'failure', 'denied'
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    previous_hash VARCHAR(64) -- SHA-256(previous_row || current_row)
);

CREATE INDEX idx_audit_logs_event_type ON audit_logs (event_type);
CREATE INDEX idx_audit_logs_user ON audit_logs (user_id);
CREATE INDEX idx_audit_logs_resource ON audit_logs (resource_type, resource_id);
CREATE INDEX idx_audit_logs_created_at ON audit_logs (created_at);
CREATE INDEX idx_audit_logs_result ON audit_logs (result);

CREATE OR REPLACE FUNCTION fn_audit_immutable()
RETURNS TRIGGER AS $$
BEGIN
    RAISE EXCEPTION 'Audit logs are immutable and cannot be modified or deleted';
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_audit_no_update
BEFORE UPDATE ON audit_logs
FOR EACH ROW EXECUTE FUNCTION fn_audit_immutable();

CREATE TRIGGER trg_audit_no_delete
BEFORE DELETE ON audit_logs
FOR EACH ROW EXECUTE FUNCTION fn_audit_immutable();

COMMENT ON TABLE audit_logs IS
'Immutable audit trail - all security events logged here';
```

---

## 7. Email Notification System

```sql
CREATE TABLE notifications (
    id BIGSERIAL PRIMARY KEY,
    type VARCHAR(50) NOT NULL, -- 'document_published', 'document_updated'
    recipient_user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    document_id BIGINT REFERENCES documents(id) ON DELETE SET NULL,
    email_sent_at TIMESTAMPTZ,
    email_status VARCHAR(20), -- 'pending', 'sent', 'failed'
    email_error TEXT,
    metadata JSONB,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_notifications_recipient ON notifications (recipient_user_id);
CREATE INDEX idx_notifications_document ON notifications (document_id);
CREATE INDEX idx_notifications_status ON notifications (email_status);
CREATE INDEX idx_notifications_type ON notifications (type);
CREATE INDEX idx_notifications_created_at ON notifications (created_at);
```
