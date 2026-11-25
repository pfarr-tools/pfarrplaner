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

/**
 * Created by PhpStorm.
 * User: Christoph Fischer
 * Date: 29.10.2019
 * Time: 13:29
 */

namespace App\CalendarLinks;

use App\Models\People\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

/**
 * Class AbstractCalendarLink
 */
class AbstractCalendarLink
{
    /**
     * @var string
     */
    protected $title = '';

    /**
     * @var string
     */
    protected $description = '';

    /**
     * @var array<string, mixed>
     */
    protected $data = [];

    /**
     * @var string
     */
    protected $viewName = 'ical';

    /** @var User */
    protected $user = null;

    /** @var string[] */
    protected $needs = [];

    /**
     * @return void
     */
    public function __construct()
    {
        $this->data['key'] = $this->getKey();
        if ($x = request()->get('includeHidden', 0)) {
            $this->data['includeHidden'] = $x;
        }
        if (Auth::user() !== null) {
            $this->user = Auth::user();
            $this->data['token'] = Auth::user()->getToken();
            $this->data['user'] = Auth::user()->id;
        }
    }

    public function getKey(): string
    {
        return lcfirst(strtr(get_class($this), ['CalendarLink' => '', 'App\\s\\' => '', 'App\\CalendarLinks\\' => '']));
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function setupRoute()
    {
        return route('ical.setup', ['key' => $this->getKey(), 'includeHidden' => request()->get('includeHidden', 0)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @return Application|Factory|\Illuminate\View\View
     */
    public function setupView()
    {
        $data = $this->setupData();
        $data['calendarLink'] = $this;

        /** @var view-string $viewName */
        $viewName = $this->getSetupViewName();

        return view($viewName, $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function setupData()
    {
        return [];
    }

    /**
     * @return string
     */
    public function getSetupViewName()
    {
        return 'ical.'.$this->getKey().'.setup';
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return route('ical.export', $this->data);
    }

    /**
     * @param  string  $s  Text to wrap.
     */
    public function wrap(string $s): string
    {
        return implode("\n ", str_split($s, 32));
    }

    /**
     * Convert line endings to CR+LF and remove empty lines
     */
    protected function normalizeLineEndings(string $s): string
    {
        $s = (string) preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $s);
        $s = str_replace("\n", "\r\n", $s);

        return $s;
    }

    /**
     * @return string|string[]|null
     */
    public function export(Request $request, User $user)
    {
        $this->setDataFromRequest($request);
        $this->setUser($user);
        $data = $this->getRenderData($request, $user);
        $calendarLink = $this;
        /** @var view-string $view */
        $view = sprintf('%s', 'ical.export.'.$this->viewName);
        $raw = View::make($view, compact('calendarLink', 'data'))->render();

        return $this->normalizeLineEndings($raw);
    }

    /**
     * @return void
     */
    /**
     * @param  Request  $request  Current request.
     * @return void
     */
    public function setDataFromRequest(Request $request) {}

    /**
     * @param  User  $user  Authenticated user.
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->data['user'] = $this->user;
        $this->data['token'] = $this->user->getToken();
    }

    /**
     * @return mixed Render data consumed by the calendar link view.
     */
    public function getRenderData(Request $request, User $user)
    {
        return [];
    }

    /**
     * Convert into array for vue
     *
     * @return array<string, mixed>
     */
    public function toArray()
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->getTitle(),
            'description' => $this->getDescription(),
            'needs' => $this->needs,
        ];
    }

    /**
     * @return string
     */
    public function filename()
    {
        return implode('-', [$this->data['user']->id, $this->getKey()]).'.ics';
    }
}
