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



use App\Http\Controllers\ServiceController;

Route::get('/mitfeiern/{service:slug}', [ServiceController::class, 'publicLiturgy'])->name('service.publicLiturgy')->middleware([]);

// multiple services
Route::get('/veranstaltungen/neu/{city?}/{date?}', [ServiceController::class, 'create'])->name('service.create');
Route::get('/veranstaltungen/neue-veranstaltung/{filter}/{date?}', [ServiceController::class, 'createEvent'])->name('event.create');
Route::get('/veranstaltungen/meine/letzte-aktualisierung', [ServiceController::class, 'lastUpdate'])->name('services.currentUser.lastUpdate');

// one service
Route::get('/veranstaltung/{service:slug}', [ServiceController::class, 'edit'])->name('service.edit');
Route::patch('/veranstaltung/{service:slug}', [ServiceController::class, 'update'])->name('service.update');
Route::delete('/veranstaltung/{service:slug}', [ServiceController::class, 'destroy'])->name('service.destroy');
Route::get('/veranstaltung-daten/{service:slug}', [ServiceController::class, 'data'])->name('service.data');
Route::patch('/veranstaltung-predigt/{service:slug}', [ServiceController::class, 'setSermon'])->name('service.setsermon');

// additional service routes
Route::get('/veranstaltung/{service:slug}/ical', [ServiceController::class, 'ical'])->name('service.ical');
Route::get('/veranstaltung/{service:slug}/liedblatt', [ServiceController::class, 'songsheet'])->name('service.songsheet');
Route::get('/veranstaltung/{service:slug}/spendencode', [ServiceController::class, 'epc'])->name('service.epc');
Route::post('/veranstaltung/{service:slug}/dateien', [ServiceController::class, 'attach'])->name('service.attach');
Route::delete('/veranstaltung/{service:slug}/datei/{attachment}', [ServiceController::class, 'detach'])->name('service.detach');

Route::post('/veranstaltung/{service:slug}/qr', [ServiceController::class, 'createQR'])->name('service.createQR');



// KEEP FOR THE MOMENT (for backwards compatibility)

// multiple services
Route::get('/gottesdienste/neu/{city}/{date?}', [ServiceController::class, 'create']);
Route::get('/gottesdienste/meine/letzte-aktualisierung', [ServiceController::class, 'lastUpdate']);

// one service
Route::get('/gottesdienst/{service:slug}', [ServiceController::class, 'edit']);
Route::patch('/gottesdienst/{service:slug}', [ServiceController::class, 'update']);
Route::delete('/gottesdienst/{service:slug}', [ServiceController::class, 'destroy']);
Route::get('/gottesdienst-daten/{service:slug}', [ServiceController::class, 'data']);
Route::patch('/gottesdienst-predigt/{service:slug}', [ServiceController::class, 'setSermon']);

// additional service routes
Route::get('/gottesdienst/{service:slug}/ical', [ServiceController::class, 'ical']);
Route::get('/gottesdienst/{service:slug}/liedblatt', [ServiceController::class, 'songsheet']);
Route::post('/gottesdienst/{service:slug}/dateien', [ServiceController::class, 'attach']);
Route::delete('/gottesdienst/{service:slug}/datei/{attachment}', [ServiceController::class, 'detach']);

Route::post('/gottesdienst/{service:slug}/qr', [ServiceController::class, 'createQR']);

