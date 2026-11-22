<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Hash legacy plaintext API tokens without changing their client credential. */
    public function up(): void
    {
        DB::table('users')
            ->whereNotNull('api_token')
            ->orderBy('id')
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    $token = $user->api_token;
                    if (! is_string($token) || $token === '' || preg_match('/\A[a-f0-9]{64}\z/', $token)) {
                        continue;
                    }

                    DB::table('users')
                        ->where('id', $user->id)
                        ->where('api_token', $token)
                        ->update(['api_token' => hash('sha256', $token)]);
                }
            });
    }

    /** Keep existing credentials usable by restoring the verified database backup. */
    public function down(): void
    {
        throw new LogicException(
            'Gehashte API-Tokens sind nicht rückwärts migrierbar; verwenden Sie den dokumentierten Datenbank-Restore.'
        );
    }
};
