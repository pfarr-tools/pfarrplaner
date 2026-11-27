<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 * @version git: $
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add configuration and immutable records for electronically signed offerings.
 */
return new class extends Migration
{
    /**
     * Create the settlement storage and city-level feature switch.
     */
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table): void {
            $table->boolean('electronic_offering_signatures')->default(false);
        });

        Schema::create('offering_settlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('revision')->default(1);
            $table->json('snapshot');
            $table->json('signatures');
            $table->json('manifest')->nullable();
            $table->string('status', 32)->default('pending');
            $table->string('snapshot_hash', 64);
            $table->string('manifest_hash', 64)->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('pdf_hash', 64)->nullable();
            $table->dateTime('finalized_at')->nullable();
            $table->timestamps();
            $table->unique(['service_id', 'revision']);
        });

        Schema::create('offering_settlement_corrections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('previous_settlement_id')->constrained('offering_settlements')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->text('reason');
            $table->timestamps();
        });
    }

    /**
     * Remove settlement storage and the feature switch.
     */
    public function down(): void
    {
        Schema::dropIfExists('offering_settlement_corrections');
        Schema::dropIfExists('offering_settlements');
        Schema::table('cities', function (Blueprint $table): void {
            $table->dropColumn('electronic_offering_signatures');
        });
    }
};
