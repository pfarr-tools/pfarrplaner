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

use App\Rules\Seatable;
use App\Rules\SeatableFixed;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Tests\TestCase;

class SeatableRuleTest extends TestCase
{
    public function testImplementsValidationRuleInterface()
    {
        $this->assertInstanceOf(ValidationRule::class, new Seatable());
    }

    public function testImplementsDataAwareRuleInterface()
    {
        $this->assertInstanceOf(DataAwareRule::class, new Seatable());
    }

    public function testSetDataReturnsSelf()
    {
        $rule = new Seatable('booking_id');
        $result = $rule->setData(['service_id' => 1, 'booking_id' => null]);
        $this->assertSame($rule, $result);
    }

    public function testConstructorAcceptsBookingIdKey()
    {
        $rule = new Seatable('booking_id');
        $this->assertInstanceOf(Seatable::class, $rule);
    }

    public function testConstructorWithoutBookingIdKey()
    {
        $rule = new Seatable();
        $this->assertInstanceOf(Seatable::class, $rule);
    }
}
