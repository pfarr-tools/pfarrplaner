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

namespace App\Liturgy\LiturgySheets;


use App\Documents\PDF;
use App\Models\Service;
use App\Services\FileNameService;
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
    protected $defaultConfig = [];
    protected $config = [];

    protected $privileged = false;

    public function __construct() {
        $this->config = $this->getConfiguration();
    }


    protected function getData(Service $service)
    {
        return [];
    }

    /**
     * @param Service $service
     */
    public function render(Service $service)
    {

        return PDF::fromView($this->getRenderViewName(),
                                            array_merge(compact('service'), $this->getData($service)))
            ->download($this->getFileName($service));
    }

    protected function getRenderViewName()
    {
        return 'liturgy.sheets.' . strtolower($this->getKey() . '.render');
    }

    public function getFileSignature(Service $service)
    {
        if ($service->funerals->count()) return '51.6';
        if ($service->weddings->count()) return '51.5';
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
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     */
    public function setIcon(string $icon): void
    {
        $this->icon = $icon;
    }

    /**
     * @return string[]
     */
    public function getLayout(): array
    {
        return $this->layout;
    }

    /**
     * @param string[] $layout
     */
    public function setLayout(array $layout): void
    {
        $this->layout = $layout;
    }

    /**
     * @return string
     */
    public function getFileTitle(): string
    {
        return $this->fileTitle;
    }

    /**
     * @param string $fileTitle
     */
    public function setFileTitle(string $fileTitle): void
    {
        $this->fileTitle = $fileTitle;
    }

    /**
     * @return string
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * @param string $extension
     */
    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    /**
     * @return bool
     */
    public function isNotAFile(): bool
    {
        return $this->isNotAFile;
    }

    /**
     * @param bool $isNotAFile
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
     * @param null $configurationPage
     */
    public function setConfigurationPage($configurationPage): void
    {
        $this->configurationPage = $configurationPage;
    }


    /**
     * Get a configuration array combined from defaults and user settings
     * @return array
     */
    public function getConfiguration()
    {
	    if (Auth::guest()) return $this->defaultConfig;
        return $this->config = array_replace_recursive(
            $this->defaultConfig,
            Auth::user()->getSetting('liturgySheetConfig_' . $this->getKey()) ?: []
        );
    }

    /**
     * Save configuration array to user settings, merging in any missing values from defaults
     * @param array $data
     */
    public function setConfiguration(array $data)
    {
        $this->config = array_replace_recursive($this->defaultConfig, $data);
	if (Auth::guest()) return;
        Auth::user()->setSetting(
            'liturgySheetConfig_' . $this->getKey(),
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
     * @param null $configurationComponent
     */
    public function setConfigurationComponent($configurationComponent): void
    {
        $this->configurationComponent = $configurationComponent;
    }

    public function isPrivileged(): bool
    {
        return $this->privileged;
    }

    public function setPrivileged(bool $privileged): void
    {
        $this->privileged = $privileged;
    }





}
