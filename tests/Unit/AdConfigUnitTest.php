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

namespace Tests\Unit;

use App\Events\Models\AdConfig\CreatedAdConfig;
use App\Events\Models\AdConfig\DeletedAdConfig;
use App\Events\Models\AdConfig\UpdatedAdConfig;
use App\Models\AbstractModel;
use App\Models\Ads\AdConfig;
use App\Models\People\User;
use App\Models\Service;
use App\Services\RoleService;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AdConfigUnitTest extends TestCase
{
    private User $user;

    public function testAdConfigCanBeCreatedViaFactory(): void
    {
        $config = AdConfig::factory()->create();
        $this->assertCount(1, AdConfig::all());
    }

    public function testAdConfigExtendsAbstractModel(): void
    {
        $this->assertTrue(is_subclass_of(AdConfig::class, AbstractModel::class));
    }

    public function testAdConfigControllerClassesExist(): void
    {
        $this->assertTrue(is_subclass_of(AdConfig::controllerClass(), \App\Http\Controllers\AbstractCRUDController::class));
        $this->assertTrue(is_subclass_of(AdConfig::apiControllerClass(), \App\Http\Controllers\Api\AbstractApiCRUDController::class));
    }

    public function testAdConfigContractsResolve(): void
    {
        $this->assertInstanceOf(\App\Actions\AdConfig\CreateAdConfig::class, app(AdConfig::getContractName('create')));
        $this->assertInstanceOf(\App\Actions\AdConfig\UpdateAdConfig::class, app(AdConfig::getContractName('update')));
        $this->assertInstanceOf(\App\Actions\AdConfig\DeleteAdConfig::class, app(AdConfig::getContractName('delete')));
    }

    public function testAdConfigCanBeCreatedViaAction(): void
    {
        Event::fake();

        $service = Service::factory()->create();
        $config = app(AdConfig::getContractName('create'))->create($this->user, $service, [
            'service_id' => $service->id,
            'slug' => 'gemeindebrief',
            'offset' => 10,
            'ad_text' => 'Hinweis',
        ]);

        $this->assertSame('gemeindebrief', $config->slug);
        $this->assertCount(1, AdConfig::all());
        Event::assertDispatched(CreatedAdConfig::class);
    }

    public function testAdConfigCanBeUpdatedViaAction(): void
    {
        Event::fake();

        $config = AdConfig::factory()->create();
        $updated = app(AdConfig::getContractName('update'))->update($this->user, $config, [
            'service_id' => $config->service_id,
            'slug' => 'homepage',
            'offset' => 3,
            'ad_text' => 'Neu',
        ]);

        $this->assertSame('homepage', $updated->slug);
        Event::assertDispatched(UpdatedAdConfig::class);
    }

    public function testAdConfigCanBeDeletedViaAction(): void
    {
        Event::fake();

        $config = AdConfig::factory()->create();
        $result = app(AdConfig::getContractName('delete'))->delete($this->user, $config);

        $this->assertTrue($result);
        $this->assertCount(0, AdConfig::all());
        Event::assertDispatched(DeletedAdConfig::class);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->assignRole(RoleService::ROLE_SUPER_ADMIN);
    }
}
