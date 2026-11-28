<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 * @version git: $
 */

namespace App\Models;

use App\Models\People\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit record explaining why a finalized offering was reopened for correction.
 */
class OfferingSettlementCorrection extends AbstractModel
{
    /** @var list<string> Writable correction audit fields. */
    protected $fillable = ['service_id', 'previous_settlement_id', 'user_id', 'reason'];

    /**
     * Return the corrected service.
     *
     * @return BelongsTo<Service, $this> Corrected service.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Return the previous immutable settlement.
     *
     * @return BelongsTo<OfferingSettlement, $this> Previous settlement.
     */
    public function previousSettlement(): BelongsTo
    {
        return $this->belongsTo(OfferingSettlement::class, 'previous_settlement_id');
    }

    /**
     * Return the responsible user.
     *
     * @return BelongsTo<User, $this> Responsible user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
