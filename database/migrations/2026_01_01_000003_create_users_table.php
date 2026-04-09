<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('sso_user_id', 100)->nullable();
            $table->string('username', 100)->nullable();
            $table->string('email', 255);
            $table->text('full_name');
            $table->foreignId('institution_id')->constrained('institutions')->restrictOnDelete();
            $table->string('auth_type', 20)->default('sso');
            $table->string('auth_domain', 100)->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampTz('last_login_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->rememberToken();
            $table->timestampsTz();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
ALTER TABLE users ADD CONSTRAINT chk_auth_type CHECK (auth_type IN ('sso', 'local'));
ALTER TABLE users ADD CONSTRAINT chk_sso_no_password CHECK (
    (auth_type = 'sso' AND password IS NULL) OR (auth_type = 'local')
);
CREATE UNIQUE INDEX idx_users_email ON users (email);
CREATE INDEX idx_users_institution ON users (institution_id);
CREATE INDEX idx_users_auth_type ON users (auth_type);
CREATE INDEX idx_users_auth_domain ON users (auth_domain);
CREATE UNIQUE INDEX idx_users_sso_id ON users (sso_user_id);
CREATE INDEX idx_users_active ON users (is_active);
CREATE INDEX idx_users_email_active ON users (email, is_active);
CREATE UNIQUE INDEX idx_users_username ON users (username);
SQL);
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('email');
                $table->unique('username');
                $table->unique('sso_user_id');
                $table->index('institution_id');
                $table->index('auth_type');
                $table->index('auth_domain');
                $table->index(['email', 'is_active']);
            });
        }

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
