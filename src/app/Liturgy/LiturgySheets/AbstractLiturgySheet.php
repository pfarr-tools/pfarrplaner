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

namespace App\Liturgy\LiturgySheets;

use App\Documents\PDF;
use App\Models\Service;
use App\Services\FileNameService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AbstractLiturgySheet
{
    protected $title = '';

    protected $icon = 'fa fa-file';

    protected $layout = ['format' => 'A4'];

    protected $fileTitle = 'Liturgie';

    protected $extension = 'pdf';

    protected $isNotAFile = false;

    protected $configurationPage = null;

    protected $configurationComponent = null;

    protected $configurationCloseOnly = false;

    protected $defaultConfig = [];

    protected $config = [];

    protected $privileged = false;

    public function __construct()
    {
        $this->config = $this->getConfiguration();
    }

    protected function getData(Service $service)
    {
        return [];
    }

    /**
     * Render the sheet as a downloadable PDF for the given service.
     *
     * @param  Service  $service  Service whose liturgy is exported.
     * @return mixed Download response produced by Browsershot.
     */
    public function render(Service $service)
    {

        $footer = '<div style="font-size:8px; width:100%; text-align:left; padding: 0 15mm">'
            .Carbon::now()->setTimezone('Europe/Berlin')->format('d.m.Y H:i:s')
            .' – Seite <span class="pageNumber"></span> / <span class="totalPages"></span></div>';

        $pdf = PDF::fromView($this->getRenderViewName(),
            array_merge(compact('service'), $this->getData($service)))
            ->format($this->layout['format'])
            ->margins(15, 15, 15, 15)
            ->showBrowserHeaderAndFooter()
            ->hideHeader()
            ->footerHtml($this->getFooter());

        if ($this->layout['landscape'] ?? false) {
            $pdf->landscape();
        }

        return $pdf->download($this->getFileName($service));
    }

    /**
     * Provide additional data for configuration dialogs that do not submit a download form.
     */
    public function getDialogData(Service $service): array
    {
        return [];
    }

    /**
     * Return the footer content for the generated document.
     *
     * @return mixed Footer content.
     */
    protected function getFooter()
    {
        return '<div style="width: 100%; font-size: 8pt; font-family: \'Sarabun Light\', sans-serif; display: flex; justify-content: space-between; align-items: center; padding: 0 15mm;">'
              .'<div style="text-align: left;">Stand: '.now()->setTimezone('Europe/Berlin')->format('d.m.Y, H:i').' Uhr</div>'
              .'<div style="text-align: center;">'.(Auth::guest() ? '' : Auth::user()->fullName()).'</div>'
              .'<div style="text-align: right;">Seite <span class="pageNumber"></span> / <span class="totalPages"></span></div>'
              .'</div>';
    }

    /**
     * Return the view name used for rendering.
     *
     * @return string Render view name.
     */
    protected function getRenderViewName()
    {
        return 'liturgy.sheets.'.strtolower($this->getKey().'.render');
    }

    /**
     * Build a signature identifying the generated file for a service.
     *
     * @param  Service  $service  Service being rendered.
     * @return string File signature.
     */
    public function getFileSignature(Service $service)
    {
        if ($service->funerals->count()) {
            return '51.6';
        }
        if ($service->weddings->count()) {
            return '51.5';
        }

        return '50.0';
    }

    public function getFileName(Service $service, $title = null)
    {
        return FileNameService::make(
            ($title ?? $this->getFileTitle()),
            ($this->extension ? '.'.$this->extension : ''),
            $this->getFileSignature($service),
            $service->date,
            true
        );
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return strtr(
            get_called_class(),
            [
                'App\\Liturgy\\LiturgySheets\\' => '',
                'LiturgySheet' => '',
            ]
        );
    }

    /**
     * Return the sheet title.
     *
     * @return string Sheet title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set the sheet title.
     *
     * @param  string  $title  New sheet title.
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Return the sheet icon identifier.
     *
     * @return string Icon identifier.
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * Set the sheet icon identifier.
     *
     * @param  string  $icon  New icon identifier.
     */
    public function setIcon(string $icon): void
    {
        $this->icon = $icon;
    }

    /**
     * Return the PDF layout configuration used by the sheet renderer.
     *
     * @return array{format: string, landscape?: bool}
     */
    /**
     * Return the sheet layout configuration.
     *
     * @return array<string, mixed> Layout configuration.
     */
    public function getLayout(): array
    {
        return $this->layout;
    }

    /**
     * Set the PDF layout configuration used by the sheet renderer.
     *
     * @param  array{format: string, landscape?: bool}  $layout
     */
    public function setLayout(array $layout): void
    {
        $this->layout = $layout;
    }

    /**
     * Return the generated file title.
     *
     * @return string File title.
     */
    public function getFileTitle(): string
    {
        return $this->fileTitle;
    }

    /**
     * Set the generated file title.
     *
     * @param  string  $fileTitle  New file title.
     */
    public function setFileTitle(string $fileTitle): void
    {
        $this->fileTitle = $fileTitle;
    }

    /**
     * Return the generated file extension.
     *
     * @return string File extension.
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * Set the generated file extension.
     *
     * @param  string  $extension  New file extension.
     */
    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    /**
     * Determine whether the sheet produces no file.
     *
     * @return bool Whether the sheet is not file-based.
     */
    public function isNotAFile(): bool
    {
        return $this->isNotAFile;
    }

    /**
     * Set whether the sheet produces no file.
     *
     * @param  bool  $isNotAFile  New file-generation flag.
     */
    public function setIsNotAFile(bool $isNotAFile): void
    {
        $this->isNotAFile = $isNotAFile;
    }

    /**
     * @return null
     */
    public function getConfigurationPage()
    {
        return $this->configurationPage;
    }

    /**
     * @param  null  $configurationPage
     */
    public function setConfigurationPage($configurationPage): void
    {
        $this->configurationPage = $configurationPage;
    }

    /**
     * Get a configuration array combined from defaults and user settings
     *
     * @return array
     */
    public function getConfiguration()
    {
        if (Auth::guest()) {
            return $this->defaultConfig;
        }

        return $this->config = array_replace_recursive(
            $this->defaultConfig,
            Auth::user()->getSetting('liturgySheetConfig_'.$this->getKey()) ?: []
        );
    }

    /**
     * Save configuration array to user settings, merging in any missing values from defaults
     */
    public function setConfiguration(array $data)
    {
        $this->config = array_replace_recursive($this->defaultConfig, $data);
        if (Auth::guest()) {
            return;
        }
        Auth::user()->setSetting(
            'liturgySheetConfig_'.$this->getKey(),
            $this->config,
        );
    }

    /**
     * @return null
     */
    public function getConfigurationComponent()
    {
        return $this->configurationComponent;
    }

    /**
     * @param  null  $configurationComponent
     */
    public function setConfigurationComponent($configurationComponent): void
    {
        $this->configurationComponent = $configurationComponent;
    }

    /**
     * Determine whether configuration closes after one interaction.
     *
     * @return bool Whether configuration is close-only.
     */
    public function isConfigurationCloseOnly(): bool
    {
        return $this->configurationCloseOnly;
    }

    /**
     * Set whether configuration closes after one interaction.
     *
     * @param  bool  $configurationCloseOnly  New close-only flag.
     */
    public function setConfigurationCloseOnly(bool $configurationCloseOnly): void
    {
        $this->configurationCloseOnly = $configurationCloseOnly;
    }

    /**
     * Determine whether the sheet is restricted to privileged users.
     *
     * @return bool Whether privileged access is required.
     */
    public function isPrivileged(): bool
    {
        return $this->privileged;
    }

    /**
     * Set whether the sheet is restricted to privileged users.
     *
     * @param  bool  $privileged  New privilege flag.
     */
    public function setPrivileged(bool $privileged): void
    {
        $this->privileged = $privileged;
    }
}
