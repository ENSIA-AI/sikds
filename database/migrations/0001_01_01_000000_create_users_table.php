<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->text('name');
            $table->string('type', 20)->default('ministry');
            $table->string('domain', 100)->unique()->nullable();
            $table->string('contact_email', 255)->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('type');
            $table->index('is_active');
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('sso_user_id', 100)->unique()->nullable();
            $table->string('username', 100)->unique();
            $table->string('email', 255)->unique();
            $table->string('full_name');
            $table->unsignedBigInteger('institution_id');
            $table->string('auth_type', 20)->default('sso');
            $table->string('auth_domain', 100)->nullable();
            $table->string('password', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('institution_id')
                ->references('id')->on('institutions')
                ->onDelete('restrict');

            $table->foreign('created_by')
                ->references('id')->on('users')
                ->onDelete('set null');

            $table->index('institution_id');
            $table->index('auth_type');
            $table->index('auth_domain');
            $table->index('is_active');
            $table->index(['email', 'is_active']);
        });

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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('institutions');
    }
};
