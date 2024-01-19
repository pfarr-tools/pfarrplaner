<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('liturgy_info', function (Blueprint $table) {
            $table->string('currentPerikope')->nullable()->default('');
            $table->string('currentPerikopeLink')->nullable()->default('');
            $table->json('songs')->nullable()->change();
            $table->text('glossar')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('liturgy_info', function (Blueprint $table) {
            $table->dropColumn(['currentPerikope', 'currentPerikopeLink']);
            $table->string('songs')->nullable()->change();
            $table->string('glossar')->nullable()->change();
        });
    }
};
