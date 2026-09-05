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

namespace App\Providers;

use App\Models\Calendar\Day;
use App\Models\Ads\AdConfig;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Leave\Absence;
use App\Models\Leave\Replacement;
use App\Models\Liturgy\Psalm;
use App\Models\Liturgy\Song;
use App\Models\Liturgy\Songbook;
use App\Models\Location;
use App\Models\People\Team;
use App\Models\People\User;
use App\Models\Places\StreetRange;
use App\Models\Places\City;
use App\Models\Rites\Baptism;
use App\Models\Rites\Funeral;
use App\Models\Rites\Wedding;
use App\Models\Sermon;
use App\Models\ServiceGroup;
use App\Models\Seating\Booking;
use App\Models\Tag;
use App\Policies\AbsencePolicy;
use App\Policies\CityPolicy;
use App\Policies\CommentPolicy;
use App\Policies\DayPolicy;
use App\Policies\FuneralPolicy;
use App\Policies\AttachmentPolicy;
use App\Policies\AdConfigPolicy;
use App\Policies\BaptismPolicy;
use App\Policies\BookingPolicy;
use App\Policies\LocationPolicy;
use App\Policies\ParishPolicy;
use App\Policies\PsalmPolicy;
use App\Policies\SongPolicy;
use App\Policies\SongbookPolicy;
use App\Policies\RolePolicy;
use App\Policies\ReplacementPolicy;
use App\Policies\SermonPolicy;
use App\Policies\ServicePolicy;
use App\Policies\ServiceGroupPolicy;
use App\Policies\StreetRangePolicy;
use App\Policies\TagPolicy;
use App\Policies\TeamPolicy;
use App\Policies\UserPolicy;
use App\Policies\WeddingPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

/**
 * Class AuthServiceProvider
 * @package App\Providers
 */
class AuthServiceProvider extends ServiceProvider
{

    /**
     *
     */
    public const SUPER = 'Super-Administrator:in';
    /**
     *
     */
    public const ADMIN = 'Administrator:in';

    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Booking::class => BookingPolicy::class,
        Location::class => LocationPolicy::class,
        City::class => CityPolicy::class,
        //Service::class => ServicePolicy::class,
        '\App\Models\Service' => ServicePolicy::class,
        'App\Models\Service' => ServicePolicy::class,
        Day::class => DayPolicy::class,
        Role::class => RolePolicy::class,
        Absence::class => AbsencePolicy::class,
        Replacement::class => ReplacementPolicy::class,
        AdConfig::class => AdConfigPolicy::class,
        Attachment::class => AttachmentPolicy::class,
        Baptism::class => BaptismPolicy::class,
        Comment::class => CommentPolicy::class,
        Funeral::class => FuneralPolicy::class,
        Parish::class => ParishPolicy::class,
        Psalm::class => PsalmPolicy::class,
        Sermon::class => SermonPolicy::class,
        Song::class => SongPolicy::class,
        Songbook::class => SongbookPolicy::class,
        ServiceGroup::class => ServiceGroupPolicy::class,
        StreetRange::class => StreetRangePolicy::class,
        Tag::class => TagPolicy::class,
        Team::class => TeamPolicy::class,
        Wedding::class => WeddingPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Super-Administrator:in can do everything
        Gate::before(
            function ($user, $ability) {
                if ($user->hasRole(self::SUPER)) {
                    return true;
                }
            }
        );

        Gate::define('calendar.month', 'App\Policies\CalendarPolicy@month');
        Gate::define('calendar.print', 'App\Policies\CalendarPolicy@print');
        Gate::define('calendar.printsetup', 'App\Policies\CalendarPolicy@printsetup');
    }
}
