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

use App\Models\Ads\AdConfig;
use Tests\AbstractSimpleModelUnitTest;

class AdConfigUnitTest extends AbstractSimpleModelUnitTest
{
    protected string $modelClass = AdConfig::class;
    protected bool $hasPolicy = true;
    protected bool $hasFactory = false;
}
