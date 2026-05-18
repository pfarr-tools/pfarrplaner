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

use App\Models\Attachment;
use App\Models\People\User;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
        $this->user = User::factory()->create();
        $this->user->assignRole(RoleService::ROLE_SUPER_ADMIN);
    }

    public function testAttachmentCanBeUpdatedViaWebRoute(): void
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

    public function testMissingAttachmentFileStillSerializes(): void
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
}
