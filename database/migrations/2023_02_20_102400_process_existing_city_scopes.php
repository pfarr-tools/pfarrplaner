<?php

use App\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (User::all() as $user) {
            $cityIds = \App\City::whereHas('services', function ($q) use ($user) {
                $q->userParticipates($user);
            })->get()->pluck('id');
            $user->cityScopes()->sync($cityIds);
        }
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }



};
