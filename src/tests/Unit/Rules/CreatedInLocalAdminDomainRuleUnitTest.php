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

namespace Tests\Unit\Rules;

use App\Models\People\User;
use App\Models\Places\City;
use App\Rules\CreatedInLocalAdminDomainRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatedInLocalAdminDomainRuleUnitTest extends TestCase
{
    use RefreshDatabase;

    public function testRuleInstantiates(): void
    {
        $rule = new CreatedInLocalAdminDomainRule();
        $this->assertInstanceOf(CreatedInLocalAdminDomainRule::class, $rule);
    }

    public function testMessageReturnsString(): void
    {
        $rule = new CreatedInLocalAdminDomainRule();
        $this->assertIsString($rule->message());
        $this->assertNotEmpty($rule->message());
    }

    public function testPassesReturnsFalseWhenNoAdminCityHasPermission(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        // adminCities() filters pivot where permission = 'a'
        $user->cities()->attach($city->id, ['permission' => 'a']);
        $this->actingAs($user);

        $rule = new CreatedInLocalAdminDomainRule();
        // Value where city has 'n' permission → should fail
        $value = [$city->id => ['permission' => 'n']];
        $this->assertFalse($rule->passes('cities', $value));
    }

    public function testPassesReturnsTrueWhenAdminCityHasPermission(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        // adminCities() filters pivot where permission = 'a'
        $user->cities()->attach($city->id, ['permission' => 'a']);
        $this->actingAs($user);

        $rule = new CreatedInLocalAdminDomainRule();
        $value = [$city->id => ['permission' => 'write']];
        $this->assertTrue($rule->passes('cities', $value));
    }
}
