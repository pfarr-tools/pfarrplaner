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

namespace Tests\Unit\Mail;

use App\Mail\Absence\AbsenceApproved;
use App\Mail\Absence\AbsenceChecked;
use App\Mail\Absence\AbsenceRejected;
use App\Mail\Absence\AbsenceRequested;
use App\Mail\ContactFormMessage;
use App\Mail\MinistryRequest;
use App\Mail\MinistryRequestFilled;
use App\Mail\RegulatoryReportMail;
use App\Mail\User\AccountData;
use App\Models\Leave\Absence;
use App\Models\People\User;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkdownMailableRenderingUnitTest extends TestCase
{
    use RefreshDatabase;

    /** Ensures every Markdown Mailable renders its body as HTML instead of a code block. */
    public function test_markdown_mailables_do_not_render_body_content_as_code(): void
    {
        $user = User::factory()->create();
        $sender = User::factory()->create();
        $service = Service::factory()->create();
        $absence = Absence::factory()->create([
            'admin_id' => $sender->id,
            'approver_id' => $user->id,
        ]);

        $mailables = [
            new MinistryRequest($user, $sender, 'P', collect([$service]), 'Test text'),
            new MinistryRequestFilled($user, $sender, 'P', collect([$service])),
            new AbsenceRequested($absence),
            new AbsenceChecked($absence),
            new AbsenceApproved($absence),
            new AbsenceRejected($absence, $sender),
            new ContactFormMessage([
                'subject' => 'Test',
                'name' => 'Test Person',
                'email' => 'test@example.org',
                'message' => 'Test message',
            ]),
            new RegulatoryReportMail([
                'service' => $service->id,
                'template' => '<p>Regulatory report content</p>',
                'number' => 1,
            ], $sender),
            new AccountData($user, $sender),
        ];

        foreach ($mailables as $mailable) {
            $html = $mailable->render();
            preg_match_all('/<pre[^>]*>(.*?)<\/pre>/s', $html, $codeBlocks);

            $this->assertCount(
                0,
                $codeBlocks[0],
                $mailable::class.' rendered Markdown as a code block: '.html_entity_decode(strip_tags($codeBlocks[0][0] ?? ''))
            );
        }
    }
}
