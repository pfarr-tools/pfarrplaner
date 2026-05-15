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

namespace App\Actions\Comment;

use App\Actions\AbstractCreateAction;
use App\Contracts\Comment\CreatesComments;
use App\Events\Models\Comment\CreatedComment;
use App\Models\Comment;
use App\Models\People\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class CreateComment extends AbstractCreateAction implements CreatesComments
{
    use ResolvesCommentOwnership;

    public function redirectTo(): string
    {
        return '';
    }

    public function create(User $user, array $input): Comment
    {
        $validated = Validator::make($input, [
            'body' => 'required|string',
            'private' => 'nullable|boolean',
            'commentable_type' => 'required|string',
            'commentable_id' => 'required|int',
        ])->validateWithBag('createComment');

        $owner = $this->resolveOwner($validated['commentable_type'], $validated['commentable_id']);
        Gate::forUser($user)->authorize('create', [Comment::class, $owner]);

        $comment = $owner->comments()->create([
            'body' => $validated['body'],
            'private' => $validated['private'] ?? false,
            'user_id' => $user->id,
        ]);
        $comment->load('user');

        CreatedComment::dispatch($user, $comment);
        $this->messages = ['success' => 'Der Kommentar wurde gespeichert.'];

        return $comment;
    }
}
