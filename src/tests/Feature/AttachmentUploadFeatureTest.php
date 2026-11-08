<?php

namespace Tests\Feature;

use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AttachmentUploadFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        Storage::fake();
        Permission::findOrCreate('gd-bearbeiten');
        Permission::findOrCreate('gd-allgemein-bearbeiten');
        $this->user = User::factory()->create();
        $this->user->assignRole(RoleService::ROLE_SUPER_ADMIN);
        $city = City::factory()->create();
        $this->service = Service::factory()->create(['city_id' => $city->id]);
        config(['attachments.upload.chunk_size' => 5]);
    }

    public function test_large_upload_can_resume_idempotent_chunks_and_complete_once(): void
    {
        $start = $this->withMiddleware()->actingAs($this->user, 'api')->postJson(route('api.attachment-uploads.start'), [
            'target_type' => Service::class,
            'target_id' => $this->service->id,
            'filename' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 10,
            'total_chunks' => 2,
            'title' => 'Werbebild',
            'cut' => 'homepage',
        ]);

        $start->assertCreated()->assertJsonPath('chunkSize', 5);
        $session = $start->json('session');

        $first = $this->withMiddleware()->actingAs($this->user, 'api')->call(
            'PUT',
            route('api.attachment-uploads.chunk', ['session' => $session, 'index' => 0]),
            [], [], [], [
                'CONTENT_TYPE' => 'application/octet-stream',
                'HTTP_X_CHUNK_CHECKSUM' => hash('sha256', '12345'),
            ], '12345'
        );
        $first->assertOk()->assertJsonPath('received', [0]);

        $this->withMiddleware()->actingAs($this->user, 'api')
            ->getJson(route('api.attachment-uploads.status', ['session' => $session]))
            ->assertOk()
            ->assertJsonPath('received', [0]);

        $this->withMiddleware()->actingAs($this->user, 'api')->call(
            'PUT',
            route('api.attachment-uploads.chunk', ['session' => $session, 'index' => 0]),
            [], [], [], [
                'CONTENT_TYPE' => 'application/octet-stream',
                'HTTP_X_CHUNK_CHECKSUM' => hash('sha256', '12345'),
            ], '12345'
        )->assertOk();

        $this->withMiddleware()->actingAs($this->user, 'api')->call(
            'PUT',
            route('api.attachment-uploads.chunk', ['session' => $session, 'index' => 1]),
            [], [], [], [
                'CONTENT_TYPE' => 'application/octet-stream',
                'HTTP_X_CHUNK_CHECKSUM' => hash('sha256', '67890'),
            ], '67890'
        )->assertOk()->assertJsonPath('received', [0, 1]);

        $complete = $this->withMiddleware()->actingAs($this->user, 'api')
            ->postJson(route('api.attachment-uploads.complete', ['session' => $session]));

        $complete->assertOk();
        $this->assertCount(1, $this->service->fresh()->attachments);
        $this->assertSame('homepage', $this->service->fresh()->attachments->first()->cut);

        $this->withMiddleware()->actingAs($this->user, 'api')
            ->postJson(route('api.attachment-uploads.complete', ['session' => $session]))
            ->assertOk();
        $this->assertCount(1, $this->service->fresh()->attachments);
    }

    public function test_completion_rejects_missing_chunks_and_abort_removes_session(): void
    {
        $start = $this->withMiddleware()->actingAs($this->user, 'api')->postJson(route('api.attachment-uploads.start'), [
            'target_type' => Service::class,
            'target_id' => $this->service->id,
            'filename' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'size' => 10,
            'total_chunks' => 2,
            'title' => 'Dokument',
        ]);
        $session = $start->json('session');

        $this->withMiddleware()->actingAs($this->user, 'api')
            ->postJson(route('api.attachment-uploads.complete', ['session' => $session]))
            ->assertUnprocessable();

        $this->withMiddleware()->actingAs($this->user, 'api')
            ->deleteJson(route('api.attachment-uploads.abort', ['session' => $session]))
            ->assertNoContent();
        $this->assertDatabaseMissing('attachment_upload_sessions', ['id' => $session]);
    }

    public function test_chunk_requires_checksum_and_expected_length(): void
    {
        $session = $this->withMiddleware()->actingAs($this->user, 'api')
            ->postJson(route('api.attachment-uploads.start'), [
                'target_type' => Service::class,
                'target_id' => $this->service->id,
                'filename' => 'photo.jpg',
                'mime_type' => 'image/jpeg',
                'size' => 6,
                'total_chunks' => 2,
                'title' => 'Prüfung',
            ])->json('session');

        $this->withMiddleware()->actingAs($this->user, 'api')->call(
            'PUT',
            route('api.attachment-uploads.chunk', ['session' => $session, 'index' => 0]),
            [], [], [], [
                'CONTENT_TYPE' => 'application/octet-stream',
                'HTTP_ACCEPT' => 'application/json',
            ], '12345'
        )->assertUnprocessable();

        $this->withMiddleware()->actingAs($this->user, 'api')->deleteJson(
            route('api.attachment-uploads.abort', ['session' => $session])
        )->assertNoContent();
    }
}
