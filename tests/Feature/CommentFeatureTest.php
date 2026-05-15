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

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\People\User;
use App\Models\Service;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->assignRole(RoleService::ROLE_SUPER_ADMIN);
    }

    public function testCommentCanBeCreatedViaWebRoute(): void
    {
        $service = Service::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson(route('comment.store'), [
                'body' => 'Neuer Kommentar',
                'private' => false,
                'commentable_type' => Service::class,
                'commentable_id' => $service->id,
            ]);

        $response->assertOk()
            ->assertJson([
                'body' => 'Neuer Kommentar',
                'private' => false,
                'user_id' => $this->user->id,
            ]);
        $this->assertSame(1, Comment::count());
    }

    public function testCommentCanBeDeletedViaWebRoute(): void
    {
        $comment = Comment::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->delete(route('comment.destroy', $comment->id));

        $response->assertOk();
        $this->assertStringContainsString('Der Kommentar wurde gelöscht.', $response->getContent());
        $this->assertSame(0, Comment::count());
    }
}
