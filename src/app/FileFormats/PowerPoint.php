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

use Closure;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PhpOffice\PhpPresentation\Shape\Drawing\ZipFile;
use \ZipArchive;

class PowerPoint extends AbstractZIPBasedFileFormat
{

    protected const VBA_REL_TYPE = 'http://schemas.microsoft.com/office/2006/relationships/vbaProject';
    protected const CT_VBA_BIN   = 'application/vnd.ms-office.vbaProject';
    protected const CT_PPTM_MAIN = 'application/vnd.ms-powerpoint.presentation.macroEnabled.main+xml';
    protected const CUSTOM_UI = '<customUI xmlns="http://schemas.microsoft.com/office/2006/01/customui" onLoad="StartLoopListener"><ribbon /></customUI>';


    /**
     * Patch the PPTM file to enable VBA macros and custom UI
     * @param string $vbaProjectPath Path to the VBA project file
     * @param bool $customUI Add a custom UI to the presentation
     * @return void
     * @throws \DOMException
     */
    public function patchPPTM($vbaProjectPath, $customUI = true) {
        $this->zip->open($this->documentFilePath);
        $this->addVBAProject($vbaProjectPath);
        if ($customUI) $this->addCustomUI();
        $this->zip->close();

    }

    /**
     * Add the VBA project to the presentation
     *
     * This will add a precompiled vbaProject.bin and register the necessary content types.
     * @param string $vbaProjectPath Path to the VBA project file
     * @return void
     */
    protected function addVBAProject($vbaProjectPath) {
        $this->zip->addFromString('ppt/vbaProject.bin', file_get_contents($vbaProjectPath));
        $this->patchXMLFile('[Content_Types].xml', function(DOMDocument $doc) {
            $types = $doc->documentElement;
            $hasBinDefault = false;
            foreach ($types->getElementsByTagName('Default') as $def) {
                /** @var DOMElement $def */
                if (strtolower($def->getAttribute('Extension')) === 'bin') {
                    // wenn vorhanden, ggf. ContentType korrigieren
                    if ($def->getAttribute('ContentType') !== static::CT_VBA_BIN) {
                        $def->setAttribute('ContentType', static::CT_VBA_BIN);
                    }
                    $hasBinDefault = true;
                    break;
                }
            }

            if (!$hasBinDefault) {
                $newDef = $doc->createElement('Default');
                $newDef->setAttribute('Extension', 'bin');
                $newDef->setAttribute('ContentType', static::CT_VBA_BIN);

                // Einfügen „im Bereich <Types>“ – wir hängen ans Ende (robust)
                $types->appendChild($newDef);
            }

            // --- Fix: presentation.xml must be macro-enabled in a .pptm ---
            $hasPresentationOverride = false;
            foreach ($types->getElementsByTagName('Override') as $ovr) {
                /** @var DOMElement $ovr */
                if ($ovr->getAttribute('PartName') === '/ppt/presentation.xml') {
                    $ovr->setAttribute('ContentType', static::CT_PPTM_MAIN);
                    $hasPresentationOverride = true;
                    break;
                }
            }

            if (!$hasPresentationOverride) {
                $newOvr = $doc->createElement('Override');
                $newOvr->setAttribute('PartName', '/ppt/presentation.xml');
                $newOvr->setAttribute('ContentType', static::CT_PPTM_MAIN);
                $types->appendChild($newOvr);
            }

            // --- Fix: presentation.xml must be macro-enabled in a .pptm ---
            $hasCustomUIOverride = false;
            foreach ($types->getElementsByTagName('Override') as $ovr) {
                /** @var DOMElement $ovr */
                if ($ovr->getAttribute('PartName') === '/customUI/customUI.xml') {
                    $ovr->setAttribute('ContentType', 'application/xml');
                    $hasCustomUIOverride = true;
                    break;
                }
            }

            if (!$hasCustomUIOverride) {
                $newOvr = $doc->createElement('Override');
                $newOvr->setAttribute('PartName', '/customUI/customUI.xml');
                $newOvr->setAttribute('ContentType', 'application/xml');
                $types->appendChild($newOvr);
            }
            return $doc;
        });

        $this->patchXMLFile('ppt/_rels/presentation.xml.rels', function(DOMDocument $doc) {
            $relsRoot = $doc->documentElement; // <Relationships ...>

            // Falls schon vorhanden: nichts doppelt anlegen
            $already = false;
            $maxId = 0;

            foreach ($relsRoot->getElementsByTagName('Relationship') as $rel) {
                /** @var DOMElement $rel */
                if ($rel->getAttribute('Type') === static::VBA_REL_TYPE && $rel->getAttribute('Target') === 'vbaProject.bin') {
                    $already = true;
                }
                $id = $rel->getAttribute('Id'); // rIdN
                if (preg_match('/^rId(\d+)$/', $id, $m)) {
                    $maxId = max($maxId, (int)$m[1]);
                }
            }

            if (!$already) {
                $newId = 'rId' . ($maxId + 1);

                $newRel = $doc->createElement('Relationship');
                $newRel->setAttribute('Id', $newId);
                $newRel->setAttribute('Type', static::VBA_REL_TYPE);
                $newRel->setAttribute('Target', 'vbaProject.bin');

                $relsRoot->appendChild($newRel);
            }
            return $doc;
        });
    }

    /**
     * Add a custom UI to the presentation
     *
     * Add a custom UI with an onLoad event to launch the initial listeners. This is necessary because PowerPoint does
     * not support auto_open() in .pptm files
     * @return void
     * @throws \DOMException
     */
    protected function addCustomUI()
    {
        $this->patchXMLFile('_rels/.rels', function(DOMDocument $doc) {
            $relsRoot = $doc->documentElement;
            $newDef = $doc->createElement('Relationship');
            $newDef->setAttribute('Id', 'rIdCustomUI');
            $newDef->setAttribute('Type', 'http://schemas.microsoft.com/office/2006/relationships/ui/extensibility');
            $newDef->setAttribute('Target', 'customUI/customUI.xml');
            $relsRoot->appendChild($newDef);
            return $doc;
        });

        $this->zip->addFromString('customUI/customUI.xml', static::CUSTOM_UI);
    }

    /**
     * Apply a workaround for SVG images in PowerPoint
     *
     * This is necessary because of a bug in the current version of PHPPresentation, where the SVG content type
     * is registered twice. This patch will remove the first of these registrations.
     * @return void
     */
    public function applySVGFix()
    {
        $this->zip->open($this->documentFilePath);
        $this->patchXMLFile('[Content_Types].xml', function(DOMDocument $doc) {
            $types = $doc->documentElement;
            foreach ($types->getElementsByTagName('Default') as $type) {
                if (strtolower($type->getAttribute('Extension')) === 'svg') {
                    $type->parentNode->removeChild($type);
                    break;
                }
            }
            return $doc;
        });
        $this->zip->close();
    }


    /**
     * Apply a workaround for slide names in PowerPoint
     *
     * This is necessary to fix a bug in the current version of PHPPresentation, where slide names are
     * not written to the presentation file.
     * @param array $slideNames
     * @return void
     */
    public function applySlideNameFix(array $slideNames)
    {
        $this->zip->open($this->documentFilePath);
        foreach ($slideNames as $slideIndex => $name) {
            $this->patchXMLFile('ppt/slides/slide' . $slideIndex . '.xml', function(DOMDocument $doc) use ($name) {
                $slide = $doc->documentElement;
                $slide->getElementsByTagName('cSld')->item(0)->setAttribute('name', $name);
                return $doc;
            });
        }
        $this->zip->close();
    }

    /**
     * Decode HTML entities in slide text runs so PowerPoint shows Unicode characters instead of entity strings.
     *
     * @return void
     * @throws \DOMException
     */
    public function applyTextEntityDecodingFix(): void
    {
        $this->zip->open($this->documentFilePath);
        $slidePaths = [];
        for ($index = 0; $index < $this->zip->numFiles; $index++) {
            $path = $this->zip->getNameIndex($index);
            if (preg_match('#^ppt/slides/slide\d+\.xml$#', $path)) {
                $slidePaths[] = $path;
            }
        }

        foreach ($slidePaths as $path) {
            $this->patchXMLFile($path, function (DOMDocument $doc) {
                $xpath = new DOMXPath($doc);
                $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');

                foreach ($xpath->query('//a:t') as $textNode) {
                    $decodedText = html_entity_decode($textNode->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    if ($decodedText !== $textNode->textContent) {
                        $textNode->nodeValue = $decodedText;
                    }
                }

                return $doc;
            });
        }
        $this->zip->close();
    }

}
