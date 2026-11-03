<?php

namespace Tests\Unit\Console;

use App\Console\Commands\TestCommand;
use App\Services\QueryLogService;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
use Tests\TestCase;

class TestCommandTest extends TestCase
{
    public function testQueryLogPermissionsAreRestoredBeforeFollowingTestSuites(): void
    {
        $file = QueryLogService::file();
        File::put($file, '');
        chmod($file, 0644);

        $method = new ReflectionMethod(TestCommand::class, 'repairQueryLogPermissions');
        $method->setAccessible(true);
        $method->invoke(new TestCommand());

        $this->assertSame(0666, fileperms($file) & 0777);
    }
}
