<?php

namespace Tests\Unit\Console;

use App\Console\Commands\TestCommand;
use App\Services\QueryLogService;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
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
        $file = QueryLogService::file();
        File::put($file, '');
        chmod($file, 0644);

        $method = new ReflectionMethod(TestCommand::class, 'repairQueryLogPermissions');
        $method->setAccessible(true);
        $method->invoke(new TestCommand);

        $this->assertSame(0666, fileperms($file) & 0777);
    }
}
