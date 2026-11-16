<?php

namespace App\Models\People;

use App\Models\AbstractModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Persist a secret-free record of a Super-Admin two-factor recovery. */
class TwoFactorRecoveryAudit extends AbstractModel
{
    /** Keep this internal audit model out of generated web and API CRUD routes. */
    public static array $exceptRoutes = [
        'web' => ['index', 'create', 'show', 'edit', 'store', 'update', 'destroy'],
        'api' => ['index', 'show', 'store', 'update', 'destroy'],
    ];

    /** Attributes accepted for creating an audit event. */
    protected $fillable = [
        'actor_user_id',
        'target_user_id',
        'support_reference',
        'ip_address',
    ];

    /** @return BelongsTo<User, $this> The account that performed the recovery. */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /** @return BelongsTo<User, $this> The account whose factor was recovered. */
    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
