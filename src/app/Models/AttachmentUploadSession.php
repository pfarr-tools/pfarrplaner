<?php

namespace App\Models;

use App\Models\People\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A resumable browser upload session. */
class AttachmentUploadSession extends AbstractModel
{
    protected $table = 'attachment_upload_sessions';

    protected $fillable = [
        'user_id', 'attachable_type', 'attachable_id', 'filename', 'mime_type',
        'size', 'chunk_size', 'total_chunks', 'received_chunks', 'title', 'cut',
        'attachment_id', 'expires_at', 'completed_at',
    ];

    protected $casts = [
        'received_chunks' => 'array',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Attachment, $this> */
    public function attachment(): BelongsTo
    {
        return $this->belongsTo(Attachment::class);
    }
}
