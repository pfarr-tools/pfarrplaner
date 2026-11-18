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

use App\Http\Controllers\Auth\EmailLoginLinkController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasskeyLoginController;
use App\Http\Controllers\Auth\PasskeyManagementController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\PreventImpersonatedSecurityChanges;
use App\Http\Middleware\ProcessPasswordlessLoginLinkAfterResponse;
use App\Http\Middleware\RequireFreshPasskeyChallenge;
use Illuminate\Support\Facades\Auth;

Auth::routes(['logout' => false, 'register' => false, 'confirm' => false]);
Route::get('/passkeys/login/options', [PasskeyLoginController::class, 'options'])
    ->middleware(['guest', 'throttle:passkey-login'])
    ->name('passkey.login-options');
Route::post('/passkeys/login', [PasskeyLoginController::class, 'verify'])
    ->middleware(['guest', RequireFreshPasskeyChallenge::class.':login', 'throttle:passkey-login'])
    ->name('passkey.login');
Route::post('/login/link', [EmailLoginLinkController::class, 'request'])
    ->middleware(['guest', 'throttle:login-link', ProcessPasswordlessLoginLinkAfterResponse::class])
    ->name('login.link.request');
Route::get('/login/link/{token}', [EmailLoginLinkController::class, 'open'])
    ->middleware('guest')
    ->where('token', '[A-Fa-f0-9]{64}')
    ->name('login.link.open');
Route::get('/login/link/confirm', [EmailLoginLinkController::class, 'showConfirmation'])
    ->middleware('guest')
    ->name('login.link.confirm-form');
Route::post('/login/link/confirm', [EmailLoginLinkController::class, 'confirm'])
    ->middleware(['guest', 'throttle:6,1'])
    ->name('login.link.confirm');
Route::middleware(['auth', PreventImpersonatedSecurityChanges::class])
    ->prefix('/user/passkeys')
    ->name('user.passkeys.')
    ->group(function (): void {
        Route::get('/', [PasskeyManagementController::class, 'index'])
            ->middleware('throttle:6,1')
            ->name('index');
        Route::get('/options', [PasskeyManagementController::class, 'options'])
            ->middleware('throttle:6,1')
            ->name('options');
        Route::post('/', [PasskeyManagementController::class, 'store'])
            ->middleware([RequireFreshPasskeyChallenge::class.':registration', 'throttle:6,1'])
            ->name('store');
        Route::patch('/{passkey}', [PasskeyManagementController::class, 'rename'])
            ->middleware('throttle:6,1')
            ->name('rename');
        Route::delete('/{passkey}', [PasskeyManagementController::class, 'destroy'])
            ->middleware('throttle:6,1')
            ->name('destroy');
    });
Route::get('/login/challenge', [LoginController::class, 'showChallenge'])->name('login.challenge');
Route::post('/login/challenge', [LoginController::class, 'verifyChallenge'])->name('login.challenge.verify');
Route::post('/login/challenge/cancel', [LoginController::class, 'cancelChallenge'])->name('login.challenge.cancel');
Route::match(['GET', 'POST'], '/logout', [UserController::class, 'logout'])->name('logout');
