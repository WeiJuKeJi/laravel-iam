<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use WeiJuKeJi\LaravelIam\Support\ConfigHelper;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = ConfigHelper::table('permissions');
        $roles = ConfigHelper::table('roles');
        $modelHasPermissions = ConfigHelper::table('model_has_permissions');
        $modelHasRoles = ConfigHelper::table('model_has_roles');
        $roleHasPermissions = ConfigHelper::table('role_has_permissions');

        Schema::create($permissions, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->string('display_name')->nullable();
            $table->string('group')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create($roles, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->string('display_name')->nullable();
            $table->string('group')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create($modelHasPermissions, function (Blueprint $table) use ($permissions) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type'], substr($table->getTable() . '_model_id_model_type_index', 0, 64));

            $table->foreign('permission_id')
                ->references('id')
                ->on($permissions)
                ->onDelete('cascade');

            $table->primary(['permission_id', 'model_id', 'model_type'], substr($table->getTable() . '_permission_model_type_primary', 0, 64));
        });

        Schema::create($modelHasRoles, function (Blueprint $table) use ($roles) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type'], substr($table->getTable() . '_model_id_model_type_index', 0, 64));

            $table->foreign('role_id')
                ->references('id')
                ->on($roles)
                ->onDelete('cascade');

            $table->primary(['role_id', 'model_id', 'model_type'], substr($table->getTable() . '_role_model_type_primary', 0, 64));
        });

        Schema::create($roleHasPermissions, function (Blueprint $table) use ($permissions, $roles) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');

            $table->foreign('permission_id')
                ->references('id')
                ->on($permissions)
                ->onDelete('cascade');

            $table->foreign('role_id')
                ->references('id')
                ->on($roles)
                ->onDelete('cascade');

            $table->primary(['permission_id', 'role_id'], substr($table->getTable() . '_permission_id_role_id_primary', 0, 64));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(ConfigHelper::table('role_has_permissions'));
        Schema::dropIfExists(ConfigHelper::table('model_has_roles'));
        Schema::dropIfExists(ConfigHelper::table('model_has_permissions'));
        Schema::dropIfExists(ConfigHelper::table('roles'));
        Schema::dropIfExists(ConfigHelper::table('permissions'));
    }
};
