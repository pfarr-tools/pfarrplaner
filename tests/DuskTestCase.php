<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\TestCase as BaseTestCase;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    private static bool $databaseResetDone = false;
    protected string $downloadDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->downloadDirectory = static::downloadDirectory();
        File::ensureDirectoryExists($this->downloadDirectory);
        File::cleanDirectory($this->downloadDirectory);
    }

    public static function setUpBeforeClass(): void
    {
        // Reset migration state once per process so DatabaseTruncation re-migrates
        // the Dusk SQLite file instead of assuming the feature-test in-memory DB state.
        if (! self::$databaseResetDone) {
            RefreshDatabaseState::$migrated = false;
            self::$databaseResetDone = true;
        }
        parent::setUpBeforeClass();
        if (! static::runningInSail()) {
            static::startChromeDriver([
                '--port=9515'
            ]);
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        File::ensureDirectoryExists(static::downloadDirectory());

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
            'download.default_directory' => static::downloadDirectory(),
            'download.prompt_for_download' => false,
            'download.directory_upgrade' => true,
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
}
