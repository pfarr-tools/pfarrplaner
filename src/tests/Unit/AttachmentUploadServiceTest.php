<?php

namespace Tests\Unit;

use App\Services\AttachmentUploadService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AttachmentUploadServiceTest extends TestCase
{
    public function test_chunk_count_uses_configured_chunk_size(): void
    {
        Config::set('attachments.upload.chunk_size', 5);

        $this->assertSame(3, AttachmentUploadService::chunkCount(11));
    }
}
