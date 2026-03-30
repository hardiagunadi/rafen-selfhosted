<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
});

it('shows internal user management pages for super admin and blocks non admin users', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('super-admin.users.index'))
        ->assertSuccessful()
        ->assertSee('Pengguna Internal');

    $this->actingAs($admin)
        ->get(route('super-admin.users.create'))
        ->assertSuccessful()
        ->assertSee('Tambah Pengguna');

    $this->actingAs($user)
        ->get(route('super-admin.users.index'))
        ->assertForbidden();
});

it('creates updates and deletes internal users', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->post(route('super-admin.users.store'), [
            'name' => 'Teknisi Field',
            'email' => 'teknisi@example.com',
            'password' => 'password123',
            'role' => 'teknisi',
            'phone' => '081234567890',
            'nickname' => 'Field Tech',
        ])
        ->assertRedirect(route('super-admin.users.index'))
        ->assertSessionHas('success');

    $user = User::query()->where('email', 'teknisi@example.com')->firstOrFail();

    expect($user->role)->toBe('teknisi')
        ->and($user->phone)->toBe('081234567890')
        ->and(Hash::check('password123', (string) $user->password))->toBeTrue();

    $this->actingAs($admin)
        ->put(route('super-admin.users.update', $user), [
            'name' => 'Teknisi NOC',
            'email' => 'teknisi@example.com',
            'password' => '',
            'role' => 'noc',
            'phone' => null,
            'nickname' => 'NOC Tech',
        ])
        ->assertRedirect(route('super-admin.users.index'))
        ->assertSessionHas('success');

    $user->refresh();

    expect($user->name)->toBe('Teknisi NOC')
        ->and($user->role)->toBe('noc')
        ->and($user->nickname)->toBe('NOC Tech');

    $this->actingAs($admin)
        ->delete(route('super-admin.users.destroy', $user))
        ->assertRedirect(route('super-admin.users.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

it('updates last login time after successful login', function () {
    $user = User::factory()->superAdmin()->create([
        'email' => 'admin@example.com',
        'password' => 'password123',
        'last_login_at' => null,
    ]);

    $this->post(route('login.attempt'), [
        'email' => 'admin@example.com',
        'password' => 'password123',
    ])->assertRedirect(route('super-admin.dashboard'));

    expect($user->fresh()?->last_login_at)->not->toBeNull();
});
