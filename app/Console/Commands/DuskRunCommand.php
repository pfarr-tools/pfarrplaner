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

use Dotenv\Dotenv;
use Illuminate\Console\Command;
use Illuminate\Support\Env;
use Illuminate\Support\Str;
use NunoMaduro\Collision\Adapters\Phpunit\Subscribers\EnsurePrinterIsRegisteredSubscriber;
use PHPUnit\Runner\Version;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;
use Laravel\Dusk\Console\Concerns\InteractsWithTestingFrameworks;

#[AsCommand(name: 'dusk:run')]
class DuskRunCommand extends Command
{
    use InteractsWithTestingFrameworks;
    protected const QUIET_PROGRESS_INTERVAL_SECONDS = 30;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dusk:run
                {--browse : Open a browser instead of using headless mode}
                {--compact : Use the compact Collision printer output}
                {--debug-tests : Show each PHPUnit browser test while it runs}
                {--profile : List the slowest browser tests at the end}
                {--stop-on-failure : Stop the browser suite after the first failing test}
                {--testdox : Show browser tests in PHPUnit TestDox format}
                {--without-build : Reuse the existing frontend build artifacts}
                {--without-server : Reuse an already running Laravel server}
                {--without-tty : Disable output to TTY}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the Dusk tests for the application';

    /**
     * Indicates if the project has its own PHPUnit configuration.
     *
     * @var bool
     */
    protected $hasPhpUnitConfiguration = false;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->ignoreValidationErrors();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->purgeScreenshots();

        $this->purgeConsoleLogs();

        $this->purgeSourceLogs();

        $options = collect($_SERVER['argv'])
            ->slice(2)
            ->diff([
                '--browse', '--compact', '--debug-tests', '--profile', '--stop-on-failure', '--testdox', '--without-build', '--without-server', '--without-tty',
                '--quiet', '-q',
                '--verbose', '-v', '-vv', '-vvv',
                '--no-interaction', '-n',
            ])
            ->values()
            ->all();

        $this->reportBuildPhase();

        if (! $this->option('without-build')) {
            $buildExitCode = $this->prepareFrontendBuild();

            if ($buildExitCode !== 0) {
                return $buildExitCode;
            }
        }

        return $this->withDuskEnvironment(function () use ($options) {
            $this->resetDuskDatabase();

            $this->reportRunConfiguration($options);

            $server = $this->option('without-server') ? null : $this->startTestServer();

            try {
                $process = (new Process(array_merge(
                    $this->binary(), $this->phpunitArguments($options)
                ), null, $this->env()))->setTimeout(null);

                try {
                    $process->setTty(! $this->option('without-tty'));
                } catch (RuntimeException $e) {
                    $this->output->writeln('Warning: '.$e->getMessage());
                }

                return $this->runPhpUnitProcess($process, $options);
            } finally {
                $server?->stop();
            }
        });
    }

    /**
     * Report whether the frontend build will run for this test pass.
     *
     * @return void
     */
    protected function reportBuildPhase(): void
    {
        if ($this->option('without-build')) {
            $this->components->twoColumnDetail('Frontend build', 'übersprungen');
            return;
        }

        $this->components->twoColumnDetail('Frontend build', 'vite build');
    }

    protected function prepareFrontendBuild(): int
    {
        $process = (new Process(
            ['npm', 'run', 'build'],
            base_path(),
            array_merge($_ENV, $_SERVER, [
                'npm_config_cache' => '/tmp/.npm',
                'NPM_CONFIG_CACHE' => '/tmp/.npm',
            ]),
        ))->setTimeout(null);

        try {
            $process->setTty(! $this->option('without-tty'));
        } catch (RuntimeException $e) {
            $this->output->writeln('Warning: '.$e->getMessage());
        }

        return $process->run(function ($type, $line) {
            $this->output->write($line);
        });
    }

    /**
     * Run the PHPUnit child process and emit heartbeats during quiet compact runs.
     *
     * @param  Process  $process
     * @param  array  $options
     * @return int
     */
    protected function runPhpUnitProcess(Process $process, array $options): int
    {
        $showsQuietProgress = $this->shouldShowQuietProgress($options);
        $startedAt = microtime(true);
        $lastVisibleOutputAt = $startedAt;

        try {
            $process->start();

            while ($process->isRunning()) {
                $lastVisibleOutputAt = $this->drainPhpUnitOutput($process, $lastVisibleOutputAt);

                if (
                    $showsQuietProgress
                    && (microtime(true) - $lastVisibleOutputAt) >= self::QUIET_PROGRESS_INTERVAL_SECONDS
                ) {
                    $this->output->writeln(sprintf(
                        '  [Dusk] Läuft noch... %s ohne sichtbares Testergebnis. Für Live-Testnamen: `npm run test:dusk:debug`.',
                        $this->formatElapsedTime((int) floor(microtime(true) - $startedAt))
                    ));
                    $lastVisibleOutputAt = microtime(true);
                }

                usleep(100000);
            }

            $this->drainPhpUnitOutput($process, $lastVisibleOutputAt);

            return $process->getExitCode() ?? 1;
        } catch (ProcessSignaledException $e) {
            if (extension_loaded('pcntl') && $e->getSignal() !== SIGINT) {
                throw $e;
            }

            return 0;
        }
    }

    /**
     * Print the active Dusk runtime configuration before the suite starts.
     *
     * @param  array  $options
     * @return void
     */
    protected function reportRunConfiguration(array $options): void
    {
        $this->newLine();
        $this->components->twoColumnDetail('Dusk env', $this->resolvedDuskFile());
        $this->components->twoColumnDetail('APP_URL', $this->resolvedAppUrl());
        $this->components->twoColumnDetail('Browser mode', $this->option('browse') ? 'sichtbar' : 'headless');
        $this->components->twoColumnDetail('Laravel server', $this->option('without-server') ? 'bestehenden Server verwenden' : 'lokalen Testserver starten');
        $outputMode = 'Standardausgabe';
        if ($this->option('debug-tests')) {
            $outputMode = 'PHPUnit --debug';
        } elseif ($this->option('testdox')) {
            $outputMode = 'PHPUnit --testdox';
        }
        $this->components->twoColumnDetail('Testausgabe', $outputMode);

        if ($this->option('stop-on-failure')) {
            $this->components->twoColumnDetail('Abbruch bei Fehlern', 'aktiv');
        }

        if ($this->option('compact')) {
            $this->components->twoColumnDetail('Collision-Ausgabe', 'kompakt');
        } elseif ($this->option('profile')) {
            $this->components->twoColumnDetail('Collision-Ausgabe', 'mit Langsamste-Tests-Profil');
        }

        if ($options !== []) {
            $this->components->twoColumnDetail('Weitere Argumente', implode(' ', $options));
        }

        $this->newLine();
    }

    protected function resetDuskDatabase(): void
    {
        if ((string) Env::get('DB_CONNECTION') !== 'sqlite') {
            return;
        }

        $database = Env::get('DB_DATABASE');

        if (! is_string($database) || $database === '' || ! file_exists($database)) {
            return;
        }

        @unlink($database);
    }

    /**
     * Start the Laravel dev server using the current (dusk) environment.
     *
     * @return Process
     */
    protected function startTestServer(): Process
    {
        $appUrl = $this->resolvedAppUrl();
        $host   = parse_url($appUrl, PHP_URL_HOST) ?? '127.0.0.1';
        $port   = parse_url($appUrl, PHP_URL_PORT) ?? 8000;

        $server = new Process([PHP_BINARY, 'artisan', 'serve', "--host={$host}", "--port={$port}"]);
        $server->start();

        $deadline = time() + 30;
        while (time() < $deadline) {
            $ch = curl_init($appUrl);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 1, CURLOPT_NOBODY => true]);
            curl_exec($ch);
            $ready = curl_getinfo($ch, CURLINFO_HTTP_CODE) > 0;
            curl_close($ch);
            if ($ready) {
                break;
            }
            usleep(200_000);
        }

        return $server;
    }

    /**
     * Get the PHP binary to execute.
     *
     * @return array
     */
    protected function binary()
    {
        $binaryPath = 'vendor/phpunit/phpunit/phpunit';

        if ($this->usingPest()) {
            $binaryPath = 'vendor/pestphp/pest/bin/pest';
        }

        if ('phpdbg' === PHP_SAPI) {
            return [PHP_BINARY, '-qrr', $binaryPath];
        }

        return [PHP_BINARY, $binaryPath];
    }

    /**
     * Get the array of arguments for running PHPUnit.
     *
     * @param  array  $options
     * @return array
     */
    protected function phpunitArguments($options)
    {
        if ($this->shouldUseCollisionPrinter($options)) {
            $options[] = '--no-output';
        }

        if ($this->option('debug-tests') && ! in_array('--debug', $options, true)) {
            $options[] = '--debug';
        }

        if ($this->option('testdox') && ! in_array('--testdox', $options, true)) {
            $options[] = '--testdox';
        }

        if ($this->option('stop-on-failure') && ! in_array('--stop-on-failure', $options, true)) {
            $options[] = '--stop-on-failure';
        }

        $options = array_values(array_filter($options, function ($option) {
            return ! Str::startsWith($option, ['--env=', '--pest', '--ansi', '--no-ansi']);
        }));

        if (! file_exists($file = base_path('phpunit.dusk.xml'))) {
            $file = base_path('phpunit.dusk.xml.dist');
        }

        if (version_compare(Version::id(), '10.0', '>=')) {
            if ($this->option('ansi')) {
                $options[] = '--colors=always';
            }

            if ($this->option('no-ansi')) {
                $options[] = '--colors=never';
            }
        }

        return array_merge(['-c', $file], $options);
    }

    /**
     * Get the PHP binary environment variables.
     *
     * @return array|null
     */
    protected function env()
    {
        $variables = [];

        if ($this->option('browse') && ! isset($_ENV['CI']) && ! isset($_SERVER['CI'])) {
            $variables['DUSK_HEADLESS_DISABLED'] = true;
        }

        if ($this->shouldUseCollisionPrinter($this->forwardedPhpUnitOptions())) {
            $variables['COLLISION_PRINTER'] = 'DefaultPrinter';

            if ($this->option('compact')) {
                $variables['COLLISION_PRINTER_COMPACT'] = 'true';
            }

            if ($this->option('profile')) {
                $variables['COLLISION_PRINTER_PROFILE'] = 'true';
            }
        }

        return $variables;
    }

    /**
     * Determine if Collision's printer should be used.
     *
     * @return bool
     */
    protected function shouldUseCollisionPrinter(array $options = [])
    {
        return ! $this->option('debug-tests')
            && ! $this->option('testdox')
            && ! $this->wantsPhpUnitOwnOutput($options)
            && ! $this->usingPest()
            && class_exists(EnsurePrinterIsRegisteredSubscriber::class)
            && version_compare(Version::id(), '10.0', '>=');
    }

    /**
     * Determine if PHPUnit should render its own human-readable output mode.
     *
     * @param  string[]  $options
     * @return bool
     */
    protected function wantsPhpUnitOwnOutput(array $options): bool
    {
        foreach ($options as $option) {
            if ($option === '--debug' || $option === '--testdox' || str_starts_with($option, '--testdox-')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether compact/default runs should emit quiet heartbeats.
     *
     * @param  string[]  $options
     * @return bool
     */
    protected function shouldShowQuietProgress(array $options): bool
    {
        return ! $this->wantsPhpUnitOwnOutput($options);
    }

    /**
     * Flush incremental output from the PHPUnit child process.
     *
     * @param  Process  $process
     * @param  float  $lastVisibleOutputAt
     * @return float
     */
    protected function drainPhpUnitOutput(Process $process, float $lastVisibleOutputAt): float
    {
        $output = $process->getIncrementalOutput();
        $errorOutput = $process->getIncrementalErrorOutput();

        if ($output !== '') {
            $this->output->write($output);
            $lastVisibleOutputAt = microtime(true);
        }

        if ($errorOutput !== '') {
            $this->output->write($errorOutput);
            $lastVisibleOutputAt = microtime(true);
        }

        return $lastVisibleOutputAt;
    }

    /**
     * Get the PHPUnit arguments forwarded from the current CLI invocation.
     *
     * @return string[]
     */
    protected function forwardedPhpUnitOptions(): array
    {
        return collect($_SERVER['argv'])
            ->slice(2)
            ->diff([
                '--browse', '--compact', '--debug-tests', '--profile', '--stop-on-failure', '--testdox', '--without-build', '--without-server', '--without-tty',
                '--quiet', '-q',
                '--verbose', '-v', '-vv', '-vvv',
                '--no-interaction', '-n',
            ])
            ->values()
            ->all();
    }

    /**
     * Format elapsed runtime as MM:SS or HH:MM:SS.
     *
     * @param  int  $seconds
     * @return string
     */
    protected function formatElapsedTime(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
        }

        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }

    /**
     * Purge the failure screenshots.
     *
     * @return void
     */
    protected function purgeScreenshots()
    {
        $this->purgeDebuggingFiles(
            base_path('tests/Browser/screenshots'), 'failure-*'
        );
    }

    /**
     * Purge the console logs.
     *
     * @return void
     */
    protected function purgeConsoleLogs()
    {
        $this->purgeDebuggingFiles(
            base_path('tests/Browser/console'), '*.log'
        );
    }

    /**
     * Purge the source logs.
     *
     * @return void
     */
    protected function purgeSourceLogs()
    {
        $this->purgeDebuggingFiles(
            base_path('tests/Browser/source'), '*.txt'
        );
    }

    /**
     * Purge debugging files based on path and patterns.
     *
     * @param  string  $path
     * @param  string  $patterns
     * @return void
     */
    protected function purgeDebuggingFiles($path, $patterns)
    {
        if (! is_dir($path)) {
            return;
        }

        $files = Finder::create()->files()
            ->in($path)
            ->name($patterns);

        foreach ($files as $file) {
            @unlink($file->getRealPath());
        }
    }

    /**
     * Run the given callback with the Dusk configuration files.
     *
     * @param  \Closure  $callback
     * @return mixed
     */
    protected function withDuskEnvironment($callback)
    {
        $this->setupDuskEnvironment();

        try {
            return $callback();
        } finally {
            $this->teardownDuskEnvironment();
        }
    }

    /**
     * Setup the Dusk environment.
     *
     * @return void
     */
    protected function setupDuskEnvironment()
    {
        if (file_exists(base_path($this->duskFile()))) {
            if (file_exists(base_path('.env')) &&
                file_get_contents(base_path('.env')) !== file_get_contents(base_path($this->duskFile()))) {
                $this->backupEnvironment();
            }

            $this->refreshEnvironment();
        }

        $this->writeConfiguration();

        $this->setupSignalHandler();
    }

    /**
     * Backup the current environment file.
     *
     * @return void
     */
    protected function backupEnvironment()
    {
        copy(base_path('.env'), base_path('.env.backup'));

        copy(base_path($this->duskFile()), base_path('.env'));
    }

    /**
     * Refresh the current environment variables.
     *
     * @return void
     */
    protected function refreshEnvironment()
    {
        Dotenv::createMutable(base_path())->load();

        $this->preserveEnvironmentOverride('APP_URL');
    }

    /**
     * Re-apply selected parent-process environment variables after loading the
     * Dusk env file so wrapper scripts can target a temporary app server.
     */
    protected function preserveEnvironmentOverride(string $key): void
    {
        $value = getenv($key);

        if ($value === false || $value === '') {
            return;
        }

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv("{$key}={$value}");
    }

    /**
     * Write the Dusk PHPUnit configuration.
     *
     * @return void
     */
    protected function writeConfiguration()
    {
        if (! file_exists($file = base_path('phpunit.dusk.xml')) &&
            ! file_exists(base_path('phpunit.dusk.xml.dist'))) {
            copy(realpath(__DIR__.'/../../../vendor/laravel/dusk/stubs/phpunit.xml'), $file);

            return;
        }

        $this->hasPhpUnitConfiguration = true;
    }

    /**
     * Setup the SIGINT signal handler for CTRL+C exits.
     *
     * @return void
     */
    protected function setupSignalHandler()
    {
        if (extension_loaded('pcntl')) {
            pcntl_async_signals(true);

            pcntl_signal(SIGINT, function () {
                $this->teardownDuskEnvironment();
            });
        }
    }

    /**
     * Restore the original environment.
     *
     * @return void
     */
    protected function teardownDuskEnvironment()
    {
        $this->removeConfiguration();

        if (file_exists(base_path($this->duskFile())) && file_exists(base_path('.env.backup'))) {
            $this->restoreEnvironment();
        }
    }

    /**
     * Remove the Dusk PHPUnit configuration.
     *
     * @return void
     */
    protected function removeConfiguration()
    {
        if (! $this->hasPhpUnitConfiguration && file_exists($file = base_path('phpunit.dusk.xml'))) {
            unlink($file);
        }
    }

    /**
     * Restore the backed-up environment file.
     *
     * @return void
     */
    protected function restoreEnvironment()
    {
        copy(base_path('.env.backup'), base_path('.env'));

        unlink(base_path('.env.backup'));
    }

    /**
     * Get the name of the Dusk file for the environment.
     *
     * @return string
     */
    protected function duskFile()
    {
        if (file_exists(base_path($file = '.env.dusk.'.$this->laravel->environment()))) {
            return $file;
        }

        return '.env.dusk';
    }

    /**
     * Get the Dusk env file that will be loaded for this run.
     *
     * @return string
     */
    protected function resolvedDuskFile(): string
    {
        return file_exists(base_path($this->duskFile()))
            ? $this->duskFile()
            : 'keine eigene Dusk-Env-Datei';
    }

    /**
     * Resolve the app URL for the current Dusk run.
     *
     * @return string
     */
    protected function resolvedAppUrl(): string
    {
        return getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? 'http://127.0.0.1:8000');
    }
}
