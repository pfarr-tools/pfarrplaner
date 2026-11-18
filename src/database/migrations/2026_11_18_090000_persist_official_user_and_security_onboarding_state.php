<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Add durable account classification and reminder state, then backfill legacy users. */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_official_user')->default(false)->after('email');
            $table->unsignedInteger('security_onboarding_email_logins')->default(0);
            $table->timestamp('security_onboarding_last_shown_at')->nullable();
        });

        DB::table('users')->whereNotNull('password')->where('password', '!=', '')
            ->update(['is_official_user' => true]);
        DB::table('users')->update(['must_change_password' => false]);
    }

    /** Remove the columns introduced by this migration. */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'is_official_user',
                'security_onboarding_email_logins',
                'security_onboarding_last_shown_at',
            ]);
        });
    }
};
