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

namespace Tests\Unit\Mail;

use App\Mail\Absence\AbsenceApproved;
use App\Mail\Absence\AbsenceChecked;
use App\Mail\Absence\AbsenceRejected;
use App\Mail\Absence\AbsenceRequested;
use App\Models\Leave\Absence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbsenceMailUnitTest extends TestCase
{
    use RefreshDatabase;

    public function testAbsenceApprovedBuilds(): void
    {
        $absence = Absence::factory()->create();
        $mail = new AbsenceApproved($absence);
        $built = $mail->build();
        $this->assertInstanceOf(AbsenceApproved::class, $built);
        $this->assertEquals('Antrag genehmigt', $built->subject);
    }

    public function testAbsenceCheckedBuilds(): void
    {
        $absence = Absence::factory()->create();
        $mail = new AbsenceChecked($absence);
        $built = $mail->build();
        $this->assertInstanceOf(AbsenceChecked::class, $built);
    }

    public function testAbsenceRejectedBuilds(): void
    {
        $absence = Absence::factory()->create();
        $author = \App\Models\People\User::factory()->create();
        $mail = new AbsenceRejected($absence, $author);
        $built = $mail->build();
        $this->assertInstanceOf(AbsenceRejected::class, $built);
    }

    public function testAbsenceRequestedBuilds(): void
    {
        $absence = Absence::factory()->create();
        $mail = new AbsenceRequested($absence);
        $built = $mail->build();
        $this->assertInstanceOf(AbsenceRequested::class, $built);
    }
}
