<?php

namespace WeiJuKeJi\LaravelIam\Tests\Unit\Models;

use Illuminate\Support\Facades\Hash;
use WeiJuKeJi\LaravelIam\Models\Permission;
use WeiJuKeJi\LaravelIam\Models\Role;
use WeiJuKeJi\LaravelIam\Models\User;
use WeiJuKeJi\LaravelIam\Tests\TestCase;

class UserTest extends TestCase
{
    /** @test */
    public function it_can_create_a_user(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        $this->assertNotEquals('password123', $user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /** @test */
    public function it_hashes_password_automatically(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'plaintext',
        ]);

        $this->assertTrue(Hash::check('plaintext', $user->password));
        $this->assertNotEquals('plaintext', $user->password);
    }

    /** @test */
    public function it_does_not_rehash_already_hashed_password(): void
    {
        $hashedPassword = Hash::make('password123');

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => $hashedPassword,
        ]);

        $this->assertEquals($hashedPassword, $user->password);
    }

    /** @test */
    public function it_can_have_roles(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $role = Role::create([
            'name' => 'admin',
            'guard_name' => 'sanctum',
        ]);

        $user->assignRole($role);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->roles->contains($role));
    }

    /** @test */
    public function it_can_have_permissions(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'edit-posts',
            'guard_name' => 'sanctum',
        ]);

        $user->givePermissionTo($permission);

        $this->assertTrue($user->hasPermissionTo('edit-posts'));
    }

    /** @test */
    public function it_can_soft_delete(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $user->delete();

        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'test@example.com',
        ]);
    }

    /** @test */
    public function it_can_restore_soft_deleted_user(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $user->delete();
        $this->assertSoftDeleted('users', ['id' => $user->id]);

        $user->restore();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function it_casts_metadata_to_array(): void
    {
        $metadata = ['key' => 'value', 'foo' => 'bar'];

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'metadata' => $metadata,
        ]);

        $this->assertIsArray($user->metadata);
        $this->assertEquals($metadata, $user->metadata);
    }
}
