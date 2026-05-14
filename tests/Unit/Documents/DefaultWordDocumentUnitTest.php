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
 */

namespace Tests\Unit\Documents;

use App\Documents\Word\DefaultWordDocument;
use Tests\TestCase;

class DefaultWordDocumentUnitTest extends TestCase
{
    public function testCreateDomDocumentFromHtmlPreservesUtf8SpecialCharacters(): void
    {
        $dom = DefaultWordDocument::createDomDocumentFromHtml('<p>Text — mit … „Sonderzeichen“ &amp; Akzenten é</p>');
        $body = $dom->getElementsByTagName('body')->item(0);

        $this->assertNotNull($body);
        $this->assertStringContainsString('—', $body->textContent);
        $this->assertStringContainsString('…', $body->textContent);
        $this->assertStringContainsString('„Sonderzeichen“', $body->textContent);
        $this->assertStringContainsString('é', $body->textContent);
    }
}
