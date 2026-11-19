<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Create hashed, expiring one-time email login codes. */
    public function up(): void
    {
        Schema::create('two_factor_login_challenges', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();
            $table->index(['user_id', 'expires_at']);
        });
    }

    /** Remove login challenges when rolling this feature back. */
    public function down(): void
    {
        Schema::dropIfExists('two_factor_login_challenges');
    }
};
