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

module.exports = {
    data() {
        return {
            '$componentState': Math.random().toString(36).substr(2, 9),
        }
    },
    methods: {
        $api() {
            let myApiAxios = window.api;
            myApiAxios.defaults.headers.common['Authorization'] = 'Bearer '+this.$page.props.currentUser.data.api_token;
            myApiAxios.defaults.headers.common['Accept'] = 'application/json';
            return myApiAxios;
        },
        setUserSetting(key, value) {
            this.$api().post(route('setting.set', {
                user: this.$page.props.currentUser.data.id,
                key: key,
            }), {value: value});
            this.$page.props.settings[key] = value;
        },
        $updateComponentState() {
            this.$componentState = Math.random().toString(36).substr(2, 9);
        }
    }
};
