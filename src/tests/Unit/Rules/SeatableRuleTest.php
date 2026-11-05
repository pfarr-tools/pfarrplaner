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

use App\Rules\Seatable;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Tests\TestCase;

class SeatableRuleTest extends TestCase
{
    public function test_implements_validation_rule_interface()
    {
        $this->assertInstanceOf(ValidationRule::class, new Seatable);
    }

    public function test_implements_data_aware_rule_interface()
    {
        $this->assertInstanceOf(DataAwareRule::class, new Seatable);
    }

    public function test_set_data_returns_self()
    {
        $rule = new Seatable('booking_id');
        $result = $rule->setData(['service_id' => 1, 'booking_id' => null]);
        $this->assertSame($rule, $result);
    }

    public function test_constructor_accepts_booking_id_key()
    {
        $rule = new Seatable('booking_id');
        $this->assertInstanceOf(Seatable::class, $rule);
    }

    public function test_constructor_without_booking_id_key()
    {
        $rule = new Seatable;
        $this->assertInstanceOf(Seatable::class, $rule);
    }
}
