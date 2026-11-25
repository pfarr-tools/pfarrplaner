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

use App\Liturgy\PronounSets\AbstractPronounSet;
use App\Liturgy\PronounSets\PronounSets;
use App\Models\Liturgy\Item;
use App\Models\Rites\Baptism;
use App\Models\Rites\Funeral;
use App\Models\Rites\Wedding;
use App\Models\Service;
use App\Services\NameService;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $delete = [];
        $items = Item::where('data_type', 'liturgic')->get();
        /** @var Item $item */
        foreach ($items as $item) {
            // fix wrong marker
            $data = $item->data;
            $data['text'] = str_replace('[taufe:taeufling_', '[taufe:', $data['text']);
            $item->data = $data;

            if ($item->block && $item->block->service) {
                $newItem = new Item([
                    'title' => $item->title,
                    'data_type' => 'freetext',
                    'liturgy_block_id' => $item->liturgy_block_id,
                    'sortable' => $item->sortable,
                ]);
                $newItem->data = [
                    'responsible' => $item->data['responsible'] ?? [],
                    'description' => '<p>'
                        .str_replace(
                            '<br><br>',
                            '</p><p>',
                            str_replace(
                                "\n",
                                '<br>',
                                str_replace(
                                    "\r\n",
                                    '</p><p>',
                                    $this->getReplacedText($item, $item->block->service)
                                )
                            )
                        ).'</p>',
                ];
                $newItem->save();
            }
            $delete[] = $item->id;
        }

        Item::whereIn('id', $delete)->delete();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {}

    /**
     * Replace liturgical markers in the item text with concrete names and dates.
     *
     * @param  Item  $item  Liturgy item whose text is converted.
     * @param  Service  $service  Service the item belongs to.
     * @return string Text with all applicable markers replaced.
     */
    private function getReplacedText(Item $item, Service $service): string
    {
        /** @var string $text Text of the liturgy item. */
        $text = $item->data['text'];
        if (($item->data['needs_replacement'] ?? '') == '') {
            return $text;
        }
        if (($item->data['replacement'] ?? '') == '') {
            return $text;
        }
        switch ($item->data['needs_replacement']) {
            case 'funeral':
                $funeralId = $item->data['replacement'] ?? $service->funerals->first()?->id;
                if ($service->funerals->pluck('id')->contains($funeralId)) {
                    $funeral = Funeral::find($funeralId);
                    if (! $funeral instanceof Funeral) {
                        break;
                    }
                    /** @var list<string> $nameParts Last and first name of the buried person. */
                    $nameParts = NameService::fromName($funeral->buried_name)->format(NameService::LAST_FIRST_ARRAY);
                    [$lastName, $firstName] = $nameParts;
                    foreach (
                        [
                            'bestattung:vorname' => trim($firstName),
                            'bestattung:lastname' => trim($lastName),
                            'bestattung:name' => trim($firstName).' '.trim($lastName),
                            'bestattung:todesdatum' => $funeral->dod ? $funeral->dod->format(
                                'd.m.Y'
                            ) : 'bestattung:todesdatum',
                            'bestattung:todesdatum:relativ' => $funeral->dod ? ''
                                    : 'bestattung:todesdatum:relativ',
                            'bestattung:geburtsdatum' => $funeral->dob ? $funeral->dob->format(
                                'd.m.Y'
                            ) : 'bestattung:geburtsdatum',

                        ] as $marker => $value
                    ) {
                        $text = str_replace('['.$marker.']', $value, $text);
                    }
                    /** @var AbstractPronounSet $pronounSet */
                    $pronounSet = PronounSets::get($funeral->pronoun_set);
                    $text = $pronounSet->replacePronouns($text, 'bestattung');
                }
                break;
            case 'baptism':
                $baptismId = $item->data['replacement'] ?? $service->baptisms->first()?->id;
                if ($service->baptisms->pluck('id')->contains($baptismId)) {
                    $baptism = Baptism::find($baptismId);
                    if (! $baptism instanceof Baptism) {
                        break;
                    }
                    /** @var list<string> $nameParts Last and first name of the baptism candidate. */
                    $nameParts = NameService::fromName($baptism->candidate_name)->format(NameService::LAST_FIRST_ARRAY);
                    [$lastName, $firstName] = $nameParts;
                    foreach (
                        [
                            'taufe:vorname' => trim($firstName),
                            'taufe:lastname' => trim($lastName),
                            'taufe:nachname' => trim($lastName),
                            'taufe:name' => trim($firstName).' '.trim($lastName),

                        ] as $marker => $value
                    ) {
                        $text = str_replace('['.$marker.']', $value, $text);
                    }
                    /** @var AbstractPronounSet $pronounSet */
                    $pronounSet = PronounSets::get($baptism->pronoun_set);
                    $text = $pronounSet->replacePronouns($text, 'taufe');
                }
                break;
            case 'wedding':
                $weddingId = $item->data['replacement'] ?? $service->weddings->first()?->id;
                if ($service->weddings->pluck('id')->contains($weddingId)) {
                    $wedding = Wedding::find($weddingId);
                    if (! $wedding instanceof Wedding) {
                        break;
                    }
                    /** @var list<string> $nameParts1 Last and first name of the first spouse. */
                    $nameParts1 = NameService::fromName($wedding->spouse1_name)->format(NameService::LAST_FIRST_ARRAY);
                    [$lastName1, $firstName1] = $nameParts1;
                    /** @var list<string> $nameParts2 Last and first name of the second spouse. */
                    $nameParts2 = NameService::fromName($wedding->spouse2_name)->format(NameService::LAST_FIRST_ARRAY);
                    [$lastName2, $firstName2] = $nameParts2;
                    foreach (
                        [
                            'trauung:person1:vorname' => trim($firstName1),
                            'trauung:person1:nachname' => trim($lastName1),
                            'trauung::person1:name' => trim($firstName1).' '.trim($lastName1),
                            'trauung:person2:vorname' => trim($firstName2),
                            'trauung:person2:nachname' => trim($lastName2),
                            'trauung::person2:name' => trim($firstName2).' '.trim($lastName2),

                        ] as $marker => $value
                    ) {
                        $text = str_replace('['.$marker.']', $value, $text);
                    }
                    /** @var AbstractPronounSet $pronounSet1 */
                    $pronounSet1 = PronounSets::get($wedding->pronoun_set1);
                    $text = $pronounSet1->replacePronouns($text, 'trauung:person1');
                    /** @var AbstractPronounSet $pronounSet2 */
                    $pronounSet2 = PronounSets::get($wedding->pronoun_set2);
                    $text = $pronounSet2->replacePronouns($text, 'trauung:person2');
                }
                break;
        }

        return $text;
    }

};
