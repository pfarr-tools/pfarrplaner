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
 */

namespace Tests\Unit\Policies;

use App\Models\Attachment;
use App\Models\People\User;
use App\Policies\AttachmentPolicy;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AttachmentPolicyUnitTest extends TestCase
{
    use RefreshDatabase;

    private AttachmentPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::findOrCreate('gd-bearbeiten');
        $this->policy = new AttachmentPolicy();
    }

    public function testUserWithoutWritePermissionCannotUpdateAttachment(): void
    {
        $user = User::factory()->create();
        $attachment = Attachment::factory()->create();

        $this->assertFalse($this->policy->update($user, $attachment));
    }

    public function testUserWithServiceWritePermissionCanUpdateAttachment(): void
    {
        $user = User::factory()->create();
        $attachment = Attachment::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);

        $this->assertTrue($this->policy->update($user, $attachment));
    }

    /**
     * @return void
     */
    public function testAttachmentOnTrashedParentCannotBeUpdated(): void
    {
        $user = User::factory()->create();
        $attachment = Attachment::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);

        $attachment->attachable->delete();

        $this->assertFalse($this->policy->update($user, $attachment->fresh()));
    }
}
