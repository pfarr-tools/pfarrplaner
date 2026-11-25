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

namespace App\Liturgy\Replacement;

use App\Models\Service;

abstract class AbstractReplacer
{
    /** @var Service */
    protected $service;

    /** @var string Beschreibung des Replacers */
    protected $description = '';

    /**
     * @param  Service  $service  Service to replace text for.\n     * @return void
     */
    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    /**
     * Ermittelt den aus dem Klassennamen abgeleiteten Schlüssel des Replacers.
     *
     * @return string Schlüssel des Replacers
     */
    public function getKey(): string
    {
        return lcfirst(strtr(get_called_class(), ['App\\Liturgy\\Replacement\\' => '', 'Replacer' => '']));
    }

    /**
     * @param  string  $text  Input text.\n     * @return string
     */
    public function replace(string $text): string
    {
        return str_replace('['.$this->getKey().']', $this->getReplacementText(), $text);
    }

    /**
     * @return string
     */
    abstract protected function getReplacementText(): string;

    /**
     * @return Service
     */
    public function getService(): Service
    {
        return $this->service;
    }

    /**
     * @param  Service  $service  Service to use.\n     * @return void
     */
    public function setService(Service $service): void
    {
        $this->service = $service;
    }

    /**
     * Rendert einen Absatz mit abschließendem Zeilenumbruch.
     *
     * @param  string  $text  Text des Absatzes
     * @return string Absatz inklusive Zeilenumbruch
     */
    protected function renderParagraph(string $text = ''): string
    {
        return $text.PHP_EOL;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param  string  $description  Description text.\n     * @return void
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }
}
