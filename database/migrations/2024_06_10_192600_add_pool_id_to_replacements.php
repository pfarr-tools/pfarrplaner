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
        Schema::table('replacements', function (Blueprint $table) {
            $table->unsignedBigInteger('pool_id')->nullable();
            $table->foreign('pool_id')->references('id')->on('pools')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('replacements', function (Blueprint $table) {
            $table->dropColumn(['pool_id']);
        });
    }
};
