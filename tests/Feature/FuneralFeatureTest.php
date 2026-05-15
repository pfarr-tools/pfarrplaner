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

namespace Tests\Feature;

use App\Models\People\User;
use App\Models\Rites\Funeral;
use App\Models\Service;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Class FuneralFeatureTest
 * @package Tests\Feature
 */
class FuneralFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
        foreach (range((int)date('Y') - 2, (int)date('Y') + 2) as $year) {
            Storage::put("liturgy/{$year}.json", '{}');
        }
        Storage::put('liturgy/.json', '{}');
        $this->user = User::factory()->create();
        $this->user->assignRole(RoleService::ROLE_SUPER_ADMIN);
    }

    /**
     * @return void
     */
    public function testEditorLoads(): void
    {
        $funeral = Funeral::factory()->create();
        $this->actingAs($this->user)
            ->get(route('funerals.edit', $funeral->id))
            ->assertStatus(200)
            ->assertInertia(fn(Assert $page) => $page->component('Rites/FuneralEditor'));
    }

    /**
     * @return void
     */
    public function testCreateRedirectsToEditor(): void
    {
        $service = Service::factory()->create();

        $this->actingAs($this->user)
            ->get(route('funerals.create', ['service' => $service->id]))
            ->assertRedirectContains('/funerals/');

        $this->assertCount(1, Funeral::all());
    }

    public function testFuneralCanBeUpdated(): void
    {
        $funeral = Funeral::factory()->create();
        $raw = [
            'buried_name' => 'Karl Otto',
            'service_id' => $funeral->service_id,
        ];

        $this->actingAs($this->user)
            ->patch(route('funerals.update', ['modelId' => $funeral->id]), $raw)
            ->assertStatus(302);

        $this->assertSame('Karl Otto', Funeral::first()->buried_name);
    }
}
