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
        Schema::table('services', function (Blueprint $table) {
            $table->index('date');
            $table->index('slug');
            $table->index('city_id');
        });
        Schema::table('occurences', function (Blueprint $table) {
            $table->index('service_id');
            $table->index('start');
            $table->index('end');
        });
        Schema::table('absences', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('from');
            $table->index('to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['date']);
            $table->dropIndex(['slug']);
            $table->dropIndex(['city_id']);
        });
        Schema::table('occurences', function (Blueprint $table) {
            $table->dropIndex(['service_id']);
            $table->dropIndex(['start']);
            $table->dropIndex(['end']);
        });
        Schema::table('absences', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['from']);
            $table->dropIndex(['to']);
        });
    }
};
