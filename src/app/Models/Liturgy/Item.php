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

namespace App\Models\Liturgy;

use App\Liturgy\Bible\BibleText;
use App\Liturgy\Bible\ReferenceParser;
use App\Liturgy\ItemHelpers\AbstractItemHelper;
use App\Liturgy\ItemHelpers\ItemHelperNotFoundException;
use App\Models\People\User;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Liturgischer Eintrag mit serialisierten, typspezifischen Daten.
 *
 * @property array<string, mixed> $data Deserialisierte, typspezifische Daten des Eintrags.
 * @property-read Block $block Der Block, dem dieser Eintrag zugeordnet ist.
 */
class Item extends Model
{
    protected $appends = ['data'];

    protected $table = 'liturgy_items';

    protected $fillable = ['liturgy_block_id', 'title', 'instructions', 'data_type', 'serialized_data', 'sortable'];

    protected const replacement = ['pastors' => 'P', 'organists' => 'O', 'sacristans' => 'M'];

    /**
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Order by sortable ASC
        static::addGlobalScope(
            'order',
            function (Builder $builder) {
                $builder->orderBy('sortable', 'asc');
            }
        );
    }

    /**
     * Gibt den Block zurück, dem dieser Eintrag zugeordnet ist.
     *
     * @return BelongsTo<Block, $this>
     */
    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class, 'liturgy_block_id');
    }

    /**
     * Historischer Alias für die Block-Relation.
     *
     * @return BelongsTo<Block, $this>
     */
    public function liturgyBlock(): BelongsTo
    {
        return $this->block();
    }

    /**
     * Gibt die deserialisierten, typspezifischen Daten des Eintrags zurück.
     *
     * @return array<string, mixed> Deserialisierte Daten oder ein leeres Array.
     */
    public function getDataAttribute(): array
    {
        $data = unserialize($this->attributes['serialized_data']) ?: [];

        return is_array($data) ? $data : [];
    }

    /**
     * Serialisiert die typspezifischen Daten des Eintrags für die Datenbank.
     *
     * @param  array<string, mixed>  $data  Zu speichernde Daten.
     * @return string Serialisierte Daten.
     */
    public function setDataAttribute($data): string
    {
        return $this->attributes['serialized_data'] = serialize($data);
    }

    /**
     * Resolve all recipients configured for this liturgy item.
     *
     * @return array<int, string> Namen der konfigurierten Empfänger.
     */
    public function recipients(): array
    {
        $list = (isset($this->data['responsible']) ? $this->data['responsible'] : []);
        $p = [];
        foreach ($list as $participant) {
            if (is_array($participant)) {
                $type = $participant['type'];
                $id = $participant['name'];
            } else {
                if (Str::contains($participant, ':')) {
                    [$type, $id] = explode(':', $participant, 2);
                } else {
                    $id = $participant;
                    $type = 'free';
                }
            }
            if ((! $type) || (! $id)) {
                continue;
            }
            if ($type == 'ministry') {
                $id = isset(self::replacement[$id]) ? self::replacement[$id] : $id;
                /** @var Block&object{service: Service} $block */
                $block = $this->block;
                foreach ($block->service->participantsByCategory($id) as $user) {
                    $p[] = $user->fullName(false);
                }
            } elseif ($type == 'user') {
                $p[] = User::query()->whereKey($id)->firstOrFail()->fullName(false);
            } else {
                $p[] = $id;
            }
        }

        return array_unique($p);
    }

    /**
     * @throws ItemHelperNotFoundException
     */
    public function getHelper(): AbstractItemHelper
    {
        $class = 'App\\Liturgy\\ItemHelpers\\'.ucfirst($this->data_type).'ItemHelper';
        if (class_exists($class) && is_subclass_of($class, AbstractItemHelper::class)) {
            return new $class($this);
        }
        throw new ItemHelperNotFoundException('Item helper class not found for data_type '.$this->data_type);
    }

    /**
     * Setzt einen einzelnen Wert in den typspezifischen Daten des Eintrags.
     *
     * @param  string  $key  Schlüssel des zu setzenden Datenfeldes.
     * @param  mixed  $value  Neuer Wert des Datenfeldes.
     */
    public function setData($key, $value): void
    {
        $this->setDataAttribute(array_merge($this->getDataAttribute(), [$key => $value]));
    }

    /**
     * Add bible text to 'reading' items
     */
    public function addBibleText(): void
    {
        if ($this->data_type != 'reading') {
            return;
        }
        $data = $this->data;
        $bible = new BibleText;
        $bibleText = $this->data['reference'] ? $bible->get(ReferenceParser::getInstance()->parse($this->data['reference'])) : [];
        $text = '';
        foreach ($bibleText as $textSection) {
            foreach ($textSection['text'] as $verse) {
                $text .= $verse['verse'].' '.$verse['text'].' ';
            }
        }
        $data['text'] = trim($text) ? trim($text).' ('.$this->data['reference'].')' : '';
        $this->data = $data;
    }

    /**
     * On create: Perform specific actions for this data_type
     */
    public function performTypeSpecificActionsOnCreate(): void
    {
        $this->addBibleText();
    }

    /**
     * On update: Perform specific actions for this data_type
     */
    public function performTypeSpecificActionsOnUpdate(): void
    {
        $this->addBibleText();
    }
}
