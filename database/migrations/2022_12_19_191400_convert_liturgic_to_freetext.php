<?php

use App\Baptism;
use App\Funeral;
use App\Liturgy\PronounSets\AbstractPronounSet;
use App\Liturgy\PronounSets\PronounSets;
use App\Service;
use App\Services\NameService;
use App\Wedding;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Liturgy\Item;

return new class extends Migration {
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
                $newItem = new \App\Liturgy\Item([
                                                     'title' => $item->title,
                                                     'data_type' => 'freetext',
                                                     'liturgy_block_id' => $item->liturgy_block_id,
                                                     'sortable' => $item->sortable,
                                                 ]);
                $newItem->data = [
                    'responsible' => $item->data['responsible'] ?? [],
                    'description' => '<p>'
                        . str_replace(
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
                        ) . '</p>',
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
    public function down()
    {
    }



    private function getReplacedText(Item $item, Service $service)
    {
        $text = $item->data['text'];
        if (($item->data['needs_replacement'] ?? '') == '') {
            return $text;
        }
        if (($item->data['replacement'] ?? '') == '') {
            return $text;
        }
        switch ($item->data['needs_replacement']) {
            case 'funeral':
                $funeralId = $item->data['replacement'] ?? $service->funerals->first()->id;
                if ($service->funerals->pluck('id')->contains($funeralId)) {
                    $funeral = Funeral::find($funeralId);
                    list($lastName, $firstName) = NameService::fromName($funeral->buried_name)->format(NameService::LAST_FIRST_ARRAY);
                    foreach (
                        [
                            'bestattung:vorname' => trim($firstName),
                            'bestattung:lastname' => trim($lastName),
                            'bestattung:name' => trim($firstName) . ' ' . trim($lastName),
                            'bestattung:todesdatum' => $funeral->dod ? $funeral->dod->format(
                                'd.m.Y'
                            ) : 'bestattung:todesdatum',
                            'bestattung:todesdatum:relativ' =>
                                $funeral->dod ? ''
                                    : 'bestattung:todesdatum:relativ',
                            'bestattung:geburtsdatum' => $funeral->dob ? $funeral->dob->format(
                                'd.m.Y'
                            ) : 'bestattung:geburtsdatum',

                        ] as $marker => $value
                    ) {
                        $text = str_replace('[' . $marker . ']', $value, $text);
                    }
                    /** @var AbstractPronounSet $pronounSet */
                    $pronounSet = PronounSets::get($funeral->pronoun_set);
                    $text = $pronounSet->replacePronouns($text, 'bestattung');
                }
                break;
            case 'baptism':
                $baptismId = $item->data['replacement'] ?? $service->baptisms->first()->id;
                if ($service->baptisms->pluck('id')->contains($baptismId)) {
                    $baptism = Baptism::find($baptismId);
                    list($lastName, $firstName) = NameService::fromName($baptism->candidate_name)->format(NameService::LAST_FIRST_ARRAY);
                    foreach (
                        [
                            'taufe:vorname' => trim($firstName),
                            'taufe:lastname' => trim($lastName),
                            'taufe:nachname' => trim($lastName),
                            'taufe:name' => trim($firstName) . ' ' . trim($lastName),

                        ] as $marker => $value
                    ) {
                        $text = str_replace('[' . $marker . ']', $value, $text);
                    }
                    /** @var AbstractPronounSet $pronounSet */
                    $pronounSet = PronounSets::get($baptism->pronoun_set);
                    $text = $pronounSet->replacePronouns($text, 'taufe');
                }
                break;
            case 'wedding':
                $weddingId = $item->data['replacement']  ?? $service->weddings->first()->id;
                if ($service->weddings->pluck('id')->contains($weddingId)) {
                    $wedding = Wedding::find($weddingId);
                    list($lastName1, $firstName1) = NameService::fromName($wedding->spouse1_name)->format(NameService::LAST_FIRST_ARRAY);
                    list($lastName2, $firstName2) = NameService::fromName($wedding->spouse2_name)->format(NameService::LAST_FIRST_ARRAY);
                    foreach (
                        [
                            'trauung:person1:vorname' => trim($firstName1),
                            'trauung:person1:nachname' => trim($lastName1),
                            'trauung::person1:name' => trim($firstName1) . ' ' . trim($lastName1),
                            'trauung:person2:vorname' => trim($firstName2),
                            'trauung:person2:nachname' => trim($lastName2),
                            'trauung::person2:name' => trim($firstName2) . ' ' . trim($lastName2),

                        ] as $marker => $value
                    ) {
                        $text = str_replace('[' . $marker . ']', $value, $text);
                    }
                    /** @var AbstractPronounSet $pronounSet */
                    $pronounSet1 = PronounSets::get($wedding->pronoun_set1);
                    $text = $pronounSet1->replacePronouns($text, 'trauung:person1');
                    $pronounSet2 = PronounSets::get($wedding->pronoun_set2);
                    $text = $pronounSet2->replacePronouns($text, 'trauung:person2');
                }
                break;
        }
        return $text;
    }

    private function relativeDateString(Carbon $relativeToday, Carbon $dateToDescribe)
    {
        $diff = $dateToDescribe->diffInDays($relativeToday);
        if ($diff == 0) return 'heute';
        if ($diff == 1) {
            return 'gestern';
        }
        if ($diff == 2) {
            return 'vorgestern';
        }
        if ($diff >= 3 && $diff <= 6) {
            return 'am ' . $dateToDescribe->formatLocalized('%A');
        }
        if ( $diff == 7) return 'am ' . $dateToDescribe->formatLocalized('%A').' der letzten Woche';
        if ($diff <= 12) {
            return 'am ' . $dateToDescribe->formatLocalized('%A').' vor einer Woche';
        }
        $weeks = sprintf('%d', floor(($diff+1) / 7));
        return 'am ' . $dateToDescribe->formatLocalized('%A').' vor '.$weeks.' Wochen';
    }


};
