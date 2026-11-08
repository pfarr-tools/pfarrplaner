<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Create the resumable upload session table. */
    public function up(): void
    {
        Schema::create('attachment_upload_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('attachable_type');
            $table->unsignedBigInteger('attachable_id');
            $table->string('filename');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('chunk_size');
            $table->unsignedInteger('total_chunks');
            $table->json('received_chunks')->default('[]');
            $table->string('title');
            $table->string('cut')->nullable();
            $table->unsignedInteger('attachment_id')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['attachable_type', 'attachable_id']);
        });
    }

    /** Drop the resumable upload session table. */
    public function down(): void
    {
        Schema::dropIfExists('attachment_upload_sessions');
    }
};
