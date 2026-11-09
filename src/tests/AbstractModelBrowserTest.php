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
 *
 *
 * Pfarrplaner is based on the Laravel framework (https://laravel.com).
 * This file may contain code created by Laravel's scaffolding functions.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tests;

use App\Models\AbstractModel as BaseAbstractModel;
use App\Models\Ads\AdConfig;
use App\Models\Attachment;
use App\Models\AttachmentUploadSession;
use App\Models\Comment;
use App\Models\Leave\Poolmaster;
use App\Models\Occurence;
use App\Models\Parish;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Replacement;
use App\Models\Rites\Funeral;
use App\Models\Rites\Wedding;
use App\Models\Seating\SeatingRow;
use App\Models\Seating\SeatingSection;
use App\Models\ServiceGroup;
use App\Models\StreetRange;
use App\Services\RoleService;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;

abstract class AbstractModelBrowserTest extends DuskTestCase
{
    use DatabaseTruncation;

    protected $modelclass;

    protected $seeder = RoleSeeder::class;

    protected const MODEL_FIELD_CANDIDATES = [
        'name',
        'title',
        'candidate_name',
        'buried_name',
        'spouse1_name',
        'text',
        'code',
    ];

    protected const EXCLUDED_MODEL_CLASSES = [
        AdConfig::class,
        Attachment::class,
        AttachmentUploadSession::class,
        Comment::class,
        Occurence::class,
        Parish::class,
        Poolmaster::class,
        Replacement::class,
        Funeral::class,
        Wedding::class,
        SeatingRow::class,
        SeatingSection::class,
        ServiceGroup::class,
        StreetRange::class,
    ];

    /**
     * Test, if an Admin module is present
     *
     * @return void
     */
    public function test_has_admin_module()
    {
        if (empty($this->modelClass)) {
            $this->assertTrue(true);

            return;
        }
        if ($this->skipTestIfNoAdminModule()) {
            return;
        }
        auth()->login($this->testUser);
        $config = ($this->modelClass)::getAdminModuleConfig();
        auth()->logout();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->testUser, 'web')
                ->visit(route('admin.index'))
                ->waitFor('#app', 10)
                ->assertDontSee('500')
                ->assertDontSee('Whoops')
                ->assertDontSee('404');
        });

        $this->browse(function (Browser $browser) use ($config) {
            $browser->loginAs($this->testUser, 'web')
                ->visit(route('admin.index'))
                ->waitFor('#app', 10)
                ->assertSee($config['text']);
        });
    }

    /**
     * @return void
     */
    public function test_new_record_is_visible_in_index()
    {
        if (empty($this->modelClass)) {
            $this->assertTrue(true);

            return;
        }
        if ($this->skipTestIfNoAdminModule()) {
            return;
        }
        $existing = $this->createBrowserModel($this->modelClass);
        $this->assertTrue($this->testUser->can('update', $existing));
        $this->browse(function (Browser $browser) use ($existing) {
            $browser->loginAs($this->testUser, 'web')->visit(($this->modelClass)::getSingleRoute('web', 'index'))
                ->waitFor('#app', 10)
                ->assertSee($this->expectedBrowserText($this->modelClass, $existing));
        });

    }

    /**
     * @return array<int, array{0: class-string<BaseAbstractModel>}>
     */
    protected static function browserModelProvider(): array
    {
        $models = [];
        $directory = new \RecursiveDirectoryIterator(dirname(__DIR__).'/app/Models');
        $iterator = new \RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            if ((! $file->isFile()) || ($file->getExtension() !== 'php') || Str::contains($file->getPathname(), 'Abstract')) {
                continue;
            }

            $relativePath = Str::after($file->getPathname(), dirname(__DIR__).'/app/Models/');
            $className = substr('App\\Models\\'.Str::replace(DIRECTORY_SEPARATOR, '\\', $relativePath), 0, -4);
            if (! class_exists($className)
                || ! is_subclass_of($className, BaseAbstractModel::class)
                || in_array($className, static::EXCLUDED_MODEL_CLASSES, true)
            ) {
                continue;
            }

            if (count(static::getGetRouteVerbs($className)) === 0) {
                continue;
            }

            $models[] = [$className];
        }

        usort($models, fn (array $left, array $right) => strcmp($left[0], $right[0]));

        return $models;
    }

    /**
     * @param  class-string<BaseAbstractModel>  $modelClass
     * @return string[]
     */
    protected static function getGetRouteVerbs(string $modelClass): array
    {
        $verbs = [];
        foreach (($modelClass)::getRoutes()['web'] ?? [] as $routeData) {
            if (in_array('GET', $routeData['httpVerbs'], true) || in_array('HEAD', $routeData['httpVerbs'], true)) {
                $verbs[] = $routeData['verb'];
            }
        }

        return array_values(array_unique($verbs));
    }

    /**
     * @param  class-string<BaseAbstractModel>  $modelClass
     */
    protected static function hasGetRouteVerb(string $modelClass, string $verb): bool
    {
        return in_array($verb, static::getGetRouteVerbs($modelClass), true);
    }

    /**
     * @param  class-string<BaseAbstractModel>  $modelClass
     */
    protected static function shouldCheckAdminModule(string $modelClass): bool
    {
        if ($modelClass === City::class) {
            return false;
        }

        return ! empty($modelClass::$adminIcon ?? null);
    }

    /**
     * @param  class-string<BaseAbstractModel>  $modelClass
     * @return Model&BaseAbstractModel
     */
    protected function createBrowserModel(string $modelClass): Model
    {
        /** @var Model&BaseAbstractModel $model */
        $model = $modelClass::factory()->create();
        $field = $this->browserFieldName($modelClass, $model);

        if ($field && ($value = $this->browserTextSeed($modelClass, $field))) {
            $model->{$field} = $value;
            $model->save();
            $model->refresh();
        }

        $this->prepareBrowserAccessForModel($model);

        return $model;
    }

    /**
     * Ensure the browser user can see city-scoped models created by factories.
     *
     * @param  Model&BaseAbstractModel  $model
     */
    protected function prepareBrowserAccessForModel(Model $model): void
    {
        if (! $model->getAttribute('city_id')) {
            return;
        }

        $this->testUser->cities()->syncWithoutDetaching([
            $model->getAttribute('city_id') => ['permission' => 'w'],
        ]);
        $this->testUser->unsetRelation('cities');
        $this->testUser->unsetRelation('writableCities');
    }

    /**
     * @param  class-string<BaseAbstractModel>  $modelClass
     * @param  Model&BaseAbstractModel  $model
     */
    protected function browserFieldName(string $modelClass, Model $model): ?string
    {
        foreach (static::MODEL_FIELD_CANDIDATES as $field) {
            if (array_key_exists($field, $model->getAttributes())) {
                return $field;
            }
        }

        return null;
    }

    /**
     * @param  class-string<BaseAbstractModel>  $modelClass
     */
    protected function browserTextSeed(string $modelClass, string $field): ?string
    {
        $basename = Str::of(class_basename($modelClass))->headline()->replace(' ', '');
        $suffix = match ($field) {
            'code' => 'BT'.strtoupper(substr($basename, 0, 1)),
            default => 'Browser Test '.$basename,
        };

        return $suffix;
    }

    /**
     * @param  class-string<BaseAbstractModel>  $modelClass
     * @param  Model&BaseAbstractModel|null  $model
     */
    protected function expectedBrowserText(string $modelClass, ?Model $model = null): string
    {
        if ($model) {
            $field = $this->browserFieldName($modelClass, $model);
            if ($field && filled($model->{$field})) {
                return (string) $model->{$field};
            }

            if (filled($model->label ?? null)) {
                return (string) $model->label;
            }

            return (string) $model->getKey();
        }

        return class_basename($modelClass);
    }

    /**
     * @param  class-string<BaseAbstractModel>  $modelClass
     * @param  Model&BaseAbstractModel|null  $model
     */
    protected function routeForVerb(string $modelClass, string $verb, ?Model $model = null): string
    {
        $routeName = $modelClass::getSingleRouteName('web', $verb);
        $parameters = $model ? ['modelId' => $model->getKey()] : [];

        return route($routeName, $parameters);
    }

    protected function assertPageLoadsWithContent(Browser $browser, string $url, ?string $expectedText = null): void
    {
        $browser->loginAs($this->testUser, 'web')
            ->visit($url)
            ->waitFor('#app', 10)
            ->assertDontSee('500')
            ->assertDontSee('Whoops')
            ->assertDontSee('404');

        if ($expectedText) {
            $browser->assertSourceHas($expectedText);
        }
    }

    /**
     * Skip the test, if this model has no separate admin module
     */
    protected function skipTestIfNoAdminModule(): bool
    {
        if (empty($this->modelClass)) {
            return true;
        }
        $config = ($this->modelClass)::getAdminModuleConfig();
        if (($config === false) || ($config === []) || ($config === null)) {
            $this->assertTrue(true);

            return true;
        }

        return false;
    }

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->testUser = User::factory()->create();
        $this->testUser->assignRole(RoleService::ROLE_SUPER_ADMIN);
    }
}
