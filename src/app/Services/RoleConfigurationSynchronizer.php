<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 */

namespace App\Services;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Synchronizes the versioned role configuration with Spatie Permission.
 */
class RoleConfigurationSynchronizer
{
    /**
     * @param  PermissionRegistrar  $permissionRegistrar  Permission cache registrar.
     */
    public function __construct(private PermissionRegistrar $permissionRegistrar)
    {
    }

    /**
     * Synchronize configured roles and permissions without deleting unknown records.
     *
     * @param  bool  $dryRun  Whether database writes should be skipped.
     * @return RoleConfigurationSyncResult Synchronization counters.
     */
    public function synchronize(bool $dryRun = false): RoleConfigurationSyncResult
    {
        $configuration = config('roles');
        $guard = $configuration['guard'];
        $result = new RoleConfigurationSyncResult;

        foreach ($configuration['permissions'] as $permissionName) {
            $permission = Permission::query()
                ->where('name', $permissionName)
                ->where('guard_name', $guard)
                ->first();

            if ($permission) {
                $result->unchanged++;

                continue;
            }

            $result->created++;
            $result->changes[] = "Berechtigung: {$permissionName}";
            if (! $dryRun) {
                Permission::create(['name' => $permissionName, 'guard_name' => $guard]);
            }
        }

        foreach ($configuration['roles'] as $roleName => $permissionNames) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', $guard)
                ->first();

            if (! $role) {
                $result->created++;
                $result->changes[] = "Rolle: {$roleName}";
                if (! $dryRun) {
                    $role = Role::create(['name' => $roleName, 'guard_name' => $guard]);
                }
            }

            if ($dryRun) {
                continue;
            }

            $configuredPermissions = Permission::query()
                ->whereIn('name', $permissionNames)
                ->where('guard_name', $guard)
                ->get();
            $currentPermissionNames = $role->permissions->pluck('name')->sort()->values()->all();
            $configuredPermissionNames = $configuredPermissions->pluck('name')->sort()->values()->all();

            if ($currentPermissionNames === $configuredPermissionNames) {
                $result->unchanged++;

                continue;
            }

            $role->syncPermissions($configuredPermissions);
            $result->updated++;
            $result->changes[] = "Rolle: {$roleName} (Berechtigungen)";
        }

        if (! $dryRun) {
            $this->permissionRegistrar->forgetCachedPermissions();
        }

        return $result;
    }
}
