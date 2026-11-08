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

namespace Tests\Unit\Liturgy;

use App\Liturgy\LiturgySheets\A4WordSpecificLiturgySheet;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Verify that malformed serialized recipient selections are made scalar.
 */
class A4WordSpecificLiturgySheetUnitTest extends TestCase
{
    /**
     * Nested recipient arrays are flattened without losing their names.
     */
    public function test_recipient_configuration_is_normalized_to_scalar_names(): void
    {
        $sheet = new A4WordSpecificLiturgySheet;
        $method = new ReflectionMethod($sheet, 'normalizeRecipients');
        $method->setAccessible(true);

        $recipients = $method->invoke($sheet, ['Rudolf Mayer', ['Rudolf Mayer']]);

        $this->assertSame(['Rudolf Mayer'], $recipients);
    }
}
