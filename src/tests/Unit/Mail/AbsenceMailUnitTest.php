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

namespace Tests\Unit\Mail;

use App\Mail\Absence\AbsenceApproved;
use App\Mail\Absence\AbsenceChecked;
use App\Mail\Absence\AbsenceRejected;
use App\Mail\Absence\AbsenceRequested;
use App\Models\Leave\Absence;
use App\Models\People\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbsenceMailUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_absence_approved_builds(): void
    {
        $absence = Absence::factory()->create();
        $mail = new AbsenceApproved($absence);
        $built = $mail->build();
        $this->assertInstanceOf(AbsenceApproved::class, $built);
        $this->assertEquals('Antrag genehmigt', $built->subject);
    }

    /**
     * Verifies that Markdown mails use the Pfarrplaner-branded HTML shell.
     */
    public function test_markdown_mail_renders_with_pfarrplaner_branding(): void
    {
        $absence = Absence::factory()->create();
        $mail = new AbsenceRequested($absence);

        $html = $mail->render();

        $this->assertStringContainsString('background-color: #a61380', $html);
        $this->assertStringContainsString('border-radius: 6px', $html);
        $this->assertStringContainsString('Pfarrplaner', $html);
    }

    public function test_absence_checked_builds(): void
    {
        $absence = Absence::factory()->create();
        $mail = new AbsenceChecked($absence);
        $built = $mail->build();
        $this->assertInstanceOf(AbsenceChecked::class, $built);
    }

    public function test_absence_rejected_builds(): void
    {
        $absence = Absence::factory()->create();
        $author = User::factory()->create();
        $mail = new AbsenceRejected($absence, $author);
        $built = $mail->build();
        $this->assertInstanceOf(AbsenceRejected::class, $built);
    }

    public function test_absence_requested_builds(): void
    {
        $absence = Absence::factory()->create();
        $mail = new AbsenceRequested($absence);
        $built = $mail->build();
        $this->assertInstanceOf(AbsenceRequested::class, $built);
    }
}
