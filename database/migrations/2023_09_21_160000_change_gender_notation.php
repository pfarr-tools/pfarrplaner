<?php

use App\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $roles = Role::all();
        foreach ($roles as $role) {
            if (Str::endsWith($role->name, '*in')) {
                $role->update(['name' =>  Str::beforeLast($role->name, '*in').':in']);
            }
        }
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $roles = Role::all();
        foreach ($roles as $role) {
            if (Str::endsWith($role->name, ':in')) {
                $role->update(['name' =>  Str::beforeLast($role->name, ':in').'*in']);
            }
        }
    }



};
