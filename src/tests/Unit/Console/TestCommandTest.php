<?php

namespace Tests\Unit\Console;

use App\Console\Commands\TestCommand;
use App\Services\QueryLogService;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

class TestCommandTest extends TestCase
{
    /**
     * The safe test command must recognize PHPUnit's focused test filter.
     */
    public function test_filter_option_is_available_on_the_safe_test_command(): void
    {
        $command = new TestCommand;

        $this->assertTrue($command->getDefinition()->hasOption('filter'));
    }

    /** The safe test command must accept a focused test file or directory. */
    public function test_test_path_argument_is_available_on_the_safe_test_command(): void
    {
        $command = new TestCommand;

        $this->assertTrue($command->getDefinition()->hasArgument('testPath'));
    }

    public function test_query_log_permissions_are_restored_before_following_test_suites(): void
    {
        $originalStoragePath = $this->app->storagePath();
        $temporaryStoragePath = sys_get_temp_dir().'/pfarrplaner-query-log-'.uniqid('', true);
        $logFileProperty = new ReflectionProperty(QueryLogService::class, 'logFile');
        $originalLogFile = $logFileProperty->getValue();

        try {
            $this->app->useStoragePath($temporaryStoragePath);
            $logFileProperty->setValue(null, '');

            $file = QueryLogService::file();
            File::ensureDirectoryExists(dirname($file));
            File::put($file, '');
            chmod($file, 0644);

            $method = new ReflectionMethod(TestCommand::class, 'repairQueryLogPermissions');
            $method->setAccessible(true);
            $method->invoke(new TestCommand);
            clearstatcache(true, $file);

            $this->assertSame(0666, fileperms($file) & 0777);
        } finally {
            $this->app->useStoragePath($originalStoragePath);
            $logFileProperty->setValue(null, $originalLogFile);
            File::deleteDirectory($temporaryStoragePath);
        }
    }
}
