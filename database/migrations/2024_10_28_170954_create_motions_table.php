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
        Schema::create('motions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body')->nullable();
            $table->text('minutes')->nullable();
            $table->unsignedBigInteger('business_id');
            $table->unsignedInteger('voted')->default(0);
            $table->unsignedInteger('aye')->default(0);
            $table->unsignedInteger('nay')->default(0);
            $table->unsignedInteger('abstention')->default(0);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('motions');
    }
};
