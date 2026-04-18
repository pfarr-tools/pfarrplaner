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

namespace App\FileFormats;

use \Closure;
use \ZipArchive;
use \DOMDocument;

class AbstractZIPBasedFileFormat
{

    /** @var ZipArchive $zip Output file stream  */
    protected $zip = null;


    public function __construct()
    {
        $this->zip = new ZipArchive();
    }

    /**
     * Patch an XML file in the ZIP archive
     * @param string $path Path to the XML file relative to the root of the ZIP archive
     * @param Closure $callback This will be called with the DOMDocument object of the XML file
     * @return void
     */
    protected function patchXMLFile(string $path, Closure $callback) {
        $xml = $this->zip->getFromName($path);
        if (empty($xml)) throw new \RuntimeException("Empty XML passed for file $path");
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->loadXML($xml);
        $callback($doc);
        if ($this->zip->locateName($path) !== false) {
            $this->zip->deleteName($path);
        }
        $this->zip->addFromString($path, $doc->saveXML());
    }

    protected $documentFilePath = '';

    /**
     * @param $documentFilePath
     * @return static
     */
    public static function fromFile($documentFilePath) {
        $instance = new static();
        $instance->setDocumentFilePath($documentFilePath);
        return $instance;
    }

    /**
     * Set the path to the PPTX file
     * @param string $documentFilePath
     * @return void
     */
    public function setDocumentFilePath(string $documentFilePath): void
    {
        $this->documentFilePath = $documentFilePath;
    }

    /**
     * Create a file in the ZIP archive from a string, deleting old versions of the file if necessary
     * @param string $path
     * @param string $content
     * @return void
     */
    protected function zipWriteString(string $path, string $content): void
    {
        if ($this->zip->locateName($path) !== false) {
            $this->zip->deleteName($path);
        }
        $this->zip->addFromString($path, $content);
    }

    /**
     * Appends CDATA safely, splitting any occurrences of "]]>" into multiple CDATA sections.
     */
    protected function appendSafeCdata(\DOMDocument $doc, \DOMNode $parent, string $text): void
    {
        // If there's no CDATA terminator inside, simple:
        if (strpos($text, ']]>') === false) {
            $parent->appendChild($doc->createCDATASection("\n" . $text . "\n"));
            return;
        }

        // Split to avoid illegal CDATA termination
        $parts = explode(']]>', $text);

        // Leading newline for nicer diffs / similar to LO output
        $parent->appendChild($doc->createTextNode("\n"));

        $lastIdx = count($parts) - 1;
        foreach ($parts as $i => $part) {
            if ($part !== '') {
                $parent->appendChild($doc->createCDATASection($part));
            }
            // Re-insert the literal "]]>" between CDATA sections (as text), except after last part
            if ($i !== $lastIdx) {
                $parent->appendChild($doc->createTextNode(']]>'));
            }
        }

        $parent->appendChild($doc->createTextNode("\n"));
    }


}
