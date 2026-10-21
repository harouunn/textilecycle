<?php

use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rôles et permissions Spatie (migration de données, additive) :
 *  - crée les rôles « admin » et « client » et leurs permissions (voir App\Support\Permissions) ;
 *  - donne à chaque compte existant le rôle Spatie correspondant à sa colonne users.role.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permissions::PAR_ROLE as $nomRole => $permissions) {
            $role = Role::findOrCreate($nomRole, 'web');
            $role->syncPermissions(collect($permissions)->map(fn (string $nom) => Permission::findOrCreate($nom, 'web')));
        }

        $roles = Role::query()->pluck('id', 'name');
        DB::table('users')->select(['id', 'role'])->orderBy('id')->each(function ($user) use ($roles) {
            if (isset($roles[$user->role])) {
                DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $roles[$user->role],
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $user->id,
                ]);
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('model_has_roles')->delete();
        DB::table('role_has_permissions')->delete();
        Role::query()->whereIn('name', array_keys(Permissions::PAR_ROLE))->delete();
        Permission::query()->whereIn('name', Permissions::toutes())->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
