<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Create the explicit pool visibility assignments for users. */
    public function up(): void
    {
        Schema::create('pool_user_visibility', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('pool_id');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('pool_id')->references('id')->on('pools')->cascadeOnDelete();
            $table->unique(['user_id', 'pool_id']);
        });
    }

    /** Remove the explicit pool visibility assignments. */
    public function down(): void
    {
        Schema::dropIfExists('pool_user_visibility');
    }
};
