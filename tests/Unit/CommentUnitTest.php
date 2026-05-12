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

use App\Models\Comment;
use App\Models\People\User;
use App\Models\Service;
use Tests\AbstractSimpleModelUnitTest;

class CommentUnitTest extends AbstractSimpleModelUnitTest
{
    protected string $modelClass = Comment::class;
    protected bool $hasPolicy = false;
    protected bool $hasFactory = true;

    public function testCommentCanBeCreatedViaFactory(): void
    {
        $comment = Comment::factory()->create();
        $this->assertCount(1, Comment::all());
    }
}
