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

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Attachment;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AttachmentFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        Storage::fake();
        Permission::findOrCreate('gd-bearbeiten');
        Permission::findOrCreate('gd-allgemein-bearbeiten');
        $this->user = User::factory()->create();
        $this->user->assignRole(RoleService::ROLE_SUPER_ADMIN);
    }

    public function test_attachment_can_be_updated_via_web_route(): void
    {
        $attachment = Attachment::factory()->create();

        $response = $this->actingAs($this->user)
            ->post(route('attachment.update', $attachment->id), [
                'attachments' => [UploadedFile::fake()->image('crop.jpg')],
                'attachment_text' => ['Testing'],
                'cut' => 'teaser',
            ]);

        $response->assertOk();
        $attachment->refresh();
        $this->assertSame('teaser', $attachment->cut);
        $this->assertCount(1, $response->json());
    }

    public function test_multiple_uploaded_attachments_keep_their_titles_and_cut(): void
    {
        $city = City::factory()->create();
        $service = Service::factory()->create(['city_id' => $city->id]);

        $response = $this->actingAs($this->user)
            ->post(route('service.attach', $service->slug), [
                'attachments' => [
                    UploadedFile::fake()->image('first.jpg'),
                    UploadedFile::fake()->create('second.pdf', 10, 'application/pdf'),
                ],
                'attachment_text' => ['Erstes Bild', 'Zweites Dokument'],
                'cut' => 'homepage',
            ]);

        $response->assertOk();
        $this->assertSame(
            ['Erstes Bild', 'Zweites Dokument'],
            $service->fresh()->attachments->pluck('title')->all()
        );
        $this->assertSame(
            ['homepage', 'homepage'],
            $service->fresh()->attachments->pluck('cut')->all()
        );
    }

    public function test_single_upload_to_an_existing_cut_replaces_that_cut(): void
    {
        $city = City::factory()->create();
        $service = Service::factory()->create(['city_id' => $city->id]);
        $existing = Attachment::factory()->create([
            'attachable_id' => $service->id,
            'attachable_type' => Service::class,
            'title' => 'Altes Bild',
            'cut' => 'homepage',
            'file' => 'attachments/old-homepage.jpg',
        ]);
        Storage::put($existing->file, 'old');

        $response = $this->actingAs($this->user)
            ->post(route('service.attach', $service->slug), [
                'attachments' => [UploadedFile::fake()->image('new.jpg')],
                'attachment_text' => ['Neues Bild'],
                'cut' => 'homepage',
            ]);

        $response->assertOk();
        $attachments = $service->fresh()->attachments;
        $this->assertCount(1, $attachments);
        $this->assertSame('Neues Bild', $attachments->first()->title);
        $this->assertFalse(Storage::exists('attachments/old-homepage.jpg'));
    }

    public function test_missing_attachment_file_still_serializes(): void
    {
        $attachment = Attachment::factory()->create([
            'title' => 'Fehlende Datei',
            'file' => 'attachments/does-not-exist.pdf',
        ]);

        $data = $attachment->fresh()->toArray();

        $this->assertFalse($data['hasFile']);
        $this->assertNull($data['size']);
        $this->assertSame('', $data['mimeType']);
        $this->assertSame('fa-exclamation-triangle', $data['icon']);
        $this->assertSame('pdf', $data['extension']);
        $this->assertSame('Die gespeicherte Datei wurde nicht gefunden.', $data['errorMessage']);
    }

    public function test_attachment_download_is_forbidden_when_parent_service_is_trashed(): void
    {
        $city = City::factory()->create();
        $service = Service::factory()->create(['city_id' => $city->id]);
        $attachment = Attachment::factory()->create([
            'attachable_id' => $service->id,
            'attachable_type' => Service::class,
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('gd-bearbeiten');
        $user->givePermissionTo('gd-allgemein-bearbeiten');
        $user->cities()->attach($city->id, ['permission' => 'w']);

        $service->delete();

        $response = $this->actingAs($user)->get(route('attachment', $attachment->id));

        $response->assertForbidden();
    }
}
