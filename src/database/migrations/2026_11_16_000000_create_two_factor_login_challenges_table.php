<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Create or complete storage for hashed, expiring one-time email login codes. */
    public function up(): void
    {
        if (! Schema::hasTable('two_factor_login_challenges')) {
            Schema::create('two_factor_login_challenges', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->string('code_hash');
                $table->timestamp('expires_at');
                $table->timestamp('consumed_at')->nullable();
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->timestamps();
                $table->index(['user_id', 'expires_at']);
            });

            return;
        }

        $this->completeExistingTable();
    }

    /** Complete a table left behind when an earlier MySQL foreign-key DDL failed. */
    private function completeExistingTable(): void
    {
        $userIdColumn = collect(Schema::getColumns('two_factor_login_challenges'))->firstWhere('name', 'user_id');

        if (! $userIdColumn) {
            throw new LogicException('Die Tabelle two_factor_login_challenges hat keine user_id-Spalte.');
        }

        if (
            strtolower($userIdColumn['type_name'] ?? '') !== 'bigint'
            || ! str_contains(strtolower($userIdColumn['type'] ?? ''), 'unsigned')
        ) {
            Schema::table('two_factor_login_challenges', function (Blueprint $table): void {
                $table->unsignedBigInteger('user_id')->change();
            });
        }

        $foreignKey = collect(Schema::getForeignKeys('two_factor_login_challenges'))
            ->firstWhere('columns', ['user_id']);

        if ($foreignKey && $foreignKey['foreign_table'] === 'users' && $foreignKey['foreign_columns'] === ['id']) {
            return;
        }

        if ($foreignKey) {
            Schema::table('two_factor_login_challenges', function (Blueprint $table) use ($foreignKey): void {
                $table->dropForeign((string) ($foreignKey['name'] ?? ''));
            });
        }

        $hasOrphanedChallenges = DB::table('two_factor_login_challenges as challenges')
            ->leftJoin('users', 'users.id', '=', 'challenges.user_id')
            ->whereNull('users.id')
            ->exists();

        if ($hasOrphanedChallenges) {
            throw new LogicException(
                'Die Tabelle two_factor_login_challenges enthält Benutzer-IDs ohne passenden Benutzer.'
            );
        }

        Schema::table('two_factor_login_challenges', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /** Remove login challenges when rolling this feature back. */
    public function down(): void
    {
        Schema::dropIfExists('two_factor_login_challenges');
    }
};
