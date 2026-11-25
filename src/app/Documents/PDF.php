<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 * @version git: $Id$
 *
 *
 * Pfarrplaner is based on the Laravel framework (https://laravel.com).
 * This file may contain code created by Laravel's scaffolding functions.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Documents;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;
use Spatie\Browsershot\ChromiumResult;
use Spatie\Browsershot\Exceptions\CouldNotTakeBrowsershot;
use Spatie\Browsershot\Exceptions\ElementNotFound;
use Spatie\Browsershot\Exceptions\RemoteConnectionException;
use Spatie\Browsershot\Exceptions\UnsuccessfulResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/** Render PDFs with the application's configured Chromium executable. */
class PDF extends Browsershot
{
    /**
     * Configure the PDF renderer with the container's Chromium executable.
     *
     * @param  string  $url  Optional source URL for the browser.
     */
    public function __construct(string $url = '')
    {
        parent::__construct($url);
        $this->noSandbox();
        $chromePath = config('services.browsershot.chrome_path');
        if (is_string($chromePath)) {
            $this->setChromePath($chromePath);
        }
    }

    /**
     * Return the default embedded styles for generated PDFs.
     *
     * @return string Embedded font and layout styles.
     */
    protected static function getDefaultPdfStyles(): string
    {
        $lightFont = static::getEmbeddedFontDataUri(resource_path('fonts/Sarabun-Light.ttf'));
        $semiBoldFont = static::getEmbeddedFontDataUri(resource_path('fonts/Sarabun-SemiBold.ttf'));

        return <<<HTML
<style>
    @font-face {
        font-family: 'Sarabun Light';
        src: url('{$lightFont}') format('truetype');
        font-weight: 300;
        font-style: normal;
    }

    @font-face {
        font-family: 'Sarabun SemiBold';
        src: url('{$semiBoldFont}') format('truetype');
        font-weight: 600;
        font-style: normal;
    }

    *, html, body, table td {
        font-family: 'Sarabun Light', sans-serif;
        font-weight: 300;
    }

    h1, h2, h3, h4, h5, h6 {
        font-family: 'Sarabun SemiBold', sans-serif;
        font-weight: 600;
    }
</style>
HTML;
    }

    /**
     * Convert a font file into a data URI for inline PDF styles.
     *
     * @param  string  $path  Font file path.
     * @return string Encoded font data URI.
     */
    protected static function getEmbeddedFontDataUri(string $path): string
    {
        $font = file_get_contents($path);
        if ($font === false) {
            throw new \RuntimeException('PDF-Schriftdatei konnte nicht gelesen werden: '.$path);
        }

        return 'data:font/ttf;base64,'.base64_encode($font);
    }

    /**
     * Add the default PDF styles to rendered HTML.
     *
     * @param  string  $html  Rendered document markup.
     * @return string Markup with the default styles included.
     */
    protected static function applyDefaultPdfStyles(string $html): string
    {
        $styles = static::getDefaultPdfStyles();

        if (str_contains($html, '</head>')) {
            return str_replace('</head>', $styles.'</head>', $html);
        }

        return $styles.$html;
    }

    /**
     * Ensure rendered PDF source HTML has a useful document title.
     *
     * @param  string  $html  Rendered document markup.
     * @param  string  $viewName  View used to render the document.
     * @return string Markup with a non-empty document title.
     */
    protected static function applyDefaultPdfTitle(string $html, string $viewName): string
    {
        if (preg_match('/<title\\b[^>]*>(.*?)<\\/title>/is', $html, $matches)
            && trim(strip_tags($matches[1])) !== '') {
            return $html;
        }

        $viewTitle = (string) preg_replace('/\\.render$/', '', $viewName);
        $title = htmlspecialchars(
            Str::headline(str_replace('.', ' ', $viewTitle)),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $titleTag = '<title>'.$title.'</title>';

        if (preg_match('/<title\\b[^>]*>.*?<\\/title>/is', $html)) {
            return (string) preg_replace('/<title\\b[^>]*>.*?<\\/title>/is', $titleTag, $html, 1);
        }

        if (str_contains($html, '</head>')) {
            return str_replace('</head>', $titleTag.'</head>', $html);
        }

        return $titleTag.$html;
    }

    /**
     * Render a view as a configured PDF document.
     *
     * @param  string  $viewName  Name of the view to render.
     * @param  array<array-key, mixed>  $data  Variables passed to the view.
     * @param  string  $header  Optional HTML header template.
     * @param  string  $footer  Optional HTML footer template.
     * @return static Configured PDF renderer.
     */
    public static function fromView(string $viewName, array $data, string $header = '', string $footer = ''): static
    {
        $viewPath = View::getFinder()->find($viewName);
        $html = View::file($viewPath, $data)->render();
        $html = static::applyDefaultPdfTitle($html, $viewName);
        $html = static::applyDefaultPdfStyles($html);

        $pdf = static::html($html)
            ->setCustomTempPath(storage_path('app/tmp'))
            ->format('A4')
            ->margins(10, 20, 10, 20)
            ->showBackground()
            ->waitUntilNetworkIdle();

        if (($header !== '') || ($footer !== '')) {
            $pdf->showBrowserHeaderAndFooter()
                ->headerHtml($header ?: '<div style="font-size: 8px; width: 100%;"></div>')
                ->footerHtml($footer ?: '<div style="font-size: 8px; width: 100%;"></div>');
        }

        return $pdf;
    }

    /**
     * Send the rendered PDF to the client as a file download.
     *
     * @param  string  $filename  Name used for the downloaded file.
     *
     * @throws CouldNotTakeBrowsershot
     */
    public function download(string $filename): BinaryFileResponse
    {
        $tempFile = storage_path('app/tmp/'.$filename);
        File::ensureDirectoryExists(dirname($tempFile));
        $this->save($tempFile);

        return response()->download($tempFile, $filename, ['Content-Type' => 'application/pdf'])
            ->deleteFileAfterSend(true);
    }

    /**
     * Start Browsershot with a guaranteed working directory even if the runtime
     * environment does not provide a valid current directory.
     *
     * @param  array<array-key, mixed>  $command  Chromium command created by Browsershot.
     * @return string Result reported by Chromium.
     */
    protected function callBrowser(array $command): string
    {
        $fullCommand = $this->getFullCommand($command);
        $workingDirectory = base_path() ?: getcwd() ?: storage_path('app');

        $windowsEnv = $this->isWindows() ? $this->getWindowsEnv() : null;
        /** @var array<string, string|false>|null $windowsEnv */
        $process = is_array($fullCommand)
            ? new Process(array_values(array_map(static fn (mixed $part): string => is_scalar($part) ? (string) $part : '', $fullCommand)), $workingDirectory, $windowsEnv)
            : Process::fromShellCommandline($fullCommand, $workingDirectory);

        $process->setTimeout($this->timeout);

        $this->chromiumResult = null;
        $process->run();

        $rawOutput = rtrim($process->getOutput());
        $decodedOutput = json_decode($rawOutput, true);
        $this->chromiumResult = new ChromiumResult(is_array($decodedOutput) ? $decodedOutput : null);

        if ($process->isSuccessful()) {
            $result = $this->chromiumResult->getResult();

            $this->cleanupTemporaryOptionsFile();

            return $result;
        }

        $this->cleanupTemporaryOptionsFile();
        $process->clearOutput();
        $exitCode = $process->getExitCode();
        $errorOutput = $process->getErrorOutput();

        if ($exitCode === 4) {
            throw RemoteConnectionException::make(rtrim($errorOutput));
        }

        if ($exitCode === 3) {
            throw UnsuccessfulResponse::make($this->url, $errorOutput);
        }

        if ($exitCode === 2) {
            $selector = $this->additionalOptions['selector'] ?? null;
            throw ElementNotFound::make(is_string($selector) ? $selector : '');
        }

        throw new ProcessFailedException($process);
    }
}
