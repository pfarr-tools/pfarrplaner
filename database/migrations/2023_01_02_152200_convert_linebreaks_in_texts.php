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
 *
 * Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
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

use App\Liturgy\Text;
use Illuminate\Database\Migrations\Migration;
use Symfony\Component\Console\Output\ConsoleOutput;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $texts = Text::all();
        $consoleOutput = new ConsoleOutput();
        $consoleSection = $consoleOutput->section();
        $consoleSection->write('<comment>Converting</comment> linebreaks in liturgical texts (1 / '.count($texts).')...');
        $ct = 0;
        foreach ($texts as $text) {
            $ct++;
            $consoleSection->overwrite('<comment>Converting</comment> linebreaks in liturgical texts ('.$ct.' / '.count($texts).')...');

            if (str_contains($text->text, "\n")) {
                $text->update(['text' => '<p>'.str_replace("\n", '<br>', str_replace("\n\n", '</p><p>', $text->text)).'</p>']);
            }

        }
        $consoleSection->overwrite('<info>Converted</info> linebreaks in '.count($texts).' liturgical texts');
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }



};
