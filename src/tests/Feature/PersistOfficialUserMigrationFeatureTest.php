<?php

namespace Tests\Feature;

use App\Models\People\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersistOfficialUserMigrationFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** Backfill account classification before legacy password flags stop being used. */
    public function test_migration_persists_official_status_and_clears_password_change_flags(): void
    {
        $migrationFiles = glob(database_path('migrations/*_persist_official_user_and_security_onboarding_state.php'));
        self::assertCount(1, $migrationFiles);
        $migration = require $migrationFiles[0];

        $migration->down();
        $official = User::factory()->create([
            'password' => 'legacy-hash',
            'must_change_password' => true,
        ]);
        $community = User::factory()->create([
            'password' => null,
            'must_change_password' => true,
        ]);

        $migration->up();

        self::assertTrue($official->fresh()->isOfficialUser);
        self::assertFalse($community->fresh()->isOfficialUser);
        self::assertFalse((bool) $official->fresh()->must_change_password);
        self::assertFalse((bool) $community->fresh()->must_change_password);
    }
}
