<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Spatie Laravel Permission 5-table schema, default names, Teams disabled.
// Requires the installed package's config/permission.php to be available.
return new class extends Migration
{
    public function up(): void
    {
        $tables = config('permission.table_names');
        $columns = config('permission.column_names');
        $teams = config('permission.teams', false);

        if (empty($tables) || empty($columns)) {
            throw new RuntimeException('Install spatie/laravel-permission and publish config/permission.php first.');
        }
        if ($teams) {
            throw new RuntimeException('This ERD uses Spatie Permission without Teams. Publish the official Teams migration instead.');
        }

        $roleKey = $columns['role_pivot_key'] ?? 'role_id';
        $permissionKey = $columns['permission_pivot_key'] ?? 'permission_id';
        $modelKey = $columns['model_morph_key'] ?? 'model_id';

        Schema::create($tables['permissions'], function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tables['roles'], function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tables['model_has_permissions'], function (Blueprint $table) use ($tables, $permissionKey, $modelKey) {
            $table->unsignedBigInteger($permissionKey);
            $table->string('model_type');
            $table->unsignedBigInteger($modelKey);
            $table->index([$modelKey, 'model_type'], 'model_has_permissions_model_id_model_type_index');
            $table->foreign($permissionKey)->references('id')->on($tables['permissions'])->cascadeOnDelete();
            $table->primary([$permissionKey, $modelKey, 'model_type'], 'model_has_permissions_permission_model_type_primary');
        });

        Schema::create($tables['model_has_roles'], function (Blueprint $table) use ($tables, $roleKey, $modelKey) {
            $table->unsignedBigInteger($roleKey);
            $table->string('model_type');
            $table->unsignedBigInteger($modelKey);
            $table->index([$modelKey, 'model_type'], 'model_has_roles_model_id_model_type_index');
            $table->foreign($roleKey)->references('id')->on($tables['roles'])->cascadeOnDelete();
            $table->primary([$roleKey, $modelKey, 'model_type'], 'model_has_roles_role_model_type_primary');
        });

        Schema::create($tables['role_has_permissions'], function (Blueprint $table) use ($tables, $roleKey, $permissionKey) {
            $table->unsignedBigInteger($permissionKey);
            $table->unsignedBigInteger($roleKey);
            $table->foreign($permissionKey)->references('id')->on($tables['permissions'])->cascadeOnDelete();
            $table->foreign($roleKey)->references('id')->on($tables['roles'])->cascadeOnDelete();
            $table->primary([$permissionKey, $roleKey], 'role_has_permissions_permission_id_role_id_primary');
        });

        // The official Spatie migration clears permission cache at the end as well.
        $cacheStore = config('permission.cache.store');
        $cacheKey = config('permission.cache.key');
        if ($cacheKey) {
            app('cache')->store($cacheStore && $cacheStore !== 'default' ? $cacheStore : null)->forget($cacheKey);
        }
    }

    public function down(): void
    {
        $tables = config('permission.table_names');
        if (empty($tables)) {
            throw new RuntimeException('Spatie Permission config/permission.php must be installed for rollback.');
        }
        Schema::dropIfExists($tables['role_has_permissions']);
        Schema::dropIfExists($tables['model_has_roles']);
        Schema::dropIfExists($tables['model_has_permissions']);
        Schema::dropIfExists($tables['roles']);
        Schema::dropIfExists($tables['permissions']);
    }
};
