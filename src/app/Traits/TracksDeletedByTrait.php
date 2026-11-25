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
 */

namespace App\Traits;

use App\Models\People\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait TracksDeletedByTrait
{
    public static function bootTracksDeletedByTrait(): void
    {
        static::deleted(function (Model $model): void {
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                return;
            }

            $deletedBy = Auth::id();
            if (! $deletedBy) {
                return;
            }

            $model->setAttribute('deleted_by', $deletedBy);
            $model->newQueryWithoutScopes()
                ->whereKey($model->getKey())
                ->update(['deleted_by' => $deletedBy]);
        });

        static::restored(function (Model $model): void {
            $model->setAttribute('deleted_by', null);
            $model->newQueryWithoutScopes()
                ->whereKey($model->getKey())
                ->update(['deleted_by' => null]);
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
