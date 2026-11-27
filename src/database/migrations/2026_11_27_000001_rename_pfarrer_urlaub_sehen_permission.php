<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Rename the initially introduced permission without losing assignments. */
    public function up(): void
    {
        $this->renamePermission('pfarrer_urlaub_sehen', 'pfarrer_urlaub_lesen');
    }

    /** Restore the previous permission name when rolling back. */
    public function down(): void
    {
        $this->renamePermission('pfarrer_urlaub_lesen', 'pfarrer_urlaub_sehen');
    }

    /** Merge an already existing target permission while preserving assignments. */
    private function renamePermission(string $from, string $to): void
    {
        $oldPermission = DB::table('permissions')
            ->where('name', $from)
            ->where('guard_name', 'web')
            ->first();

        if ($oldPermission === null) {
            return;
        }

        $newPermission = DB::table('permissions')
            ->where('name', $to)
            ->where('guard_name', 'web')
            ->first();

        if ($newPermission === null) {
            DB::table('permissions')
                ->where('id', $oldPermission->id)
                ->update(['name' => $to]);

            return;
        }

        DB::table('role_has_permissions')
            ->where('permission_id', $oldPermission->id)
            ->get()
            ->each(function (object $assignment) use ($newPermission): void {
                if (! DB::table('role_has_permissions')
                    ->where('permission_id', $newPermission->id)
                    ->where('role_id', $assignment->role_id)
                    ->exists()) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $newPermission->id,
                        'role_id' => $assignment->role_id,
                    ]);
                }
            });

        DB::table('model_has_permissions')
            ->where('permission_id', $oldPermission->id)
            ->get()
            ->each(function (object $assignment) use ($newPermission): void {
                if (! DB::table('model_has_permissions')
                    ->where('permission_id', $newPermission->id)
                    ->where('model_type', $assignment->model_type)
                    ->where('model_id', $assignment->model_id)
                    ->exists()) {
                    DB::table('model_has_permissions')->insert([
                        'permission_id' => $newPermission->id,
                        'model_type' => $assignment->model_type,
                        'model_id' => $assignment->model_id,
                    ]);
                }
            });

        DB::table('role_has_permissions')->where('permission_id', $oldPermission->id)->delete();
        DB::table('model_has_permissions')->where('permission_id', $oldPermission->id)->delete();
        DB::table('permissions')->where('id', $oldPermission->id)->delete();
    }
};
