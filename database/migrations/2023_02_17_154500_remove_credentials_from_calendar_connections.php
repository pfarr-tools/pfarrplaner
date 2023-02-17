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
            $table->dropColumn('credentials1');
            $table->dropColumn('credentials2');
            $table->dropColumn('connection_string');
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
            $table->text('credentials1')->nullable();
            $table->text('credentials2')->nullable();
            $table->text('connection_string')->nullable();
        });
    }



};
