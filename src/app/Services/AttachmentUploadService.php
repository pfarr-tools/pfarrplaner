<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\AttachmentUploadSession;
use App\Models\Leave\Absence;
use App\Models\People\User;
use App\Models\Rites\Baptism;
use App\Models\Rites\Funeral;
use App\Models\Rites\Wedding;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Creates and finalizes resumable attachment uploads. */
class AttachmentUploadService
{
    /** @var array<string, class-string<Model>> */
    private const TARGETS = [
        'App\\Models\\Service' => Service::class,
        'App\\Models\\Leave\\Absence' => Absence::class,
        'App\\Models\\Rites\\Baptism' => Baptism::class,
        'App\\Models\\Rites\\Funeral' => Funeral::class,
        'App\\Models\\Rites\\Wedding' => Wedding::class,
    ];

    /** Calculate the number of chunks for a byte size. */
    public static function chunkCount(int $size): int
    {
        return (int) ceil($size / max(1, (int) config('attachments.upload.chunk_size')));
    }

    /** Start an authorized upload session. */
    public function start(User $user, array $input): AttachmentUploadSession
    {
        $targetClass = self::TARGETS[$input['target_type'] ?? ''] ?? null;
        $target = $targetClass ? $targetClass::findOrFail($input['target_id']) : null;
        if (! $target) {
            throw ValidationException::withMessages(['target_type' => 'Ungültiges Uploadziel.']);
        }
        Gate::forUser($user)->authorize('update', $target);

        $size = (int) $input['size'];
        $chunkSize = (int) config('attachments.upload.chunk_size');
        $totalChunks = (int) ($input['total_chunks'] ?? self::chunkCount($size));
        if ($totalChunks !== self::chunkCount($size)) {
            throw ValidationException::withMessages(['total_chunks' => 'Die Chunk-Anzahl passt nicht zur Dateigröße.']);
        }

        return AttachmentUploadSession::create([
            'user_id' => $user->id,
            'attachable_type' => $target->getMorphClass(),
            'attachable_id' => $target->getKey(),
            'filename' => basename((string) $input['filename']),
            'mime_type' => $input['mime_type'] ?? null,
            'size' => $size,
            'chunk_size' => $chunkSize,
            'total_chunks' => $totalChunks,
            'received_chunks' => [],
            'title' => (string) $input['title'],
            'cut' => $input['cut'] ?? null,
            'expires_at' => now()->addMinutes((int) config('attachments.upload.expiry_minutes')),
        ]);
    }

    /** Store one idempotent chunk. */
    public function storeChunk(User $user, AttachmentUploadSession $session, int $index, string $content, ?string $checksum): AttachmentUploadSession
    {
        $this->assertSession($user, $session);
        $this->authorizeTarget($user, $session);
        if ($index < 0 || $index >= $session->total_chunks) {
            throw ValidationException::withMessages(['index' => 'Ungültiger Chunk.']);
        }
        $expectedLength = $index === $session->total_chunks - 1
            ? $session->size - ($index * $session->chunk_size)
            : $session->chunk_size;
        if (strlen($content) !== $expectedLength) {
            throw ValidationException::withMessages(['chunk' => 'Die Chunk-Größe ist ungültig.']);
        }
        if (! $checksum || ! hash_equals(strtolower($checksum), hash('sha256', $content))) {
            throw ValidationException::withMessages(['checksum' => 'Die Chunk-Prüfsumme ist ungültig.']);
        }

        $path = $this->chunkPath($session, $index);
        if (Storage::exists($path) && Storage::get($path) !== $content) {
            throw ValidationException::withMessages(['chunk' => 'Der Chunk wurde bereits mit anderen Daten gespeichert.']);
        }
        Storage::put($path, $content);
        $received = collect($session->received_chunks ?: [])->push($index)->unique()->sort()->values()->all();
        $session->update(['received_chunks' => $received]);

        return $session->refresh();
    }

    /** Complete a session and create one attachment. */
    public function complete(User $user, AttachmentUploadSession $session): Attachment
    {
        $this->assertOwner($user, $session);
        $this->authorizeTarget($user, $session);
        if ($session->attachment_id) {
            return $session->attachment()->firstOrFail();
        }
        abort_unless($session->expires_at->isFuture(), 404);
        if (count($session->received_chunks ?: []) !== $session->total_chunks) {
            throw ValidationException::withMessages(['chunks' => 'Es fehlen noch Upload-Chunks.']);
        }

        $content = '';
        for ($index = 0; $index < $session->total_chunks; $index++) {
            $content .= Storage::get($this->chunkPath($session, $index));
        }
        abort_unless(strlen($content) === $session->size, 422);
        $path = 'attachments/'.Str::random(32).'.'.pathinfo($session->filename, PATHINFO_EXTENSION);
        Storage::put($path, $content);

        return DB::transaction(function () use ($user, $session, $path) {
            $lockedSession = AttachmentUploadSession::query()->lockForUpdate()->findOrFail($session->id);
            $this->assertOwner($user, $lockedSession);
            $this->authorizeTarget($user, $lockedSession);
            if ($lockedSession->attachment_id) {
                Storage::delete($path);

                return $lockedSession->attachment()->firstOrFail();
            }
            if ($lockedSession->cut) {
                Attachment::query()
                    ->where('attachable_type', $lockedSession->attachable_type)
                    ->where('attachable_id', $lockedSession->attachable_id)
                    ->where('cut', $lockedSession->cut)
                    ->get()
                    ->each(function (Attachment $oldAttachment): void {
                        if (Attachment::where('file', $oldAttachment->file)->where('id', '<>', $oldAttachment->id)->doesntExist()) {
                            Storage::delete($oldAttachment->file);
                        }
                        $oldAttachment->delete();
                    });
            }
            $attachment = Attachment::create([
                'title' => $lockedSession->title,
                'file' => $path,
                'cut' => $lockedSession->cut,
                'attachable_id' => $lockedSession->attachable_id,
                'attachable_type' => $lockedSession->attachable_type,
            ]);
            $lockedSession->update(['attachment_id' => $attachment->id, 'completed_at' => now()]);
            $this->deleteChunks($lockedSession);

            return $attachment;
        });
    }

    /** Abort a session and remove temporary chunks. */
    public function abort(User $user, AttachmentUploadSession $session): void
    {
        $this->assertSession($user, $session);
        $this->authorizeTarget($user, $session);
        $this->deleteChunks($session);
        $session->delete();
    }

    /** Return an active session for retrying an interrupted upload. */
    public function status(User $user, AttachmentUploadSession $session): AttachmentUploadSession
    {
        $this->assertSession($user, $session);
        $this->authorizeTarget($user, $session);

        return $session->refresh();
    }

    /** Remove expired sessions and their temporary chunks. */
    public function cleanup(): void
    {
        AttachmentUploadSession::where('expires_at', '<', now())->get()->each(function (AttachmentUploadSession $session) {
            $this->deleteChunks($session);
            $session->delete();
        });
    }

    /** Assert that an active session belongs to the current user. */
    private function assertSession(User $user, AttachmentUploadSession $session): void
    {
        $this->assertOwner($user, $session);
        abort_unless(! $session->completed_at && $session->expires_at->isFuture(), 404);
    }

    /** Assert that a session belongs to the current user. */
    private function assertOwner(User $user, AttachmentUploadSession $session): void
    {
        abort_unless((int) $session->user_id === (int) $user->id, 404);
    }

    /** Return the target model and verify the user's edit permission. */
    private function authorizeTarget(User $user, AttachmentUploadSession $session): Model
    {
        $targetClass = self::TARGETS[$session->attachable_type] ?? null;
        abort_unless($targetClass, 404);
        $target = $targetClass::find($session->attachable_id);
        abort_unless($target, 404);
        Gate::forUser($user)->authorize('update', $target);

        return $target;
    }

    /** Return the temporary storage path for a chunk. */
    private function chunkPath(AttachmentUploadSession $session, int $index): string
    {
        return 'attachment-upload-sessions/'.$session->id.'/'.$index;
    }

    /** Delete all temporary chunks for a session. */
    private function deleteChunks(AttachmentUploadSession $session): void
    {
        Storage::deleteDirectory('attachment-upload-sessions/'.$session->id);
    }
}
