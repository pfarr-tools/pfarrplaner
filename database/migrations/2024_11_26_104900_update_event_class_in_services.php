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
        // Doctrine unterstützt momentan keine Veränderungen bei ENUMs.
        DB::statement("ALTER TABLE `services` CHANGE `event_class` `event_class` ENUM ('service', 'event', 'meeting') DEFAULT ('service')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Doctrine unterstützt momentan keine Veränderungen bei ENUMs.
        DB::statement("ALTER TABLE `services` CHANGE `event_class` `event_class` ENUM ('service', 'event') DEFAULT ('service')");
    }
};
