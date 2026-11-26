<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the optional planner grouping flag to pools.
     */
    public function up(): void
    {
        Schema::table('pools', function (Blueprint $table): void {
            $table->boolean('groupable')->default(false)->after('name');
        });
    }

    /**
     * Remove the planner grouping flag from pools.
     */
    public function down(): void
    {
        Schema::table('pools', function (Blueprint $table): void {
            $table->dropColumn('groupable');
        });
    }
};
