<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 * @version git: $Id$
 */

namespace Tests\Unit\Console;

use App\Console\Commands\DuskRunCommand;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

class DuskRunCommandTest extends TestCase
{
    public function test_dusk_uses_an_isolated_sqlite_environment_without_an_env_file(): void
    {
        $method = new ReflectionMethod(DuskRunCommand::class, 'defaultDuskEnvironment');
        $method->setAccessible(true);

        $environment = $method->invoke(new DuskRunCommand);

        self::assertSame('dusk', $environment['APP_ENV']);
        self::assertSame('true', $environment['DUSK_ENABLED']);
        self::assertSame('sqlite', $environment['DB_CONNECTION']);
        self::assertSame(dirname(__DIR__, 3).'/database/database.dusk.sqlite', $environment['DB_DATABASE']);
        self::assertSame('5000', $environment['DB_BUSY_TIMEOUT']);
        self::assertSame('WAL', $environment['DB_JOURNAL_MODE']);
        self::assertSame('array', $environment['CACHE_STORE']);
        self::assertSame('file', $environment['SESSION_DRIVER']);
        self::assertSame('sync', $environment['QUEUE_CONNECTION']);
    }

    public function test_dusk_rejects_the_official_database(): void
    {
        $method = new ReflectionMethod(DuskRunCommand::class, 'validateDuskDatabase');
        $method->setAccessible(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Dusk benötigt die isolierte SQLite-Testdatenbank');

        $method->invoke(new DuskRunCommand, [
            'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => 'pfarrplaner',
        ]);
    }

    public function test_dusk_accepts_only_its_dedicated_sqlite_database(): void
    {
        $method = new ReflectionMethod(DuskRunCommand::class, 'validateDuskDatabase');
        $method->setAccessible(true);

        $method->invoke(new DuskRunCommand, [
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => dirname(__DIR__, 3).'/database/database.dusk.sqlite',
        ]);

        self::assertTrue(true);
    }
}
