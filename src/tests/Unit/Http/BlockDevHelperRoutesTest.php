<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 */

namespace Tests\Unit\Http;

use App\Http\Middleware\BlockDevHelperRoutes;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Exercise the helper-route boundary without booting services or using a database.
 */
class BlockDevHelperRoutesTest extends TestCase
{
    /**
     * Verify access decisions while restoring the process environment and container.
     */
    #[DataProvider('accessCases')]
    public function test_helper_access_requires_an_explicit_dusk_environment(
        string $environment,
        ?string $enabled,
        string $path,
        bool $allowed,
    ): void {
        $previousApp = Container::getInstance();
        $repository = Env::getRepository();
        $previousFlag = $repository->get('DUSK_ENABLED');
        $app = new Application;
        $app['env'] = $environment;
        $repository->clear('DUSK_ENABLED');
        if ($enabled !== null) {
            $repository->set('DUSK_ENABLED', $enabled);
        }

        try {
            $result = (new BlockDevHelperRoutes)->handle(
                Request::create($path),
                static fn () => 'continued',
            );
            self::assertTrue($allowed, 'A helper route escaped its dedicated test environment.');
            self::assertSame('continued', $result);
        } catch (HttpException $exception) {
            self::assertFalse($allowed, 'An allowed request was blocked.');
            self::assertSame(404, $exception->getStatusCode());
        } finally {
            $repository->clear('DUSK_ENABLED');
            if ($previousFlag !== null) {
                $repository->set('DUSK_ENABLED', $previousFlag);
            }
            Container::setInstance($previousApp);
        }
    }

    /**
     * Cover helper endpoints, disabled flags and normal routes across environments.
     *
     * @return iterable<string, array{string, ?string, string, bool}>
     */
    public static function accessCases(): iterable
    {
        foreach (['production', 'local', 'testing', 'staging', 'dusk'] as $environment) {
            foreach ([null, 'false', 'true'] as $enabled) {
                foreach (['_dusk', '_dusk/login/1', '_dusk/logout', '_dusk/user', 'login'] as $path) {
                    yield $environment.'/'.($enabled ?? 'unset').'/'.$path => [
                        $environment,
                        $enabled,
                        '/'.$path,
                        $path === 'login' || ($environment === 'dusk' && $enabled === 'true'),
                    ];
                }
            }
        }
    }
}
