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

namespace Tests\Unit;

use App\Contracts\Attachment\UpdatesAttachments;
use App\Events\Models\Attachment\UpdatedAttachment;
use App\Models\Attachment;
use App\Models\People\User;
use App\Models\Rites\Funeral;
use App\Models\Service;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\AbstractSimpleModelUnitTest;

/**
 * Class AttachmentUnitTest
 */
class AttachmentUnitTest extends AbstractSimpleModelUnitTest
{
    use RefreshDatabase;

    protected string $modelClass = Attachment::class;

    /**
     * Test that an attachment can be created
     *
     * @return void
     */
    public function test_attachment_can_be_created()
    {
        $attachment = Attachment::create(['title' => 'cool title', 'file' => 'file.txt']);
        $this->assertCount(1, Attachment::all());
    }

    /**
     * Test that an attachment can be created and attached to a funeral
     *
     * @return void
     */
    public function test_attachment_can_be_created_for_funeral()
    {
        $funeral = Funeral::factory()->for(Service::factory(), 'service')->create();
        $this->assertCount(1, Funeral::all());
        $funeral->attachments()->create(['title' => 'cool title', 'file' => 'file.txt']);
        $this->assertCount(1, Funeral::first()->attachments);
    }

    public function test_attachment_update_route_exists(): void
    {
        $this->assertTrue(Route::has('attachment.update'));
    }

    public function test_attachment_can_be_updated_via_action(): void
    {
        Storage::fake();
        Event::fake();

        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $attachment = Attachment::factory()->create();

        $updated = app(UpdatesAttachments::class)->update($user, $attachment, [
            'attachments' => [UploadedFile::fake()->image('updated.jpg')],
            'cut' => 'header',
        ]);

        $this->assertInstanceOf(Attachment::class, $updated);
        $this->assertSame('header', $updated->cut);
        Storage::assertExists($updated->file);
        Event::assertDispatched(UpdatedAttachment::class);
    }
}
