<?php

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
        Schema::table('calendar_connections', function (Blueprint $table) {
            $table->boolean('include_vacations')->nullable();
            $table->integer('include_rite_anniversaries')->nullable()->default(0);
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('calendar_connections', function (Blueprint $table) {
            $table->dropColumn('include_vacations');
            $table->dropColumn('include_rite_anniversaries');
        });
    }



};
