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

namespace App\Liturgy\PronounSets;

class AbstractPronounSet
{
    /**
     * @var string
     */
    protected $label;

    /**
     * @var array<string, string>
     */
    protected $pronouns = [];

    /**
     * @return string Pronoun set key.
     */
    public function getKey()
    {
        return lcfirst(strtr(get_called_class(), ['App\\Liturgy\\PronounSets\\' => '', 'PronounSet' => '']));
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param  string  $label  Label text.
     */
    public function setLabel($label): void
    {
        $this->label = $label;
    }

    /**
     * @param  string  $text  Text to replace the pronouns in.
     * @param  string  $prefix  Placeholder prefix.
     * @return string Text with replaced pronouns.
     */
    public function replacePronouns($text, $prefix)
    {
        foreach ($this->pronouns as $pronoun => $replacement) {
            $text = strtr($text, [
                '['.$prefix.':'.ucfirst($pronoun).']' => ucfirst($replacement),
                '['.$prefix.':'.$pronoun.']' => $replacement,
            ]);
        }

        return $text;
    }

    /**
     * @return array<string, string> Pronoun to replacement mapping.
     */
    public function getPronouns(): array
    {
        return $this->pronouns;
    }

    /**
     * @param  array<string, string>  $pronouns  Pronoun to replacement mapping.
     */
    public function setPronouns(array $pronouns): void
    {
        $this->pronouns = $pronouns;
    }
}
