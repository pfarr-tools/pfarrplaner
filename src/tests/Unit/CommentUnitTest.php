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

namespace Tests\Unit;

use App\Contracts\Comment\CreatesComments;
use App\Contracts\Comment\DeletesComments;
use App\Events\Models\Comment\CreatedComment;
use App\Events\Models\Comment\DeletedComment;
use App\Models\Comment;
use App\Models\People\User;
use App\Models\Service;
use App\Services\RoleService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Tests\AbstractSimpleModelUnitTest;

class CommentUnitTest extends AbstractSimpleModelUnitTest
{
    protected string $modelClass = Comment::class;
    protected bool $hasPolicy = true;
    protected bool $hasFactory = true;

    public function testCommentCanBeCreatedViaFactory(): void
    {
        $comment = Comment::factory()->create();
        $this->assertCount(1, Comment::all());
    }

    public function testCommentUsesAbstractModelRouting(): void
    {
        $this->assertTrue(is_subclass_of(Comment::class, \App\Models\AbstractModel::class));
        $this->assertTrue(Route::has('comment.store'));
        $this->assertTrue(Route::has('comment.destroy'));
    }

    public function testCommentCanBeCreatedViaAction(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $service = Service::factory()->create();

        $comment = app(CreatesComments::class)->create($user, [
            'body' => 'Testkommentar',
            'private' => true,
            'commentable_type' => Service::class,
            'commentable_id' => $service->id,
        ]);

        $this->assertInstanceOf(Comment::class, $comment);
        $this->assertSame(1, Comment::count());
        $this->assertTrue((bool) $comment->private);
        Event::assertDispatched(CreatedComment::class);
    }

    public function testCommentCanBeDeletedViaAction(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $comment = Comment::factory()->create(['user_id' => $user->id]);

        $result = app(DeletesComments::class)->delete($user, $comment);

        $this->assertTrue($result);
        $this->assertSame(0, Comment::count());
        Event::assertDispatched(DeletedComment::class);
    }
}
