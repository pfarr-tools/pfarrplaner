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

use App\Documents\Word\DefaultWordDocument;
use PhpOffice\PhpWord\Exception\Exception;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

/**
 * Class AbstractWordDocumentReport
 * @package App\Reports
 */
class AbstractWordDocumentReport extends AbstractReport
{
    /**
     * @var string
     */
    public $icon = 'fa fa-file-word';

    /** @var PhpWord $wordDocument */
    protected $wordDocument = null;

    // transition: use both PhpWord and DefaultWordDocument

    /** @var DefaultWordDocument */
    protected $doc;
    protected $useDefaultDocument = false;

    /** @var array  */
    protected $config = [];

    public function __construct()
    {
        $this->wordDocument = new PhpWord();
        $this->prepareWordDocument();
    }

    /**
     * Prepare the DefaultWordDocument
     */
    protected function prepareWordDocument()
    {
        $this->doc = new DefaultWordDocument($this->config);
    }

    /**
     * @param $filename
     * @throws Exception
     */
    public function sendToBrowser($filename)
    {
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $objWriter = IOFactory::createWriter($this->useDefaultDocument ? $this->doc->getPhpWord() : $this->wordDocument, 'Word2007');
        $objWriter->save($tempFile);
        return response()->download($tempFile, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
            ->deleteFileAfterSend(true);
    }
}
