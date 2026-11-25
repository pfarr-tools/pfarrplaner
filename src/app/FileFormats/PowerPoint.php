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

namespace App\FileFormats;

use DOMDocument;
use DOMElement;
use DOMXPath;

class PowerPoint extends AbstractZIPBasedFileFormat
{
    /** @var array<string, array{extension: string, contentType: string}> Image MIME types supported by PowerPoint. */
    protected const IMAGE_FORMATS = [
        'image/bmp' => ['extension' => 'bmp', 'contentType' => 'image/bmp'],
        'image/gif' => ['extension' => 'gif', 'contentType' => 'image/gif'],
        'image/jpeg' => ['extension' => 'jpg', 'contentType' => 'image/jpeg'],
        'image/png' => ['extension' => 'png', 'contentType' => 'image/png'],
        'image/svg+xml' => ['extension' => 'svg', 'contentType' => 'image/svg+xml'],
        'image/tiff' => ['extension' => 'tif', 'contentType' => 'image/tiff'],
        'image/x-ms-bmp' => ['extension' => 'bmp', 'contentType' => 'image/bmp'],
    ];

    protected const VBA_REL_TYPE = 'http://schemas.microsoft.com/office/2006/relationships/vbaProject';

    protected const CT_VBA_BIN = 'application/vnd.ms-office.vbaProject';

    protected const CT_PPTM_MAIN = 'application/vnd.ms-powerpoint.presentation.macroEnabled.main+xml';

    protected const CUSTOM_UI = '<customUI xmlns="http://schemas.microsoft.com/office/2006/01/customui" onLoad="StartLoopListener"><ribbon /></customUI>';

    /**
     * Patch the PPTM file to enable VBA macros and custom UI
     *
     * @param  string  $vbaProjectPath  Path to the VBA project file
     * @param  bool  $customUI  Add a custom UI to the presentation
     * @return void
     *
     * @throws \DOMException
     */
    public function patchPPTM($vbaProjectPath, $customUI = true)
    {
        $this->zip->open($this->documentFilePath);
        $this->addVBAProject($vbaProjectPath);
        if ($customUI) {
            $this->addCustomUI();
        }
        $this->zip->close();

    }

    /**
     * Add the VBA project to the presentation
     *
     * This will add a precompiled vbaProject.bin and register the necessary content types.
     *
     * @param  string  $vbaProjectPath  Path to the VBA project file
     * @return void
     */
    protected function addVBAProject($vbaProjectPath)
    {
        $vbaProject = file_get_contents($vbaProjectPath);
        if ($vbaProject === false) {
            throw new \RuntimeException("VBA-Projekt konnte nicht gelesen werden: $vbaProjectPath");
        }
        $this->zip->addFromString('ppt/vbaProject.bin', $vbaProject);
        $this->patchXMLFile('[Content_Types].xml', function (DOMDocument $doc) {
            $types = $doc->documentElement;
            if (! $types instanceof DOMElement) {
                return $doc;
            }
            $hasBinDefault = false;
            foreach ($types->getElementsByTagName('Default') as $def) {
                /** @var DOMElement $def */
                if (strtolower($def->getAttribute('Extension')) === 'bin') {
                    // wenn vorhanden, ggf. ContentType korrigieren
                    if ($def->getAttribute('ContentType') !== self::CT_VBA_BIN) {
                        $def->setAttribute('ContentType', self::CT_VBA_BIN);
                    }
                    $hasBinDefault = true;
                    break;
                }
            }

            if (! $hasBinDefault) {
                $newDef = $doc->createElement('Default');
                $newDef->setAttribute('Extension', 'bin');
                $newDef->setAttribute('ContentType', self::CT_VBA_BIN);

                // Einfügen „im Bereich <Types>“ – wir hängen ans Ende (robust)
                $types->appendChild($newDef);
            }

            // --- Fix: presentation.xml must be macro-enabled in a .pptm ---
            $hasPresentationOverride = false;
            foreach ($types->getElementsByTagName('Override') as $ovr) {
                /** @var DOMElement $ovr */
                if ($ovr->getAttribute('PartName') === '/ppt/presentation.xml') {
                    $ovr->setAttribute('ContentType', self::CT_PPTM_MAIN);
                    $hasPresentationOverride = true;
                    break;
                }
            }

            if (! $hasPresentationOverride) {
                $newOvr = $doc->createElement('Override');
                $newOvr->setAttribute('PartName', '/ppt/presentation.xml');
                $newOvr->setAttribute('ContentType', self::CT_PPTM_MAIN);
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

            if (! $hasCustomUIOverride) {
                $newOvr = $doc->createElement('Override');
                $newOvr->setAttribute('PartName', '/customUI/customUI.xml');
                $newOvr->setAttribute('ContentType', 'application/xml');
                $types->appendChild($newOvr);
            }

            return $doc;
        });

        $this->patchXMLFile('ppt/_rels/presentation.xml.rels', function (DOMDocument $doc) {
            $relsRoot = $doc->documentElement; // <Relationships ...>
            if (! $relsRoot instanceof DOMElement) {
                return $doc;
            }

            // Falls schon vorhanden: nichts doppelt anlegen
            $already = false;
            $maxId = 0;

            foreach ($relsRoot->getElementsByTagName('Relationship') as $rel) {
                /** @var DOMElement $rel */
                if ($rel->getAttribute('Type') === self::VBA_REL_TYPE && $rel->getAttribute('Target') === 'vbaProject.bin') {
                    $already = true;
                }
                $id = $rel->getAttribute('Id'); // rIdN
                if (preg_match('/^rId(\d+)$/', $id, $m)) {
                    $maxId = max($maxId, (int) $m[1]);
                }
            }

            if (! $already) {
                $newId = 'rId'.($maxId + 1);

                $newRel = $doc->createElement('Relationship');
                $newRel->setAttribute('Id', $newId);
                $newRel->setAttribute('Type', self::VBA_REL_TYPE);
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
     *
     * @return void
     *
     * @throws \DOMException
     */
    protected function addCustomUI()
    {
        $this->patchXMLFile('_rels/.rels', function (DOMDocument $doc) {
            $relsRoot = $doc->documentElement;
            if (! $relsRoot instanceof DOMElement) {
                return $doc;
            }
            $newDef = $doc->createElement('Relationship');
            $newDef->setAttribute('Id', 'rIdCustomUI');
            $newDef->setAttribute('Type', 'http://schemas.microsoft.com/office/2006/relationships/ui/extensibility');
            $newDef->setAttribute('Target', 'customUI/customUI.xml');
            $relsRoot->appendChild($newDef);

            return $doc;
        });

        $this->zip->addFromString('customUI/customUI.xml', self::CUSTOM_UI);
    }

    /**
     * Apply a workaround for SVG images in PowerPoint
     *
     * This is necessary because of a bug in the current version of PHPPresentation, where the SVG content type
     * is registered twice. This patch will remove the first of these registrations.
     *
     * @return void
     */
    public function applySVGFix()
    {
        $this->zip->open($this->documentFilePath);
        $this->patchXMLFile('[Content_Types].xml', function (DOMDocument $doc) {
            $types = $doc->documentElement;
            if (! $types instanceof DOMElement) {
                return $doc;
            }
            foreach ($types->getElementsByTagName('Default') as $type) {
                if (strtolower($type->getAttribute('Extension')) === 'svg') {
                    $type->parentNode?->removeChild($type);
                    break;
                }
            }

            return $doc;
        });
        $this->zip->close();
    }

    /**
     * Normalize embedded image names, relationships, and content types from each image's actual bytes.
     *
     * @throws \DOMException
     * @throws \RuntimeException When the presentation archive cannot be opened.
     */
    public function applyImageFileTypeFix(): void
    {
        if ($this->zip->open($this->documentFilePath) !== true) {
            throw new \RuntimeException('PowerPoint-Datei konnte nicht geöffnet werden.');
        }

        try {
            $imagePaths = [];
            for ($index = 0; $index < $this->zip->numFiles; $index++) {
                $path = $this->zip->getNameIndex($index);
                if (is_string($path) && preg_match('#^ppt/media/[^/]+$#', $path)) {
                    $imagePaths[] = $path;
                }
            }

            $renamedPaths = [];
            $imageFormats = [];
            foreach ($imagePaths as $path) {
                $contents = $this->zip->getFromName($path);
                if (! is_string($contents)) {
                    continue;
                }

                $format = $this->getImageFormat($contents);
                if ($format === null) {
                    if (pathinfo($path, PATHINFO_EXTENSION) === '') {
                        throw new \RuntimeException("Unbekanntes Bildformat in PowerPoint-Datei: $path");
                    }

                    continue;
                }

                $imageFormats[$path] = $format;
                $currentExtension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if ($currentExtension === $format['extension']) {
                    continue;
                }

                $basePath = preg_replace('/\.[^.\/]*$/', '', $path) ?: $path;
                $newPath = $basePath.'.'.$format['extension'];
                $suffix = 1;
                while ($this->zip->locateName($newPath) !== false) {
                    $newPath = $basePath.'-'.$suffix.'.'.$format['extension'];
                    $suffix++;
                }

                $this->zip->deleteName($path);
                $this->zip->addFromString($newPath, $contents);
                $renamedPaths[$path] = $newPath;
                unset($imageFormats[$path]);
                $imageFormats[$newPath] = $format;
            }

            $this->updateImageRelationships($renamedPaths);
            $this->updateImageContentTypes($renamedPaths, $imageFormats);
        } finally {
            $this->zip->close();
        }
    }

    /**
     * Detect a supported image format from the embedded file contents.
     *
     * @param  string  $contents  Raw image bytes.
     * @return array{extension: string, contentType: string}|null Detected format, or null for unknown data.
     */
    protected function getImageFormat(string $contents): ?array
    {
        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);
        if (! is_string($mimeType)) {
            return null;
        }

        return static::IMAGE_FORMATS[$mimeType] ?? null;
    }

    /**
     * Update package relationships that reference renamed embedded images.
     *
     * @param  array<string, string>  $renamedPaths  Old and new paths inside the presentation archive.
     *
     * @throws \DOMException
     */
    protected function updateImageRelationships(array $renamedPaths): void
    {
        if ($renamedPaths === []) {
            return;
        }

        $relationshipFiles = [];
        for ($index = 0; $index < $this->zip->numFiles; $index++) {
            $path = $this->zip->getNameIndex($index);
            if (is_string($path) && str_ends_with($path, '.rels')) {
                $relationshipFiles[] = $path;
            }
        }

        foreach ($relationshipFiles as $path) {
            $this->patchXMLFile($path, function (DOMDocument $doc) use ($renamedPaths) {
                foreach ($doc->getElementsByTagName('Relationship') as $relationship) {
                    if (! $relationship instanceof DOMElement || $relationship->getAttribute('TargetMode') === 'External') {
                        continue;
                    }

                    $target = $relationship->getAttribute('Target');
                    foreach ($renamedPaths as $oldPath => $newPath) {
                        if (basename($target) === basename($oldPath)) {
                            $relationship->setAttribute(
                                'Target',
                                substr($target, 0, -strlen(basename($oldPath))).basename($newPath)
                            );
                            break;
                        }
                    }
                }

                return $doc;
            });
        }
    }

    /**
     * Remove invalid empty defaults and register the content types of embedded images.
     *
     * @param  array<string, string>  $renamedPaths  Old and new paths inside the presentation archive.
     * @param  array<string, array{extension: string, contentType: string}>  $imageFormats  Detected formats by path.
     *
     * @throws \DOMException
     */
    protected function updateImageContentTypes(array $renamedPaths, array $imageFormats): void
    {
        $this->patchXMLFile('[Content_Types].xml', function (DOMDocument $doc) use ($renamedPaths, $imageFormats) {
            $types = $doc->documentElement;
            if (! $types instanceof DOMElement) {
                return $doc;
            }
            $requiredTypes = [];
            $emptyDefaults = [];

            foreach ($types->getElementsByTagName('Default') as $default) {
                if (! $default instanceof DOMElement) {
                    continue;
                }
                if ($default->getAttribute('Extension') === '') {
                    $emptyDefaults[] = $default;
                }
            }
            foreach ($emptyDefaults as $default) {
                $default->parentNode?->removeChild($default);
            }

            foreach ($types->getElementsByTagName('Override') as $override) {
                if (! $override instanceof DOMElement) {
                    continue;
                }
                $oldPath = ltrim($override->getAttribute('PartName'), '/');
                $newPath = $renamedPaths[$oldPath] ?? $oldPath;
                if (isset($imageFormats[$newPath])) {
                    if ($newPath !== $oldPath) {
                        $override->setAttribute('PartName', '/'.$newPath);
                    }
                    $override->setAttribute('ContentType', $imageFormats[$newPath]['contentType']);
                }
            }

            foreach ($imageFormats as $format) {
                $requiredTypes[$format['extension']] = $format['contentType'];
            }

            foreach ($requiredTypes as $extension => $contentType) {
                $matchedDefault = null;
                foreach ($types->getElementsByTagName('Default') as $default) {
                    if ($default instanceof DOMElement
                        && strtolower($default->getAttribute('Extension')) === $extension) {
                        $matchedDefault = $default;
                        break;
                    }
                }

                if ($matchedDefault instanceof DOMElement) {
                    $matchedDefault->setAttribute('ContentType', $contentType);

                    continue;
                }

                $default = $doc->createElement('Default');
                $default->setAttribute('Extension', $extension);
                $default->setAttribute('ContentType', $contentType);
                $types->appendChild($default);
            }

            return $doc;
        });
    }

    /**
     * Apply a workaround for slide names in PowerPoint
     *
     * This is necessary to fix a bug in the current version of PHPPresentation, where slide names are
     * not written to the presentation file.
     *
     * @param  array<array-key, string>  $slideNames  Slide names keyed by slide index
     * @return void
     */
    public function applySlideNameFix(array $slideNames)
    {
        $this->zip->open($this->documentFilePath);
        foreach ($slideNames as $slideIndex => $name) {
            $this->patchXMLFile('ppt/slides/slide'.$slideIndex.'.xml', function (DOMDocument $doc) use ($name) {
                $slide = $doc->documentElement;
                if (! $slide instanceof DOMElement) {
                    return $doc;
                }
                $cSld = $slide->getElementsByTagName('cSld')->item(0);
                if ($cSld instanceof DOMElement) {
                    $cSld->setAttribute('name', $name);
                }

                return $doc;
            });
        }
        $this->zip->close();
    }

    /**
     * Decode HTML entities in slide text runs so PowerPoint shows Unicode characters instead of entity strings.
     *
     * @throws \DOMException
     */
    public function applyTextEntityDecodingFix(): void
    {
        $this->zip->open($this->documentFilePath);
        $slidePaths = [];
        for ($index = 0; $index < $this->zip->numFiles; $index++) {
            $path = $this->zip->getNameIndex($index);
            if (is_string($path) && preg_match('#^ppt/slides/slide\d+\.xml$#', $path)) {
                $slidePaths[] = $path;
            }
        }

        foreach ($slidePaths as $path) {
            $this->patchXMLFile($path, function (DOMDocument $doc) {
                $xpath = new DOMXPath($doc);
                $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');

                $textNodes = $xpath->query('//a:t');
                if ($textNodes === false) {
                    return $doc;
                }
                foreach ($textNodes as $textNode) {
                    if (! $textNode instanceof DOMElement) {
                        continue;
                    }
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
