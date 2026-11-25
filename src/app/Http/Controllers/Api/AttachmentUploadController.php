<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttachmentUploadSession;
use App\Models\People\User;
use App\Services\AttachmentUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Handle resumable attachment upload sessions. */
class AttachmentUploadController extends Controller
{
    /** Inject the upload session service. */
    public function __construct(private readonly AttachmentUploadService $uploads) {}

    /** Start an authenticated resumable upload session. */
    public function start(Request $request): JsonResponse
    {
        /** @var array<string, mixed> $input */
        $input = $request->validate([
            'target_type' => ['required', 'string'],
            'target_id' => ['required', 'integer'],
            'filename' => ['required', 'string'],
            'mime_type' => ['nullable', 'string'],
            'size' => ['required', 'integer', 'min:1'],
            'total_chunks' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string'],
            'cut' => ['nullable', 'string'],
        ]);
        $session = $this->uploads->start($this->authenticatedUser($request), $input);

        return response()->json([
            'session' => $session->id,
            'chunkSize' => $session->chunk_size,
            'received' => $session->received_chunks,
        ], Response::HTTP_CREATED);
    }

    /** Store one raw upload chunk. */
    public function chunk(Request $request, AttachmentUploadSession $session, int $index): JsonResponse
    {
        $session = $this->uploads->storeChunk(
            $this->authenticatedUser($request),
            $session,
            $index,
            $request->getContent(),
            $request->header('X-Chunk-Checksum')
        );

        return response()->json(['received' => $session->received_chunks]);
    }

    /** Return the current state of an active upload session. */
    public function status(Request $request, AttachmentUploadSession $session): JsonResponse
    {
        $session = $this->uploads->status($this->authenticatedUser($request), $session);

        return response()->json([
            'session' => $session->id,
            'chunkSize' => $session->chunk_size,
            'received' => $session->received_chunks,
        ]);
    }

    /** Complete a session and return its attachment. */
    public function complete(Request $request, AttachmentUploadSession $session): JsonResponse
    {
        return response()->json($this->uploads->complete($this->authenticatedUser($request), $session));
    }

    /** Abort a session and remove its temporary chunks. */
    public function abort(Request $request, AttachmentUploadSession $session): Response
    {
        $this->uploads->abort($this->authenticatedUser($request), $session);

        return response()->noContent();
    }

    /** Resolve the authenticated user required for every upload operation. */
    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, Response::HTTP_UNAUTHORIZED);

        return $user;
    }
}
