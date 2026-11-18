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

use App\Http\Controllers\Auth\TwoFactorRecoveryController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\PreventImpersonatedSecurityChanges;

Route::get('/profil', [UserController::class, 'profile'])->name('user.profile');
Route::patch('/profil', [UserController::class, 'profileSave'])->name('user.profile.save');
Route::post('/profil/zwei-faktor/start', [UserController::class, 'startTwoFactor'])
    ->middleware(PreventImpersonatedSecurityChanges::class)
    ->name('user.two-factor.start');
Route::post('/profil/zwei-faktor/bestaetigen', [UserController::class, 'confirmTwoFactor'])
    ->middleware(PreventImpersonatedSecurityChanges::class)
    ->name('user.two-factor.confirm');
Route::post('/profil/zwei-faktor/deaktivieren', [UserController::class, 'disableTwoFactor'])
    ->middleware(PreventImpersonatedSecurityChanges::class)
    ->name('user.two-factor.disable');
Route::get('/profil/zwei-faktor/wiederherstellen', [TwoFactorRecoveryController::class, 'show'])
    ->middleware(['auth', PreventImpersonatedSecurityChanges::class])
    ->name('user.two-factor.recovery');
Route::post('/benutzer/{user}/zwei-faktor/wiederherstellen', [UserController::class, 'recoverTwoFactor'])
    ->name('user.two-factor.recover');
Route::get('/benutzer/{user}/gottesdienste', [UserController::class, 'services'])->name('user.services');

Route::post('/benutzer/zurueck-zu-admin', [UserController::class, 'switchBack'])->name('user.switchback');
