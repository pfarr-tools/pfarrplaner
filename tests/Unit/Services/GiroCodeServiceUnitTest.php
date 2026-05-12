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

namespace Tests\Unit\Services;

use App\Services\GiroCodeService;
use Tests\TestCase;

class GiroCodeServiceUnitTest extends TestCase
{
    public function testCodeValueStartsWithBcd(): void
    {
        $result = GiroCodeService::codeValue('Kirchengemeinde', 'DE89370400440532013000');
        $this->assertStringStartsWith('BCD', $result);
    }

    public function testCodeValueContainsName(): void
    {
        $result = GiroCodeService::codeValue('Kirchengemeinde Muster', 'DE89370400440532013000');
        $this->assertStringContainsString('Kirchengemeinde Muster', $result);
    }

    public function testCodeValueNormalizesIban(): void
    {
        $result = GiroCodeService::codeValue('Test', 'de89 3704 0044 0532 0130 00');
        $this->assertStringContainsString('DE89370400440532013000', $result);
    }

    public function testCodeValueIncludesAmountWhenProvided(): void
    {
        $result = GiroCodeService::codeValue('Test', 'DE89370400440532013000', 50.0);
        $this->assertStringContainsString('EUR50.00', $result);
    }

    public function testCodeValueOmitsAmountLineWhenNull(): void
    {
        $result = GiroCodeService::codeValue('Test', 'DE89370400440532013000', null);
        $this->assertStringNotContainsString('EUR', $result);
    }

    public function testCodeValueIncludesPurpose(): void
    {
        $result = GiroCodeService::codeValue('Test', 'DE89370400440532013000', null, 'Kollekte');
        $this->assertStringContainsString('Kollekte', $result);
    }

    public function testCodeValueIncludesBicWhenProvided(): void
    {
        $result = GiroCodeService::codeValue('Test', 'DE89370400440532013000', null, '', 'COBADEFFXXX');
        $this->assertStringContainsString('COBADEFFXXX', $result);
    }

    public function testCodeValueReturnsSctLine(): void
    {
        $result = GiroCodeService::codeValue('Test', 'DE89370400440532013000');
        $this->assertStringContainsString('SCT', $result);
    }
}
