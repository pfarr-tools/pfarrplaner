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

namespace App\Reports;

use App\Documents\PDF;
use Illuminate\Support\Facades\View;

/**
 * Class AbstractPDFDocumentReport
 * @package App\Reports
 */
class AbstractPDFDocumentReport extends AbstractReport
{

    /** @var bool  */
    protected $landscape = false;

    /** @var string  */
    protected $header = '';

    /** @var string  */
    protected $footer = '';

    /**
     * @var string
     */
    public $icon = 'fa fa-file-pdf';

    /**
     * @param $filename
     * @param $data
     * @param $layout
     * @return mixed
     */
    public function sendToBrowser($filename, $data, $layout)
    {
        return $this->sendToFile($filename, $data, $layout);
    }

    /**
     * @param $data
     * @return string
     */
    public function renderPDF($data)
    {
        return View::make($this->getRenderViewName(), $data)->render();
    }

    /**
     * @param $filename
     * @param $data
     * @param $layout
     * @return mixed
     */
    public function sendToFile($filename, $data, $layout)
    {
        return PDF::fromView($this->getRenderViewName(), $data, $this->header, $this->footer)->download($filename);
    }

    public function isLandscape(): bool
    {
        return $this->landscape;
    }

    public function setLandscape(bool $landscape): void
    {
        $this->landscape = $landscape;
    }

    public function getHeader(): string
    {
        return $this->header;
    }

    public function setHeader(string $header): void
    {
        $this->header = $header;
    }

    public function getFooter(): string
    {
        return $this->footer;
    }

    public function setFooter(string $footer): void
    {
        $this->footer = $footer;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): void
    {
        $this->icon = $icon;
    }



}
