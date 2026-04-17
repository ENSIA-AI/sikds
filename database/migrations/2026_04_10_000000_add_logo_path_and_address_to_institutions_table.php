<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            if (! Schema::hasColumn('institutions', 'logo_path')) {
                $table->string('logo_path', 500)->nullable();
            }
            if (! Schema::hasColumn('institutions', 'address')) {
                $table->text('address')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            if (Schema::hasColumn('institutions', 'logo_path')) {
                $table->dropColumn('logo_path');
            }
            if (Schema::hasColumn('institutions', 'address')) {
                $table->dropColumn('address');
            }
        });
    }
};
