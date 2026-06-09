<?php

/*
|--------------------------------------------------------------------------
| Migration: create_permission_tables          (Task T-3.4)
|--------------------------------------------------------------------------
| Purpose:       RBAC storage for Spatie laravel-permission — roles,
|                permissions, and their assignments to users (D-021). Uses
|                Spatie's default table names (match DATABASE_BLUEPRINT).
| Decision IDs:  D-021 (RBAC), D-040 (14-role model retained, granular).
| Security:      The single server-side source of truth for authorization.
|                Default-deny + least privilege enforced in policies/gates
|                (T-5.4). `guard_name` scopes roles/permissions per guard.
|                Contains no PII.
| Dependencies:  Polymorphic morph to core_users (model_has_roles /
|                model_has_permissions); FKs to roles & permissions.
| Extension pts: Spatie "teams" column is intentionally omitted now (single
|                RBAC scope); teams can be enabled for tenant-scoped roles in
|                Phase 3 (multi-tenancy) via a blueprint amendment. Catalogue
|                + role→permission mapping are seeded in Task 5 (T-5.1/2/3).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
            $table->primary(['permission_id', 'model_id', 'model_type'],
                'model_has_permissions_permission_model_type_primary');
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->primary(['role_id', 'model_id', 'model_type'],
                'model_has_roles_role_model_type_primary');
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');

            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id'],
                'role_has_permissions_permission_id_role_id_primary');
        });

        app('cache')->store(config('cache.default'))->forget('spatie.permission.cache');
    }

    public function down(): void
    {
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }
};
