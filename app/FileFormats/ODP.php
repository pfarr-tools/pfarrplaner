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
use RuntimeException;
use \ZipArchive;

class ODP extends AbstractZIPBasedFileFormat
{


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
        return;
        $this->zip->open($this->documentFilePath);

        $this->patchXMLFile('content.xml', function (DOMDocument $doc) use ($slideNames) {
            $xp = new DOMXPath($doc);

            // Register ODF namespaces
            $xp->registerNamespace('office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');
            $xp->registerNamespace('draw', 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0');


            // Robust: unabhängig von Prefixen im Dokument
            $pages = $xp->query("//*[local-name()='presentation']/*[local-name()='page']");
            $p7 = $pages->item(7);
            $p7->setAttributeNS(
                'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0',
                'draw:name',
                'foo-bar-baz'
            );

            // Fallback, falls die Struktur anders ist
            if (!$pages || $pages->length === 0) {
                $pages = $xp->query(
                    "//*[local-name()='page' and namespace-uri()='urn:oasis:names:tc:opendocument:xmlns:drawing:1.0']"
                );
            }

            if (!$pages->count()) {
                return;
            }

            $count = $pages->count();

            foreach ($slideNames as $slideIndex => $name) {
                $slideIndex = (int)$slideIndex;
                if ($slideIndex < 0 || $slideIndex >= $count) {
                    continue;
                }

                $page = $pages->item($slideIndex);
                if (!$page) {
                    continue;
                }

                // set draw:name in correct namespace
                $page->setAttributeNS(
                    'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0',
                    'draw:name',
                    (string)$name
                );
            }
            return $doc;
        });
        $this->zip->close();
    }


    public function injectBasicMacroFromBas(
        string $basPath
    ): void {
        if (!is_file($basPath)) {
            throw new RuntimeException("BAS file not found: {$basPath}");
        }

        $basicCode = file_get_contents($basPath);
        if ($basicCode === false || trim($basicCode) === '') {
            throw new RuntimeException("BAS file empty/unreadable: {$basPath}");
        }
        // Convert to valid UTF-8 (ODF expects UTF-8)
        $enc = mb_detect_encoding($basicCode, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true) ?: 'Windows-1252';
        $basicCode = mb_convert_encoding($basicCode, 'UTF-8', $enc);
        // Remove any remaining invalid sequences (belt + suspenders)
        $basicCode = iconv('UTF-8', 'UTF-8//IGNORE', $basicCode);
        $basicCode = preg_replace('/^\xEF\xBB\xBF/', '', $basicCode);
        if (!mb_check_encoding($basicCode, 'UTF-8')) {
            throw new \RuntimeException("LoopListener.xml not valid UTF-8");
        }

        $moduleName = pathinfo($basPath, PATHINFO_FILENAME);
        $libraryName = 'Pfarrplaner';

        $this->zip->open($this->documentFilePath);

        // 1) Write XBA module
        $xbaPath = "Basic/{$libraryName}/{$moduleName}.xba";
        $this->zipWriteString($xbaPath, $this->buildXba($moduleName, $basicCode));

        // 2) Write library index (script.xlb)
        $xlbPath = "Basic/{$libraryName}/script-lb.xml";
        $this->zipWriteString($xlbPath, $this->buildScriptXlb($libraryName, [$moduleName]));

        // 3) Write libraries index (script.xlb)
        $xlcPath = "Basic/script-lc.xml";
        $this->zipWriteString($xlcPath, $this->buildScriptLc([$libraryName]));

        // 4) Ensure manifest contains entries for our new files
        $this->ensureManifestEntries([
                                         'Basic/' => 'application/vnd.sun.star.basic-library',
                                         'Basic/script-lc.xml' => 'application/vnd.sun.star.basic-library',
                                         "Basic/{$libraryName}/" => 'application/vnd.sun.star.basic-library',
                                         "Basic/{$libraryName}/script-lb.xml" => 'application/vnd.sun.star.basic-library',
                                         "Basic/{$libraryName}/{$moduleName}.xba" => 'application/vnd.sun.star.basic',
                                     ]);

        $this->zip->close();
        $this->registerContentXmlEvent('dom:load',
            'vnd.sun.star.script:Pfarrplaner.LoopListener.StartWatchdog?language=Basic&location=document');
    }

    private function buildXba(string $moduleName, string $basicCode): string
    {
        // Normalize line endings (LO is tolerant, but consistent is nice)
        $basicCode = str_replace(["\r\n", "\r"], "\n", $basicCode);

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;

        $SCRIPT_NS = 'http://openoffice.org/2000/script';

        // Optional, aber in echten .xba-Dateien üblich:
        $doctype = $doc->implementation->createDocumentType(
            'script:module',
            '-//OpenOffice.org//DTD OfficeDocument 1.0//EN',
            'module.dtd'
        );
        $doc->appendChild($doctype);

        // <script:module ...>
        $module = $doc->createElementNS($SCRIPT_NS, 'script:module');
        $doc->appendChild($module);

        // Namespaced attributes (WICHTIG)
        $module->setAttributeNS($SCRIPT_NS, 'script:name', $moduleName);
        $module->setAttributeNS($SCRIPT_NS, 'script:language', 'StarBasic');

        // Inhalt als Text (wie in echten .xba; kein CDATA nötig)
        $module->appendChild($doc->createTextNode("\n" . $basicCode . "\n"));

        return $doc->saveXML();
    }

    private function buildScriptXlb(string $libraryName, array $moduleNames): string
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;

        $LIB_NS = 'http://openoffice.org/2000/library';

        // <library:library ...>
        $lib = $doc->createElementNS($LIB_NS, 'library:library');
        $doc->appendChild($lib);

        // Attributes are in the same namespace (matches LO output style)
        $lib->setAttributeNS($LIB_NS, 'library:name', $libraryName);
        $lib->setAttributeNS($LIB_NS, 'library:readonly', 'false');
        $lib->setAttributeNS($LIB_NS, 'library:passwordprotected', 'false');

        // <library:element library:name="Module1"/>
        foreach ($moduleNames as $m) {
            $m = (string)$m;
            if ($m === '') {
                continue;
            }

            $el = $doc->createElementNS($LIB_NS, 'library:element');
            $el->setAttributeNS($LIB_NS, 'library:name', $m);
            $lib->appendChild($el);
        }

        return $doc->saveXML();
    }

    /**
     * @param array $libraryNames
     * @return string
     * @throws \DOMException
     */
    private function buildScriptLc(array $libraryNames): string
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;

        $LIB_NS = 'http://openoffice.org/2000/library';
        $XLINK = 'http://www.w3.org/1999/xlink';

        // Root is a "library container" index. LO uses library:libraries in many cases.
        $root = $doc->createElementNS($LIB_NS, 'library:libraries');
        $doc->appendChild($root);

        // Needed attributes commonly present
        $root->setAttributeNS($XLINK, 'xlink:type', 'simple');

        foreach ($libraryNames as $name) {
            $name = (string)$name;
            if ($name === '') {
                continue;
            }

            $entry = $doc->createElementNS($LIB_NS, 'library:library');
            $entry->setAttributeNS($LIB_NS, 'library:name', $name);
            $entry->setAttributeNS($LIB_NS, 'library:link', 'false');
            $entry->setAttributeNS($LIB_NS, 'library:readonly', 'false');
            $entry->setAttributeNS($LIB_NS, 'library:passwordprotected', 'false');

            $root->appendChild($entry);
        }

        return $doc->saveXML();
    }

    /**
     * Ensure META-INF/manifest.xml contains file-entry elements for given paths.
     *
     * @param array<string,string> $pathsToMediaTypes
     */
    private function ensureManifestEntries(array $pathsToMediaTypes): void
    {
        $this->patchXMLFile('META-INF/manifest.xml', function (DOMDocument $doc) use ($pathsToMediaTypes) {
            $xp = new DOMXPath($doc);
            $xp->registerNamespace('manifest', 'urn:oasis:names:tc:opendocument:xmlns:manifest:1.0');

            $root = $doc->documentElement;
            if (!$root) {
                throw new RuntimeException("Invalid manifest.xml (no root)");
            }

            // Collect existing full-path entries
            $existing = [];
            foreach ($xp->query('//manifest:file-entry') as $node) {
                /** @var \DOMElement $node */
                $fullPath = $node->getAttributeNS(
                    'urn:oasis:names:tc:opendocument:xmlns:manifest:1.0',
                    'full-path'
                );
                if ($fullPath !== '') {
                    $existing[$fullPath] = true;
                }
            }

            foreach ($pathsToMediaTypes as $fullPath => $mediaType) {
                if (isset($existing[$fullPath])) {
                    continue;
                }

                $entry = $doc->createElementNS(
                    'urn:oasis:names:tc:opendocument:xmlns:manifest:1.0',
                    'manifest:file-entry'
                );
                $entry->setAttributeNS(
                    'urn:oasis:names:tc:opendocument:xmlns:manifest:1.0',
                    'manifest:full-path',
                    $fullPath
                );
                $entry->setAttributeNS(
                    'urn:oasis:names:tc:opendocument:xmlns:manifest:1.0',
                    'manifest:media-type',
                    $mediaType
                );

                $root->appendChild($entry);
            }
        });
    }


    public function registerContentXmlEvent(string $odfEventName, string $macroUrl): void
    {
        $this->zip->open($this->documentFilePath);

        $this->patchXMLFile('content.xml', function(\DOMDocument $doc) use ($odfEventName, $macroUrl) {
            $OFFICE_NS = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
            $SCRIPT_NS = 'urn:oasis:names:tc:opendocument:xmlns:script:1.0';
            $XLINK_NS  = 'http://www.w3.org/1999/xlink';

            $xp = new \DOMXPath($doc);
            $xp->registerNamespace('office', $OFFICE_NS);
            $xp->registerNamespace('script', $SCRIPT_NS);
            $xp->registerNamespace('xlink',  $XLINK_NS);

            // Root <office:document-content>
            $root = $doc->documentElement;
            if (!$root || $root->namespaceURI !== $OFFICE_NS || $root->localName !== 'document-content') {
                throw new \RuntimeException('content.xml: unexpected root element');
            }

            // <office:scripts> (direct child of root)
            $scripts = $xp->query('/office:document-content/office:scripts')->item(0);
            if (!$scripts) {
                $scripts = $doc->createElementNS($OFFICE_NS, 'office:scripts');

                // Insert scripts early (before office:body if possible)
                $body = $xp->query('/office:document-content/office:body')->item(0);
                if ($body) {
                    $root->insertBefore($scripts, $body);
                } else {
                    $root->appendChild($scripts);
                }
            }

            // <office:event-listeners>
            $listeners = $xp->query('./office:event-listeners', $scripts)->item(0);
            if (!$listeners) {
                $listeners = $doc->createElementNS($OFFICE_NS, 'office:event-listeners');
                $scripts->appendChild($listeners);
            }

            // Find existing listener for this event name
            $query = './script:event-listener[@script:event-name="'.htmlspecialchars($odfEventName, ENT_QUOTES | ENT_XML1).'"]';
            $existing = $xp->query($query, $listeners)->item(0);

            if ($existing) {
                $listener = $existing;
            } else {
                $listener = $doc->createElementNS($SCRIPT_NS, 'script:event-listener');
                $listeners->appendChild($listener);
            }

            // Set attributes like in your known-good file
            $listener->setAttributeNS($SCRIPT_NS, 'script:language', 'ooo:script');
            $listener->setAttributeNS($SCRIPT_NS, 'script:event-name', $odfEventName);

            $listener->setAttributeNS($XLINK_NS, 'xlink:href', $macroUrl);
            $listener->setAttributeNS($XLINK_NS, 'xlink:type', 'simple');
        });

        $this->zip->close();
    }

    /**
     * Decode HTML entities in ODP body text so LibreOffice shows Unicode characters instead of entity strings.
     *
     * @return void
     */
    public function applyTextEntityDecodingFix(): void
    {
        $this->zip->open($this->documentFilePath);

        $this->patchXMLFile('content.xml', function (DOMDocument $doc) {
            $xpath = new DOMXPath($doc);
            $xpath->registerNamespace('office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');

            foreach ($xpath->query('/office:document-content/office:body//text()') as $textNode) {
                $decodedText = html_entity_decode($textNode->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if ($decodedText !== $textNode->textContent) {
                    $textNode->nodeValue = $decodedText;
                }
            }
        });

        $this->zip->close();
    }

}
