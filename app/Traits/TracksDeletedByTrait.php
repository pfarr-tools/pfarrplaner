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

namespace App\Traits;

use App\Models\People\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait TracksDeletedByTrait
{
    /**
     * @return void
     */
    public static function bootTracksDeletedByTrait(): void
    {
        static::deleted(function ($model) {
            if ($model->isForceDeleting()) {
                return;
            }

            $deletedBy = Auth::id();
            if (!$deletedBy) {
                return;
            }

            $model->deleted_by = $deletedBy;
            $model->newQueryWithoutScopes()
                ->whereKey($model->getKey())
                ->update(['deleted_by' => $deletedBy]);
        });

        static::restored(function ($model) {
            $model->deleted_by = null;
            $model->newQueryWithoutScopes()
                ->whereKey($model->getKey())
                ->update(['deleted_by' => null]);
        });
    }

    /**
     * @return BelongsTo
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
