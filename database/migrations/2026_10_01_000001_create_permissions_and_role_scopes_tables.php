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
        // 1. Ampliar tabla roles con metadatos visuales y de protección
        Schema::table('roles', function (Blueprint $table) {
            $table->string('color', 50)->nullable()->after('description');
            $table->string('badge_bg', 50)->nullable()->after('color');
            $table->string('badge_text', 50)->nullable()->after('badge_bg');
            $table->string('badge_border', 50)->nullable()->after('badge_text');
            $table->boolean('is_protected')->default(false)->after('badge_border');
        });

        // 2. Tabla de permisos granulares
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('module', 50)->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 3. Tabla pivote roles <-> permisos
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
        });

        // 4. Tabla de alcances operativos entre roles (matriz de interacción)
        Schema::create('role_operational_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('target_role_id')->constrained('roles')->cascadeOnDelete();
            $table->string('scope_type', 20); // 'view', 'attend', 'assign'
            $table->timestamps();

            $table->unique(['source_role_id', 'target_role_id', 'scope_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_operational_scopes');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['color', 'badge_bg', 'badge_text', 'badge_border', 'is_protected']);
        });
    }
};
