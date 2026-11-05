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

namespace Tests\Unit\Rules;

use App\Models\People\User;
use App\Models\Places\City;
use App\Rules\CreatedInLocalAdminDomainRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatedInLocalAdminDomainRuleUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_rule_instantiates(): void
    {
        $rule = new CreatedInLocalAdminDomainRule;
        $this->assertInstanceOf(CreatedInLocalAdminDomainRule::class, $rule);
    }

    public function test_message_returns_string(): void
    {
        $rule = new CreatedInLocalAdminDomainRule;
        $this->assertIsString($rule->message());
        $this->assertNotEmpty($rule->message());
    }

    public function test_passes_returns_false_when_no_admin_city_has_permission(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        // adminCities() filters pivot where permission = 'a'
        $user->cities()->attach($city->id, ['permission' => 'a']);
        $this->actingAs($user);

        $rule = new CreatedInLocalAdminDomainRule;
        // Value where city has 'n' permission → should fail
        $value = [$city->id => ['permission' => 'n']];
        $this->assertFalse($rule->passes('cities', $value));
    }

    public function test_passes_returns_true_when_admin_city_has_permission(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        // adminCities() filters pivot where permission = 'a'
        $user->cities()->attach($city->id, ['permission' => 'a']);
        $this->actingAs($user);

        $rule = new CreatedInLocalAdminDomainRule;
        $value = [$city->id => ['permission' => 'write']];
        $this->assertTrue($rule->passes('cities', $value));
    }
}
