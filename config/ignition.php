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

return [
    'editor' => env('IGNITION_EDITOR', 'phpstorm'),
    'theme' => env('IGNITION_THEME', 'auto'),
    'enable_share_button' => env('IGNITION_SHARING_ENABLED', false),
    'register_commands' => env('REGISTER_IGNITION_COMMANDS', false),
    'enable_runnable_solutions' => env('IGNITION_ENABLE_RUNNABLE_SOLUTIONS', false),
    'housekeeping_endpoint_prefix' => env('IGNITION_HOUSEKEEPING_ENDPOINT_PREFIX', '_ignition'),
    'settings_file_path' => env('IGNITION_SETTINGS_FILE_PATH', ''),
    'with_stack_frame_arguments' => env('IGNITION_WITH_STACK_FRAME_ARGUMENTS', true),
];
