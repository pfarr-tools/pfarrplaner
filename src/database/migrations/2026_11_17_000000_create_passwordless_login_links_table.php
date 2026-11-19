<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Create storage for hashed, short-lived passwordless login tokens. */
    public function up(): void
    {
        Schema::create('passwordless_login_links', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'expires_at']);
        });
    }

    /** Remove passwordless login tokens when rolling this feature back. */
    public function down(): void
    {
        Schema::dropIfExists('passwordless_login_links');
    }
};
