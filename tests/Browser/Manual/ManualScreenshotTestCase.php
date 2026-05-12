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

namespace Tests\Browser\Manual;

use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;

/**
 * Base class for Dusk tests that capture screenshots for the user manual.
 *
 * Screenshots are saved to manual/media/images/ so they can be referenced
 * directly in the manual's Markdown files as ![alt](media/images/name.png).
 */
abstract class ManualScreenshotTestCase extends AbstractPageLoadTest
{
    protected const APP_RENDER_TIMEOUT_SECONDS = 45;

    protected string $screenshotDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->screenshotDir = base_path('manual/media/images');
        if (!is_dir($this->screenshotDir)) {
            mkdir($this->screenshotDir, 0755, true);
        }

        $this->superAdminUser->setSetting('homeScreenTabsConfig', ['tabs' => []]);
    }

    /**
     * Navigate to $url, wait for the app to render, take a screenshot and
     * copy it to manual/media/images/{$filename}.png.
     *
     * @param Browser $browser       Already-authenticated browser instance
     * @param string  $url           URL to navigate to
     * @param string  $filename      Base name for the output file (without .png)
     * @param int     $pauseMs       Optional pause after page load (ms) for animations to settle
     * @return Browser
     */
    protected function captureManualScreenshot(
        Browser $browser,
        string  $url,
        string  $filename,
        int     $pauseMs = 500
    ): Browser {
        try {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit($url)
                    ->waitFor('#app', self::APP_RENDER_TIMEOUT_SECONDS)
                    ->assertDontSee('500')
                    ->assertDontSee('Whoops');
        } catch (\Throwable $exception) {
            $this->captureFailureArtifacts($browser, $filename);
            throw $exception;
        }

        if ($pauseMs > 0) {
            $browser->pause($pauseMs);
        }

        $this->captureCurrentManualScreenshot($browser, $filename);

        return $browser;
    }

    /**
     * Store the current browser state when a screenshot page fails to render.
     */
    protected function captureFailureArtifacts(Browser $browser, string $filename): void
    {
        $failureName = 'manual-failure-' . $filename;
        $browser->screenshot($failureName);

        $sourceDir = base_path('tests/Browser/source');
        if (!is_dir($sourceDir)) {
            mkdir($sourceDir, 0755, true);
        }
        file_put_contents($sourceDir . '/' . $failureName . '.html', $browser->driver->getPageSource());
    }

    /**
     * Capture the currently visible browser state for the manual.
     */
    protected function captureCurrentManualScreenshot(Browser $browser, string $filename, int $pauseMs = 300): Browser
    {
        if ($pauseMs > 0) {
            $browser->pause($pauseMs);
        }

        $duskName = 'manual-' . $filename;
        $browser->screenshot($duskName);

        $src = base_path('tests/Browser/screenshots/' . $duskName . '.png');
        $dst = $this->screenshotDir . '/' . $filename . '.png';
        if (file_exists($src)) {
            copy($src, $dst);
        }

        return $browser;
    }

    /**
     * Capture a single page element for manual screenshots.
     *
     * @param Browser $browser  Already-authenticated browser instance
     * @param string  $selector CSS selector for the visible element
     * @param string  $filename Base name for the output file (without .png)
     * @param int     $pauseMs  Optional pause before capture (ms)
     * @return Browser
     */
    protected function captureCurrentManualElementScreenshot(
        Browser $browser,
        string  $selector,
        string  $filename,
        int     $pauseMs = 300
    ): Browser {
        if ($pauseMs > 0) {
            $browser->pause($pauseMs);
        }

        $browser->script("document.querySelector('$selector')?.scrollIntoView({block: 'start', inline: 'nearest'});");
        $browser->pause(100);

        $duskName = 'manual-' . $filename;
        $browser->screenshotElement($selector, $duskName);

        $src = base_path('tests/Browser/screenshots/' . $duskName . '.png');
        $dst = $this->screenshotDir . '/' . $filename . '.png';
        if (file_exists($src)) {
            copy($src, $dst);
        }

        return $browser;
    }

    /**
     * Switch to a Bootstrap/Vue tab and capture it for the manual.
     */
    protected function captureTab(Browser $browser, string $tabId, string $filename, int $pauseMs = 300): Browser
    {
        $browser->script("document.querySelector('a[href=\"#$tabId\"]')?.click();");
        $browser->pause($pauseMs);

        return $this->captureCurrentManualScreenshot($browser, $filename, 0);
    }
}
