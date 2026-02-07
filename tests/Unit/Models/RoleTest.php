<?php

namespace WeiJuKeJi\LaravelIam\Tests\Unit\Models;

use WeiJuKeJi\LaravelIam\Models\Permission;
use WeiJuKeJi\LaravelIam\Models\Role;
use WeiJuKeJi\LaravelIam\Tests\TestCase;

class RoleTest extends TestCase
{
    /** @test */
    public function it_can_create_a_role(): void
    {
        $role = Role::create([
            'name' => 'admin',
            'guard_name' => 'sanctum',
            'display_name' => 'Administrator',
        ]);

        $this->assertDatabaseHas('iam_roles', [
            'name' => 'admin',
            'display_name' => 'Administrator',
        ]);
    }

    /** @test */
    public function it_can_have_permissions(): void
    {
        $role = Role::create(['name' => 'editor', 'guard_name' => 'sanctum']);

        $permission1 = Permission::create(['name' => 'edit-posts', 'guard_name' => 'sanctum']);
        $permission2 = Permission::create(['name' => 'delete-posts', 'guard_name' => 'sanctum']);

        $role->givePermissionTo($permission1, $permission2);

        $this->assertTrue($role->hasPermissionTo('edit-posts'));
        $this->assertTrue($role->hasPermissionTo('delete-posts'));
        $this->assertCount(2, $role->permissions);
    }

    /** @test */
    public function it_can_sync_permissions(): void
    {
        $role = Role::create(['name' => 'editor', 'guard_name' => 'sanctum']);

        $permission1 = Permission::create(['name' => 'edit-posts', 'guard_name' => 'sanctum']);
        $permission2 = Permission::create(['name' => 'delete-posts', 'guard_name' => 'sanctum']);
        $permission3 = Permission::create(['name' => 'publish-posts', 'guard_name' => 'sanctum']);

        $role->syncPermissions([$permission1, $permission2]);
        $this->assertCount(2, $role->permissions);

        $role->syncPermissions([$permission3]);
        $this->assertCount(1, $role->fresh()->permissions);
        $this->assertTrue($role->fresh()->hasPermissionTo('publish-posts'));
        $this->assertFalse($role->fresh()->hasPermissionTo('edit-posts'));
    }

    /** @test */
    public function it_resolves_user_model_correctly(): void
    {
        $role = Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);

        // 应该不抛出异常
        $users = $role->users();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $users);
    }

    /** @test */
    public function it_casts_metadata_to_array(): void
    {
        $metadata = ['color' => 'blue', 'priority' => 1];

        $role = Role::create([
            'name' => 'admin',
            'guard_name' => 'sanctum',
            'metadata' => $metadata,
        ]);

        $this->assertIsArray($role->metadata);
        $this->assertEquals($metadata, $role->metadata);
    }
}
