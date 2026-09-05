<?php

namespace Tests;

use Throwable;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Collection;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\TestCase as BaseTestCase;
use RuntimeException;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    private static bool $databaseResetDone = false;
    protected string $downloadDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->downloadDirectory = static::downloadDirectory();
        if (!is_dir($this->downloadDirectory)) {
            mkdir($this->downloadDirectory, 0777, true);
        }

        $files = glob($this->downloadDirectory . DIRECTORY_SEPARATOR . '*');
        if (is_array($files)) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    public static function setUpBeforeClass(): void
    {
        // Reset migration state once per process so DatabaseTruncation re-migrates
        // the Dusk SQLite file instead of assuming the feature-test in-memory DB state.
        if (! self::$databaseResetDone) {
            static::resetDuskDatabaseFile();
            RefreshDatabaseState::$migrated = false;
            self::$databaseResetDone = true;
        }
        parent::setUpBeforeClass();
        if (! static::runningInSail()) {
            static::startLocalChromeDriver();
        }
    }

    /**
     * Start a ChromeDriver process and wait until it accepts WebDriver sessions.
     *
     * @return void
     */
    protected static function startLocalChromeDriver(): void
    {
        $systemChromeDriver = '/usr/bin/chromedriver';
        if (is_executable($systemChromeDriver)) {
            static::useChromedriver($systemChromeDriver);
        }

        static::startChromeDriver([
            '--port=9515'
        ]);

        static::waitForChromeDriver();
    }

    /**
     * Wait for ChromeDriver to listen before Dusk creates the first browser.
     *
     * @return void
     */
    protected static function waitForChromeDriver(): void
    {
        $startedAt = microtime(true);

        do {
            $connection = @fsockopen('127.0.0.1', 9515, $errorCode, $errorMessage, 0.2);
            if (is_resource($connection)) {
                fclose($connection);
                return;
            }

            usleep(100000);
        } while (microtime(true) - $startedAt < 5);

        throw new RuntimeException('ChromeDriver did not become available on port 9515.');
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $downloadDirectory = static::downloadDirectory();
        if (!is_dir($downloadDirectory)) {
            mkdir($downloadDirectory, 0777, true);
        }

        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--disable-software-rasterizer',
            '--disable-extensions',
            '--disable-background-networking',
            '--ozone-platform=x11',        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());
        $options->setExperimentalOption('prefs', [
            'download.default_directory' => $downloadDirectory,
            'download.prompt_for_download' => false,
            'download.directory_upgrade' => true,
            'plugins.always_open_pdf_externally' => true,
            'safebrowsing.enabled' => true,
        ]);

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    /**
     * Determine whether the Dusk command has disabled headless mode.
     */
    protected function hasHeadlessDisabled(): bool
    {
        return isset($_SERVER['DUSK_HEADLESS_DISABLED']) ||
               isset($_ENV['DUSK_HEADLESS_DISABLED']);
    }

    /**
     * Determine if the browser window should start maximized.
     */
    protected function shouldStartMaximized(): bool
    {
        return isset($_SERVER['DUSK_START_MAXIMIZED']) ||
               isset($_ENV['DUSK_START_MAXIMIZED']);
    }

    protected static function downloadDirectory(): string
    {
        return base_path('tests/Browser/downloads');
    }

    protected static function resetDuskDatabaseFile(): void
    {
        if ((string) env('DB_CONNECTION') !== 'sqlite') {
            return;
        }

        $database = env('DB_DATABASE');
        if (!is_string($database) || $database === '') {
            return;
        }

        if (file_exists($database)) {
            unlink($database);
        }
    }

    protected function storeConsoleLogsFor($browsers): void
    {
        $browsers->each(function ($browser, $key) {
            try {
                $name = $this->getCallerName();
                $browser->storeConsoleLog($name.'-'.$key);
            } catch (Throwable) {
                // ChromeDriver can disappear before log collection on long Dusk runs.
            }
        });
    }
}
