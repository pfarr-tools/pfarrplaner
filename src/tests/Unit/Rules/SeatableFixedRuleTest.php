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

use App\Rules\SeatableFixed;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Tests\TestCase;

class SeatableFixedRuleTest extends TestCase
{
    public function testImplementsValidationRuleInterface()
    {
        $this->assertInstanceOf(ValidationRule::class, new SeatableFixed());
    }

    public function testImplementsDataAwareRuleInterface()
    {
        $this->assertInstanceOf(DataAwareRule::class, new SeatableFixed());
    }

    public function testSetDataReturnsSelf()
    {
        $rule = new SeatableFixed('booking_id');
        $result = $rule->setData(['service_id' => 1, 'booking_id' => null]);
        $this->assertSame($rule, $result);
    }

    public function testConstructorAcceptsBookingIdKey()
    {
        $rule = new SeatableFixed('booking_id');
        $this->assertInstanceOf(SeatableFixed::class, $rule);
    }

    public function testConstructorWithoutBookingIdKey()
    {
        $rule = new SeatableFixed();
        $this->assertInstanceOf(SeatableFixed::class, $rule);
    }
}
