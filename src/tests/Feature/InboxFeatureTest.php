<?php

namespace Tests\Feature;

use App\Models\People\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class InboxFeatureTest extends TestCase
{
    use RefreshDatabase;

    private string $inboxPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->inboxPath = storage_path('inbox-feature-test');
        File::ensureDirectoryExists($this->inboxPath);
        config(['inbox.path' => 'inbox-feature-test']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->inboxPath);
        parent::tearDown();
    }

    public function test_inbox_file_can_be_downloaded_for_local_processing(): void
    {
        File::put($this->inboxPath.'/photo.jpg', 'image-data');

        $response = $this->actingAs(User::factory()->create())
            ->get(route('api.inbox.download', ['filename' => 'photo.jpg']));

        $response->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=photo.jpg');
        $this->assertSame('image-data', $response->streamedContent());
    }

    public function test_inbox_download_rejects_path_traversal_and_disallowed_files(): void
    {
        File::put($this->inboxPath.'/secret.txt', 'secret');

        $this->actingAs(User::factory()->create())
            ->get(route('api.inbox.download', ['filename' => '../secret.txt']))
            ->assertNotFound();

        $this->actingAs(User::factory()->create())
            ->get(route('api.inbox.download', ['filename' => 'secret.txt']))
            ->assertNotFound();
    }
}
