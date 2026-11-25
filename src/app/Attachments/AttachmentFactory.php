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

namespace App\Attachments;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/** Factory for automatic attachments. */
class AttachmentFactory
{
    /**
     * Liefert die Metadaten der automatischen Attachments eines Eintragstyps.
     *
     * Für nicht unterstützte Typen wird ein leeres Array geliefert. Dateien,
     * deren Name mit „Abstract“ beginnt, werden als Basisklassen ausgeschlossen.
     *
     * @param  string  $type  Typ des zugehörigen Eintrags (baptism, funeral oder wedding).
     * @return array<int, mixed>|Collection<int, mixed> Metadaten der verfügbaren Attachments.
     */
    public static function getList(string $type): array|Collection
    {
        if (! in_array($type, ['baptism', 'funeral', 'wedding'])) {
            return [];
        }

        $infoMapper = function (string $file) {
            /** @var class-string<AbstractAttachment> $class */
            $class = static::getClassFromFile($file);

            return $class::getInfo();
        };

        /** @var Collection<int, mixed> $attachments Metadaten der verfügbaren Attachments. */
        $attachments = collect(glob(app_path('Attachments/'.ucfirst($type).'/*.php')) ?: [])
            ->reject(function (string $file) {
                return Str::startsWith(basename($file), 'Abstract');
            })
            ->map($infoMapper);

        return $attachments;
    }

    /**
     * Erzeugt das automatische Attachment mit dem angegebenen Schlüssel für einen Eintrag.
     *
     * @param  string  $type  Typ des zugehörigen Eintrags.
     * @param  string  $key  Schlüssel des Attachments aus getList().
     * @param  int|string  $id  ID des zugehörigen Eintrags.
     * @return AbstractAttachment|null Attachment zum Eintrag oder null bei unbekanntem Schlüssel.
     */
    public static function get(string $type, string $key, int|string $id): ?AbstractAttachment
    {
        /** @var array{key: string, class: class-string<AbstractAttachment>}|null $item Gefundenes Attachment-Metadatum. */
        $item = collect(static::getList($type))->first(function ($item) use ($key) {
            return is_array($item) && ($item['key'] ?? null) == $key;
        });
        if (! $item) {
            return null;
        }

        $class = $item['class'];

        return $class::fromId($id);
    }

    /**
     * Ermittelt den Klassennamen eines Attachment-Dateipfads.
     *
     * @param  string  $file  Dateipfad der Attachment-Klasse innerhalb der Anwendung.
     * @return string Vollqualifizierter Klassenname der Attachment-Klasse.
     */
    protected static function getClassFromFile(string $file): string
    {
        return 'App\\'.Str::replace(['/', '.php'], ['\\', ''], Str::after($file, 'app/'));
    }
}
