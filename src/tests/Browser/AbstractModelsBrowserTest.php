<?php
/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarr.tools/pfarrplaner
 * @version git: $Id$
 */

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\AbstractModelBrowserTest;

class AbstractModelsBrowserTest extends AbstractModelBrowserTest
{
    /**
     * @return array<int, array{0: class-string<\App\Models\AbstractModel>}>
     */
    public static function modelProvider(): array
    {
        return static::browserModelProvider();
    }

    #[DataProvider('modelProvider')]
    public function testIndexRouteShowsModelContent(string $modelClass): void
    {
        if (!static::hasGetRouteVerb($modelClass, 'index')) {
            $this->assertTrue(true);
            return;
        }

        $model = $this->createBrowserModel($modelClass);
        $expectedText = $this->expectedBrowserText($modelClass, $model);

        $this->browse(function (Browser $browser) use ($modelClass, $expectedText) {
            $this->assertPageLoadsWithContent($browser, $this->routeForVerb($modelClass, 'index'), $expectedText);
        });
    }

    #[DataProvider('modelProvider')]
    public function testCreateRouteLoads(string $modelClass): void
    {
        if (!static::hasGetRouteVerb($modelClass, 'create')) {
            $this->assertTrue(true);
            return;
        }

        $countBefore = $modelClass::count();

        $this->browse(function (Browser $browser) use ($modelClass) {
            $this->assertPageLoadsWithContent($browser, $this->routeForVerb($modelClass, 'create'));
        });

        $latestModel = $modelClass::query()->latest('id')->first();
        if ($latestModel && ($modelClass::count() > $countBefore)) {
            $this->assertNotNull($latestModel);
            $this->assertNotSame($latestModel->getKey(), 0);
        }
    }

    #[DataProvider('modelProvider')]
    public function testEditRouteShowsModelContent(string $modelClass): void
    {
        if (!static::hasGetRouteVerb($modelClass, 'edit')) {
            $this->assertTrue(true);
            return;
        }

        $model = $this->createBrowserModel($modelClass);
        $expectedText = $this->expectedBrowserText($modelClass, $model);

        $this->browse(function (Browser $browser) use ($modelClass, $model, $expectedText) {
            $this->assertPageLoadsWithContent(
                $browser,
                $this->routeForVerb($modelClass, 'edit', $model),
                $expectedText
            );
        });
    }

    #[DataProvider('modelProvider')]
    public function testAdminModuleRouteIsVisible(string $modelClass): void
    {
        if (!static::shouldCheckAdminModule($modelClass)) {
            $this->assertTrue(true);
            return;
        }

        auth()->login($this->testUser);
        $config = $modelClass::getAdminModuleConfig();
        auth()->logout();

        if (!$config) {
            $this->assertTrue(true);
            return;
        }

        $this->browse(function (Browser $browser) use ($config) {
            $this->assertPageLoadsWithContent($browser, route('admin.index'), $config['text']);
        });
    }
}
