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
 *
 *
 * Pfarrplaner is based on the Laravel framework (https://laravel.com).
 * This file may contain code created by Laravel's scaffolding functions.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Liturgy\LiturgySheets;

use App\Liturgy\ItemHelpers\SongItemHelper;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class SongMailLiturgySheet extends AbstractLiturgySheet
{
    protected $title = 'E-Mail mit Liederliste';

    protected $icon = 'fa fa-envelope';

    protected $isNotAFile = true;

    protected $configurationComponent = 'SongMailLiturgySheetDialog';

    protected $configurationCloseOnly = true;

    protected $privileged = true;

    public function render(Service $service)
    {
        $dialogData = $this->getDialogData($service);
        $body = $dialogData['body'];
        $subject = $dialogData['subject'];
        $recipients = $dialogData['recipients'];

        if (count($recipients) == 0) {
            $recipients[] = (string) Auth::user()?->email;
        }

        if (strlen($body) > 1000) {
            return Inertia::render('Liturgy/LiturgySheets/SongMailOverflow', compact('body', 'subject', 'recipients', 'service'));
        }

        return redirect(
            'mailto:'.implode(',', $recipients).'?subject='.rawurlencode($subject).'&body='.rawurlencode($body)
        );
    }

    /** @return array{body: string, subject: string, recipients: list<string>} */
    public function getDialogData(Service $service): array
    {
        $subject = $this->buildSubject($service);
        $body = $this->buildBody($service);
        $recipients = $this->getRecipients($service);

        return compact('body', 'subject', 'recipients');
    }

    protected function buildSubject(Service $service): string
    {
        return $service->titleText(false).' am '.$service->trueDate()->isoFormat(
            'dddd, DD.MM.YYYY'
        ).', '.$service->timeText().', '.$service->locationText();
    }

    protected function buildBody(Service $service): string
    {
        $body = 'Sehr geehrte Mitwirkende am Gottesdienst'.' am '.$service->trueDate()->isoFormat(
            'dddd, DD. MMMM YYYY'
        )
            .', '.$service->timeText().
            (($service->location && $service->location->at_text) ? ' '.$service->location->at_text : ', '.$service->locationText())
            .','.PHP_EOL.PHP_EOL.'Hier der geplante Ablauf für den Gottesdienst:'.PHP_EOL.PHP_EOL;
        foreach ($service->liturgyBlocks as $block) {
            foreach ($block->items as $item) {
                /** @var array{responsible?: mixed, song?: array{code?: mixed, reference?: mixed, altEG?: mixed, song?: array{title?: mixed}, songbook?: array{name?: mixed}}, psalm?: array{songbook_abbreviation?: mixed, songbook?: mixed, reference?: mixed, title?: mixed}, verses?: mixed, reference?: mixed} $data */
                $data = $item->data;
                $itemTitle = is_scalar($item->title) ? (string) $item->title : '';

                $responsible = $data['responsible'] ?? null;
                if (is_array($responsible)) {
                    $concernsOrganists = in_array('ministry:organists', $responsible);
                    if (! $concernsOrganists) {
                        foreach ($service->organists as $organist) {
                            $concernsOrganists = $concernsOrganists || in_array(
                                'user:'.$organist->id,
                                $responsible
                            );
                        }
                    }
                } else {
                    $concernsOrganists = false;
                }

                if (($item->data_type == 'song') && isset($data['song'])) {
                    $songData = $data['song'];
                    $helper = new SongItemHelper($item);
                    $verseCount = $helper->getActiveVerseCount(true, true);

                    $code = $songData['code'] ?? ($songData['songbook']['name'] ?? '');
                    $reference = $songData['reference'] ?? '';
                    $altEg = $songData['altEG'] ?? null;
                    $songTitle = $songData['song']['title'] ?? '';
                    $body .= '  -> '.$itemTitle.': '
                        .(is_scalar($code) ? (string) $code : '')
                        .' '
                        .(is_scalar($reference) ? (string) $reference : '').' '
                        .(($altEg !== null && is_scalar($altEg)) ? '(EG '.(string) $altEg.') ' : '')
                        .(is_scalar($songTitle) ? (string) $songTitle : '')
                        .$helper->forceVerseString(', ')
                        .($verseCount ? ' ('.$verseCount.')' : '')
                        .PHP_EOL;
                } elseif ($item->data_type == 'psalm') {
                    if (isset($data['psalm'])) {
                        $psalmData = $data['psalm'];
                        $songbook = $psalmData['songbook_abbreviation'] ?? $psalmData['songbook'] ?? '';
                        $reference = $psalmData['reference'] ?? '';
                        $title = $psalmData['title'] ?? '';
                        $verses = $data['verses'] ?? null;
                        $body .= '  -> '.$itemTitle.': '
                            .(is_scalar($songbook) ? (string) $songbook : '')
                            .' '
                            .(is_scalar($reference) ? (string) $reference : '').' '
                            .(is_scalar($title) ? (string) $title : '')
                            .(is_scalar($verses) && $verses != '' ? ', '.(string) $verses : '')
                            .PHP_EOL;
                    }
                } elseif ($item->data_type == 'reading') {
                    $reference = $data['reference'] ?? '--';
                    $body .= ($concernsOrganists ? '  -> ' : '       ').$itemTitle.': '.(is_scalar($reference) ? (string) $reference : '--').PHP_EOL;
                } else {
                    $body .= ($concernsOrganists ? '  -> ' : '       ').$itemTitle.PHP_EOL;
                }
            }
        }

        return $body.PHP_EOL
            .'Der komplette Ablauf kann hier in einem druckbaren Format heruntergeladen werden:'.PHP_EOL
            .URL::temporarySignedRoute('liturgy.download', now()->addDays(14), ['service' => $service->slug, 'key' => 'A4']).PHP_EOL.PHP_EOL
            .'Außerdem gibt es den Ablauf in einem druckbaren, für Organist:innen optimierten Format hier:'.PHP_EOL
            .URL::temporarySignedRoute('liturgy.download', now()->addDays(14), ['service' => $service->slug, 'key' => 'Organist']).PHP_EOL
            .PHP_EOL.'Freundliche Grüße, '.PHP_EOL.(Auth::user()?->fullName() ?? '');
    }

    /**
     * @return list<string> Recipient email addresses.
     */
    protected function getRecipients(Service $service): array
    {
        $recipients = [];
        foreach ($service->organists as $organist) {
            $recipients[] = (string) $organist->email;
        }

        if (count($recipients) == 0) {
            $recipients[] = (string) Auth::user()?->email;
        }

        return $recipients;
    }
}
