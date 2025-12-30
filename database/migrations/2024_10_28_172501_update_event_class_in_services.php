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
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // SQLite does not support altering enum columns, which will cause this migration to fail all tests.
            return;
        }
        DB::statement("ALTER TABLE `services` CHANGE `event_class` `event_class` ENUM ('service', 'event', 'meeting')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // SQLite does not support altering enum columns, which will cause this migration to fail all tests.
            return;
        }
        DB::statement("ALTER TABLE `services` CHANGE `event_class` `event_class` ENUM ('service', 'event')");
    }
};
