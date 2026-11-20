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

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Http\Controllers\UserController;

// all users
Route::get('/benutzer', [UserController::class, 'index'])->name('users.index');
Route::match(['GET', 'POST'], '/benutzer/{user}/zusammenfuehren', [UserController::class, 'join'])->name('user.join');
Route::post('/benutzer/final-zusammenfuehren/{user1}/{user2}', [UserController::class, 'doJoin'])->name('user.join.finalize');
Route::get('/doppelte-benutzer', [UserController::class, 'findDuplicates'])->name('users.duplicates');
Route::post('/doppelte-benutzer', [UserController::class, 'fixDuplicates'])->name('users.duplicates.fix');

// create person via ajax call
Route::post('/person/neu', [UserController::class, 'add'])->name('users.add');

// single user
Route::post('/benutzer', [UserController::class, 'store'])->name('user.store');
Route::get('/benutzer/neu', [UserController::class, 'create'])->name('user.create');
Route::post('/benutzer/{user}/konto/sperren', [UserController::class, 'lockAccount'])->name('user.account.lock');
Route::post('/benutzer/{user}/konto/entsperren', [UserController::class, 'unlockAccount'])->name('user.account.unlock');
Route::post('/benutzer/{user}/login-link', [UserController::class, 'createManualLoginLink'])
    ->middleware('throttle:6,1')
    ->name('user.account.login-link');
Route::delete('/benutzer/{user}/konto', [UserController::class, 'resetAccount'])->name('user.account.reset');
Route::delete('/benutzer/{user}/passkeys', [UserController::class, 'revokePasskeys'])->name('user.passkeys.revoke-all');
Route::get('/benutzer/{user}', [UserController::class, 'edit'])->name('user.edit');
Route::patch('/benutzer/{user}', [UserController::class, 'update'])->name('user.update');
Route::delete('/benutzer/{user}', [UserController::class, 'destroy'])->name('user.destroy');
Route::post('/benutzer/{model}/attach', [UserController::class, 'attachImage'])->name('user.attach');
Route::delete('/benutzer/{model}/detach', [UserController::class, 'detachImage'])->name('user.detach');

Route::post('/benutzer/login-als/{user}', [UserController::class, 'switch'])->name('user.switch');
