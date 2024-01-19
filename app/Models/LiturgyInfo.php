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

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiturgyInfo extends Model
{

    protected $table = 'liturgy_info';

    protected $fillable = [
        'id',
        'dayId',
        'date',
        'calendarYear',
        'title',
        'litProfileText',
        'litProfileGist',
        'litColor',
        'litColorName',
        'litRitesImage',
        'feastCircleName',
        'litDetailsTeaser',
        'litDetailsText',
        'litDetailsYoutubeId',
        'litDetailsImageSubline',
        'litDetailsImageCopyright',
        'litDetailsYoutubeCopyright',
        'litDetailsImage',
        'evangeliumId',
        'evangeliumURL',
        'evangeliumReader',
        'litTextsLinkPrayer',
        'litTextsWeeklyPsalm',
        'litTextsWeeklyQuote',
        'litTextsWeeklyQuoteText',
        'litTextsEntryPsalm',
        'litTextsOldTestament',
        'litTextsEpistel',
        'litTextsEvangelium',
        'litTextsPreacher',
        'litTextsHaleluja',
        'litTextsPerikope1',
        'litTextsPerikope2',
        'litTextsPerikope3',
        'litTextsPerikope4',
        'litTextsPerikope5',
        'litTextsPerikope6',
        'litTextsSpeciality',
        'litProfileExtended',
        'litRitesTitle',
        'litRitesSubtitle',
        'litRitesTeaser',
        'litRitesText',
        'litRitesImageSubline',
        'litRitesImageCopyright',
        'perikope',
        'litProfileImage',
        'litTextsWeeklySongNbr',
        'litDetailsHeadline',
        'push_text',
        'litTextsWeeklyQuoteLink',
        'litTextsWeeklyPsalmLink',
        'litTextsEntryPsalmLink',
        'litTextsOldTestamentLink',
        'litTextsEpistelLink',
        'litTextsEvangeliumLink',
        'litTextsPreacherLink',
        'litTextsHalelujaLink',
        'litTextsPerikope1Link',
        'litTextsPerikope2Link',
        'litTextsPerikope3Link',
        'litTextsPerikope4Link',
        'litTextsPerikope5Link',
        'litTextsPerikope6Link',
        'currentPerikope',
        'currentPerikopeLink',
        'songs',
        'links',
    ];

    protected $casts = [
        'songs' => 'array',
        'links' => 'array',
    ];


}
