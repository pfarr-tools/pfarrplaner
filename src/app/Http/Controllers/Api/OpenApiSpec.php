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
 */

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

#[OA\Server(url: '/api', description: 'Pfarrplaner API server')]
#[OA\Tag(name: 'Health', description: 'Application health check')]
#[OA\Tag(name: 'Calendar', description: 'Calendar and service date queries')]
#[OA\Tag(name: 'City', description: 'Parish / city data')]
#[OA\Tag(name: 'Absence', description: 'Absence and leave management')]
#[OA\Tag(name: 'Booking', description: 'Seat bookings')]
#[OA\Tag(name: 'Event', description: 'Local event calendar')]
#[OA\Tag(name: 'Inbox', description: 'Upload inbox')]
#[OA\Tag(name: 'LiturgicalTexts', description: 'Liturgical text library')]
#[OA\Tag(name: 'Liturgy', description: 'Service liturgy blocks and items')]
#[OA\Tag(name: 'Ministries', description: 'Ministry / service role catalogue')]
#[OA\Tag(name: 'People', description: 'User / people lookup')]
#[OA\Tag(name: 'Pool', description: 'Leave pool and poolmaster queries')]
#[OA\Tag(name: 'Psalm', description: 'Psalm catalogue')]
#[OA\Tag(name: 'Rites', description: 'Baptisms, funerals, weddings search')]
#[OA\Tag(name: 'Service', description: 'Church service CRUD')]
#[OA\Tag(name: 'Song', description: 'Hymn and song catalogue')]
#[OA\Tag(name: 'Songbook', description: 'Songbook catalogue')]
#[OA\Tag(name: 'Tab', description: 'Home screen tab data')]
#[OA\Tag(name: 'Teams', description: 'Teams by city')]
class OpenApiSpec
{
}
