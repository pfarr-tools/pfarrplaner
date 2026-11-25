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

namespace App\Reports;

use App\Documents\PDF;
use Illuminate\Support\Facades\View;

/**
 * Class AbstractPDFDocumentReport
 */
class AbstractPDFDocumentReport extends AbstractReport
{
    /** @var bool */
    protected $landscape = false;

    /** @var string */
    protected $header = '';

    /** @var string */
    protected $footer = '';

    /**
     * @var string
     */
    public $icon = 'fa fa-file-pdf';

    /**
     * @param  string  $filename  Name of the downloaded file.
     * @param  array<string, mixed>  $data  Variables passed to the render view.
     * @param  mixed  $layout  Optional layout configuration; currently unused.
     * @return mixed
     */
    public function sendToBrowser(string $filename, array $data, mixed $layout)
    {
        return $this->sendToFile($filename, $data, $layout);
    }

    /**
     * @param  array<string, mixed>  $data  Variables passed to the render view.
     * @return string
     */
    public function renderPDF(array $data)
    {
        return View::file(View::getFinder()->find($this->getRenderViewName()), $data)->render();
    }

    /**
     * @param  string  $filename  Name of the downloaded file.
     * @param  array<string, mixed>  $data  Variables passed to the render view.
     * @param  mixed  $layout  Optional layout configuration; currently unused.
     * @return mixed
     */
    public function sendToFile(string $filename, array $data, mixed $layout)
    {
        return PDF::fromView($this->getRenderViewName(), $data, $this->header, $this->footer)->download($filename);
    }

    public function isLandscape(): bool
    {
        return $this->landscape;
    }

    /**
     * @param  bool  $landscape  Landscape flag.
     */
    public function setLandscape(bool $landscape): void
    {
        $this->landscape = $landscape;
    }

    public function getHeader(): string
    {
        return $this->header;
    }

    /**
     * @param  string  $header  Header text.
     */
    public function setHeader(string $header): void
    {
        $this->header = $header;
    }

    public function getFooter(): string
    {
        return $this->footer;
    }

    /**
     * @param  string  $footer  Footer text.
     */
    public function setFooter(string $footer): void
    {
        $this->footer = $footer;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * @param  string  $icon  Icon name.
     */
    public function setIcon(string $icon): void
    {
        $this->icon = $icon;
    }
}
