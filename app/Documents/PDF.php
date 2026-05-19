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
 *
 * Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
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
use Spatie\Browsershot\ChromiumResult;
use Spatie\Browsershot\Browsershot;
use Spatie\Browsershot\Exceptions\ElementNotFound;
use Spatie\Browsershot\Exceptions\RemoteConnectionException;
use Spatie\Browsershot\Exceptions\UnsuccessfulResponse;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PDF extends Browsershot
{
    /**
     * @param string $viewName
     * @param array $data
     * @return static
     */
    public static function fromView($viewName, $data)
    {
        return static::html(View::make($viewName, $data)->render())
            ->setCustomTempPath(storage_path('app/tmp'))
            ->format('A4')
            ->margins(10, 20, 10, 20)
            ->showBackground()
            ->waitUntilNetworkIdle();
    }

    /**
     * @param $filename
     * @return BinaryFileResponse
     * @throws \Spatie\Browsershot\Exceptions\CouldNotTakeBrowsershot
     */
    public function download($filename): BinaryFileResponse
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
     * @param array $command
     * @return string
     */
    protected function callBrowser(array $command): string
    {
        $fullCommand = $this->getFullCommand($command);
        $workingDirectory = base_path() ?: getcwd() ?: storage_path('app');

        $process = $this->isWindows()
            ? new Process($fullCommand, $workingDirectory, $this->getWindowsEnv())
            : Process::fromShellCommandline($fullCommand, $workingDirectory);

        $process->setTimeout($this->timeout);

        $this->chromiumResult = null;
        $process->run();

        $rawOutput = rtrim($process->getOutput());
        $this->chromiumResult = new ChromiumResult(json_decode($rawOutput, true));

        if ($process->isSuccessful()) {
            $result = $this->chromiumResult?->getResult();

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
            throw UnsuccessfulResponse::make($this->url, $errorOutput ?? '');
        }

        if ($exitCode === 2) {
            throw ElementNotFound::make($this->additionalOptions['selector']);
        }

        throw new ProcessFailedException($process);
    }
}
