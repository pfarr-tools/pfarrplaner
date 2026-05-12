<?php
/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarr.tools/pfarrplaner
 * @version git: $Id$
 */

namespace App\Console\Commands;

use Dotenv\Exception\InvalidPathException;
use Dotenv\Parser\Parser;
use Dotenv\Store\StoreBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Env;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Process;

class TestCommand extends Command
{
    protected $signature = 'test
        {--without-tty : Disable output to TTY}
        {--compact : Indicates whether the compact printer should be used}
        {--coverage : Indicates whether code coverage information should be collected}
        {--min= : Indicates the minimum threshold enforcement for code coverage}
        {--p|parallel : Indicates if the tests should run in parallel}
        {--profile : Lists top 10 slowest tests}
        {--recreate-databases : Indicates if the test databases should be re-created}
        {--drop-databases : Indicates if the test databases should be dropped}
        {--without-databases : Indicates if database configuration should be performed}
        {--without-cache : Indicates if cache configuration should be performed}
        {--no-js : Skip JavaScript (vitest) tests}
        {--no-browser : Skip browser (Dusk) tests}
    ';

    protected $description = 'Run the application tests (PHPUnit, JavaScript, and browser)';

    public function __construct()
    {
        parent::__construct();
        $this->ignoreValidationErrors();
    }

    /**
     * @return int
     */
    public function handle(): int
    {
        $exitCode = 0;

        $exitCode = max($exitCode, $this->runPhpUnit());

        if (! $this->option('no-js')) {
            $exitCode = max($exitCode, $this->runVitest());
        }

        if (! $this->option('no-browser')) {
            $exitCode = max($exitCode, $this->runDusk());
        }

        return $exitCode;
    }

    /**
     * Remove env vars loaded from .env so phpunit.xml <env> entries are not blocked by inherited values.
     *
     * @return void
     */
    protected function clearEnv(): void
    {
        $vars = $this->envVarsFromFile(
            $this->laravel->environmentPath(),
            $this->laravel->environmentFile()
        );

        $repository = Env::getRepository();

        foreach ($vars as $name) {
            $repository->clear($name);
        }
    }

    /**
     * @param string $path
     * @param string $file
     * @return string[]
     */
    protected function envVarsFromFile(string $path, string $file): array
    {
        try {
            $content = StoreBuilder::createWithNoNames()
                ->addPath($path)
                ->addName($file)
                ->make()
                ->read();
        } catch (InvalidPathException) {
            return [];
        }

        return array_map(
            fn ($entry) => $entry->getName(),
            (new Parser)->parse($content)
        );
    }

    /**
     * @return int
     */
    protected function runPhpUnit(): int
    {
        $this->newLine();
        $this->line('<fg=yellow;options=bold>  PHPUnit Tests</>');
        $this->newLine();

        $this->clearEnv();

        $phpunit = base_path('vendor/phpunit/phpunit/phpunit');
        $config = file_exists(base_path('phpunit.xml'))
            ? base_path('phpunit.xml')
            : base_path('phpunit.xml.dist');

        $process = (new Process(
            [PHP_BINARY, $phpunit, '--configuration='.$config, '--no-output'],
            null,
            ['COLLISION_PRINTER' => 'DefaultPrinter'],
        ))->setTimeout(null);

        try {
            $process->setTty(! $this->option('without-tty'));
        } catch (\RuntimeException) {
            // TTY not supported — ignore
        }

        try {
            return $process->run(fn ($type, $line) => $this->output->write($line));
        } catch (ProcessSignaledException $e) {
            if (extension_loaded('pcntl') && $e->getSignal() !== SIGINT) {
                throw $e;
            }

            return 0;
        }
    }

    /**
     * @return int
     */
    protected function runVitest(): int
    {
        $this->newLine();
        $this->line('<fg=yellow;options=bold>  JavaScript Tests (Vitest)</>');
        $this->newLine();

        $process = (new Process(
            ['npm', 'run', 'test:js:run'],
            base_path(),
        ))->setTimeout(null);

        try {
            $process->setTty(! $this->option('without-tty'));
        } catch (\RuntimeException) {
            // TTY not supported — ignore
        }

        try {
            return $process->run(fn ($type, $line) => $this->output->write($line));
        } catch (ProcessSignaledException $e) {
            if (extension_loaded('pcntl') && $e->getSignal() !== SIGINT) {
                throw $e;
            }

            return 0;
        }
    }

    /**
     * @return int
     */
    protected function runDusk(): int
    {
        $this->newLine();
        $this->line('<fg=yellow;options=bold>  Browser Tests (Dusk)</>');
        $this->newLine();

        $args = ['--without-tty' => $this->option('without-tty')];

        return (int) $this->call('dusk:run', $args);
    }
}
