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

use App\Models\ServiceGroup;
use Tests\AbstractSimpleModelUnitTest;

class ServiceGroupUnitTest extends AbstractSimpleModelUnitTest
{
    protected string $modelClass = ServiceGroup::class;
    protected bool $hasPolicy = false;
    protected bool $hasFactory = true;

    public function testServiceGroupCanBeCreatedViaFactory(): void
    {
        $group = ServiceGroup::factory()->create();
        $this->assertCount(1, ServiceGroup::all());
    }
}
