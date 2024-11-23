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
        Schema::create('committees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->default(null);
            $table->boolean('has_public_meetings')->default(false);
            $table->boolean('has_private_meetings')->default(true);
            $table->timestamps();
        });

        Schema::create('committee_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('committee_id');
            $table->unsignedInteger('user_id');
            $table->string('role')->default('Beisitzer:in');
            $table->timestamps();

            $table->foreign('committee_id')->references('id')->on('committees')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::create('city_committee', function (Blueprint $table) {
            $table->unsignedInteger('city_id');
            $table->unsignedBigInteger('committee_id');
            $table->foreign('city_id')->references('id')->on('cities');
            $table->foreign('committee_id')->references('id')->on('committees')->cascadeOnDelete();
        });

        Schema::create('committee_service', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('service_id');
            $table->unsignedBigInteger('committee_id');
            $table->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
            $table->foreign('committee_id')->references('id')->on('committees')->cascadeOnDelete();
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->unsignedBigInteger('board_committee_id')->nullable()->default(null);
            $table->foreign('board_committee_id')->references('id')->on('committees')->cascadeOnDelete();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('committees');
        Schema::dropIfExists('committee_user');
        Schema::dropIfExists('city_commitee');
        Schema::dropIfExists('committee_service');
        Schema::table('cities', function (Blueprint $table) {
            $table->dropForeign(['board_committee_id']);
            $table->dropColumn(['board_committee_id']);
        });
    }
};
