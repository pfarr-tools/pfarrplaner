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

namespace App\Services;

use App\Models\OfferingSettlement;
use App\Models\OfferingSettlementCorrection;
use App\Models\People\User;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use LogicException;

/**
 * Reopen a finalized offering through an audited correction workflow.
 */
class OfferingSettlementRevisionService
{
    /**
     * Reset mutable offering fields after recording an authorized correction.
     *
     * @param  Service  $service  Finalized service to correct.
     * @param  string  $reason  Human-readable correction reason.
     * @param  User  $user  Responsible authorized user.
     * @return Service Reset service ready for a new signed submission.
     */
    public function create(Service $service, string $reason, User $user): Service
    {
        Gate::forUser($user)->authorize('update', $service);
        if (trim($reason) === '') {
            throw new LogicException('Eine Begründung für die Korrektur ist erforderlich.');
        }
        $previous = OfferingSettlement::query()
            ->where('service_id', $service->id)
            ->where('status', 'finalized')
            ->latest('revision')
            ->firstOrFail();

        return DB::transaction(function () use ($service, $reason, $user, $previous): Service {
            OfferingSettlementCorrection::query()->create([
                'service_id' => $service->id,
                'previous_settlement_id' => $previous->id,
                'user_id' => $user->id,
                'reason' => trim($reason),
            ]);

            Service::withoutEvents(static function () use ($service): void {
                $service->update([
                    'offering_amount' => '',
                    'offering_denominations' => null,
                    'offerings_counter1' => '',
                    'offerings_counter2' => '',
                ]);
            });

            return $service->refresh();
        });
    }
}
