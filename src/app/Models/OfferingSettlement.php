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

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable finalized data set for one electronically signed offering.
 */
class OfferingSettlement extends AbstractModel
{
    /** @var list<string> Attributes that may be written during finalization. */
    protected $fillable = [
        'service_id',
        'revision',
        'snapshot',
        'signatures',
        'manifest',
        'status',
        'snapshot_hash',
        'manifest_hash',
        'pdf_path',
        'pdf_hash',
        'finalized_at',
    ];

    /** @var array<string, string> Database casts for structured settlement data. */
    protected $casts = [
        'snapshot' => 'array',
        'signatures' => 'array',
        'manifest' => 'array',
        'finalized_at' => 'datetime',
    ];

    /**
     * Return the service whose offering was finalized.
     *
     * @return BelongsTo<Service, $this> Related service.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
