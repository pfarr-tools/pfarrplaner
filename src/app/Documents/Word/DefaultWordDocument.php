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

namespace App\Documents\Word;

use App\Models\Service;
use DOMDocument;
use DOMNode;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Exception\Exception;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\Style\Language;
use PhpOffice\PhpWord\Style\Tab;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DefaultWordDocument
{
    /** @var PhpWord Word document initialized by the constructor. */
    protected PhpWord $phpWord;

    /** @var Section Document section initialized by configureLayout(). */
    protected Section $section;

    /** @var array<string, mixed> */
    protected $instructionsFontStyle = ['size' => 8, 'italic' => true];

    /** @var array<string, mixed> */
    protected $instructionsParagraphStyle = [];

    /** @var string|null */
    protected $recipient = null;

    public const NORMAL = 'Standard';

    public const NO_INDENT = 'Bekanntgaben ohne Einrückung';

    public const BLOCKQUOTE = 'Zitat';

    public const INSTRUCTIONS = 'Standard mit Anweisungen';

    public const BOLD = ['bold' => true];

    public const UNDERLINE = ['underline' => Font::UNDERLINE_SINGLE];

    public const BOLD_UNDERLINE = ['bold' => true, 'underline' => Font::UNDERLINE_SINGLE];

    /** @var array<string, mixed> */
    protected $config = [];

    /**
     * @param  array<string, mixed>  $config  Document configuration.
     */
    public function __construct($config = [])
    {

        $this->config = array_replace_recursive($this->getBaseConfig(), $this->config, $config);
        Settings::setOutputEscapingEnabled(true);
        $this->phpWord = new PhpWord;
        $this->phpWord->getSettings()->setThemeFontLang(new Language(Language::DE_DE));
        $this->configureLayout($config['layout'] ?? []);
        $this->setDefaultDocumentStyles($config);
    }

    /**
     * Get the base config array.
     *
     * This will expand all ['tabs'] configs to PhpWord's Tab objects.
     */
    /**
     * @return array<string, mixed>
     */
    public function getBaseConfig(): array
    {
        $baseConfig = config('documents.word.default');
        foreach ($baseConfig['styles']['paragraphs'] as $pKey => $pStyle) {
            if (isset($pStyle['tabs'])) {
                $tabs = [];
                foreach ($pStyle['tabs'] as $tab) {
                    $tabs[] = new Tab($tab['type'], $tab['position']);
                }
                $baseConfig['styles']['paragraphs'][$pKey]['tabs'] = $tabs;
            }
        }
        foreach (['titles', 'custom'] as $sKey) {
            foreach ($baseConfig['styles']['paragraphs'][$sKey] as $pKey => $pStyle) {
                if (isset($pStyle['tabs'])) {
                    $tabs = [];
                    foreach ($pStyle['tabs'] as $tab) {
                        $tabs[] = new Tab($tab['type'], $tab['position']);
                    }
                    $baseConfig['styles']['paragraphs'][$sKey][$pKey]['tabs'] = $tabs;
                }
            }
        }

        return $baseConfig;
    }

    /**
     * @param  array<string, mixed>  $config  Layout configuration.
     * @return void
     */
    protected function configureLayout($config)
    {
        $this->section = $this->phpWord->addSection($config);
    }

    /**
     * @param  array<string, mixed>  $config  Style configuration.
     * @return void
     */
    protected function setDefaultDocumentStyles($config = [])
    {
        $phpWord = $this->phpWord;
        $phpWord->setDefaultFontName($config['defaultFont'] ?? 'Sarabun Light');
        $phpWord->setDefaultFontSize($config['defaultFontSize'] ?? 11);

        // Standard
        $phpWord->setDefaultParagraphStyle($this->config['styles']['paragraphs']['default'] ?? []);
        $phpWord->addFontStyle(self::NORMAL, $this->config['styles']['fonts'][self::NORMAL]);

        // title styles
        foreach ($this->config['styles']['paragraphs']['titles'] as $level => $pStyle) {
            $phpWord->addTitleStyle($level, $this->config['styles']['fonts']['titles'][$level] ?? [], $pStyle);
        }

        foreach ($this->config['styles']['paragraphs']['custom'] as $pKey => $pStyle) {
            $phpWord->addParagraphStyle($pKey, $pStyle);
        }

        foreach ($this->config['styles']['fonts']['custom'] as $fKey => $fStyle) {
            $phpWord->addFontStyle($fKey, $fStyle);
        }

    }

    // SETTERS

    public function getPhpWord(): PhpWord
    {
        return $this->phpWord;
    }

    public function setPhpWord(PhpWord $phpWord): void
    {
        $this->phpWord = $phpWord;
    }

    public function getSection(): Section
    {
        return $this->section;
    }

    public function setSection(Section $section): void
    {
        $this->section = $section;
    }

    /**
     * Render a styled paragraph block.
     *
     * @param  string  $template  Paragraph style name.
     * @param  array<int, mixed>  $blocks  Paragraph text blocks.
     * @param  int  $emptyParagraphsAfter  Number of empty paragraphs after this block.
     * @param  TextRun|null  $existingTextRun  Existing text run to extend.
     * @return TextRun|null
     */
    public function renderParagraph(
        $template = '',
        array $blocks = [],
        $emptyParagraphsAfter = 0,
        $existingTextRun = null
    ) {
        $textRun = $existingTextRun ?: $this->section->addTextRun($template);
        foreach ($blocks as $block) {
            if ($block[0] !== null) {
                $ct = 0;
                foreach (explode("\n", $block[0]) as $item) {
                    if ($ct > 0) {
                        $textRun->addTextBreak();
                    }
                    $textRun->addText($item, $block[1] ?? []);
                    $ct++;
                }
                if (isset($block[2]) && $block[2]) {
                    $textRun->addTextBreak();
                }
            }
        }
        for ($i = 0; $i < $emptyParagraphsAfter; $i++) {
            $textRun = $this->section->addTextRun($template);
        }

        return $textRun;
    }

    /**
     * @throws Exception
     */
    /**
     * @param  string  $filename  Download file name.
     * @return BinaryFileResponse
     */
    public function sendToBrowser($filename)
    {
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $phpWord = $this->phpWord;
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);

        return response()->download($tempFile, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
            ->deleteFileAfterSend(true);
    }

    /**
     * Create a DOM document from HTML while preserving UTF-8 characters for Word exports.
     */
    public static function createDomDocumentFromHtml(string $html): DOMDocument
    {
        $dom = new DOMDocument;
        $dom->loadHTML(
            mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'),
            LIBXML_NOERROR | LIBXML_NOWARNING
        );

        return $dom;
    }

    /**
     * Convert a DOM node to plain text while preserving paragraph and line breaks.
     */
    public static function getTextWithBreaksFromDomNode(DOMNode $node): string
    {
        $text = self::extractTextWithBreaksFromDomNode($node);
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * Render some text with default formatting
     *
     * @param  $text  Text
     * @param  array  $fontOption  Font options
     * @param  false  $breakAfter  Text break at the end?
     */
    /**
     * @param  string  $text  Text to render.
     * @param  array<string, mixed>  $fontOption  Font options.
     * @param  bool  $breakAfter  Whether a break follows the text.
     * @return void
     */
    public function renderNormalText($text, $fontOption = [], $breakAfter = false)
    {
        $this->renderText($text, self::NORMAL, $fontOption, $breakAfter);
    }

    /**
     * Render some text with specific paragraph style
     *
     * @param  $text  Text
     * @param  array|string  $paragraphOption  Font options
     * @param  array  $fontOption  Font options
     * @param  false  $breakAfter  Text break at the end?
     */
    /**
     * @param  string  $text  Text to render.
     * @param  array<string, mixed>|string  $paragraphOption  Paragraph style.
     * @param  array<string, mixed>  $fontOption  Font options.
     * @param  bool  $breakAfter  Whether a break follows the text.
     * @return void
     */
    public function renderText($text, $paragraphOption = [], $fontOption = [], $breakAfter = false)
    {
        $instructionMode = (substr($text, 0, 1) == '[');
        if ($instructionMode) {
            $text = str_replace("\n\n", "\n", $text);
        }
        $textRun = $this->section->addTextRun(($instructionMode ? (self::INSTRUCTIONS) : $paragraphOption));
        if (trim($text) == '') {
            return;
        }
        $paragraphs = explode("\n", trim($text));
        $ct = 0;
        foreach ($paragraphs as $paragraph) {
            $ct++;
            if (substr($paragraph, 0, 1) != '[') {
                $textRun->addText($paragraph, $fontOption);
            } else {
                preg_match('/\[(.*)?]/', $paragraph, $matches);
                $keyWord = '';
                if (count($matches)) {
                    $keyWord = $matches[1] ?? '';
                    $paragraph = trim(str_replace('['.$keyWord.']', '', $paragraph));
                    $paragraph = strtr($paragraph, ["\r" => '', "\n" => '', '>>' => "\t"]);
                }
                if (trim($keyWord) && ($keyWord == $this->recipient)) {
                    // highlight for current recipient
                    $textRun->addText(
                        $keyWord."\t",
                        array_merge(['size' => 8, 'italic' => true, 'bold' => false], ['fgColor' => 'yellow'])
                    );
                } else {
                    $textRun->addText($keyWord."\t", ['size' => 8, 'italic' => true, 'bold' => false]);
                }
                if (trim($paragraph)) {
                    $this->renderWithLineBreaks($textRun, $paragraph, $fontOption);
                }
            }
            if ((! $instructionMode) && (($ct < count($paragraphs)) || $breakAfter)) {
                $textRun->addTextBreak();
            }
            if ($instructionMode) {
                $textRun = $this->section->addTextRun(self::INSTRUCTIONS);
            }
        }
    }

    /**
     * @param  string  $text  Text containing line separators.
     * @param  array<string, mixed>  $fontOption  Font options.
     * @return void
     */
    protected function renderWithLineBreaks(TextRun $textRun, $text, $fontOption)
    {
        $lines = explode("\n", str_replace('|', "\n", $text));
        $ct = 0;
        foreach ($lines as $line) {
            $ct++;
            $textRun->addText($line, $fontOption);
            if ($ct < count($lines)) {
                $textRun->addTextBreak();
            }
        }
    }

    /**
     * Recursively extract text content from a DOM node while keeping semantic breaks.
     */
    protected static function extractTextWithBreaksFromDomNode(DOMNode $node): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return html_entity_decode($node->nodeValue ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        if ($node->nodeName === 'br') {
            return "\n";
        }

        $text = '';
        foreach ($node->childNodes as $childNode) {
            $text .= self::extractTextWithBreaksFromDomNode($childNode);
        }

        if (in_array($node->nodeName, ['p', 'div', 'blockquote', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'])) {
            $text = trim($text);
            if ($text === '') {
                return '';
            }

            return $text."\n\n";
        }

        return $text;
    }

    /**
     * @param  string|array<int, string>  $text  Literal text or paragraphs.
     * @return void
     */
    public function renderLiteral(string|array $text)
    {
        $paragraphs = is_array($text) ? $text : [$text];
        foreach ($paragraphs as $paragraph) {
            switch (substr($paragraph, 0, 1)) {
                case '*':
                    $format = self::BOLD;
                    $paragraph = substr($paragraph, 1);
                    break;
                case '_':
                    $format = self::UNDERLINE;
                    $paragraph = substr($paragraph, 1);
                    break;
                default:
                    $format = [];
            }
            $paragraph = trim(
                strtr(
                    $paragraph,
                    [
                        "\r" => '',
                        "\n" => '<w:br />',
                    ]
                )
            );
            $this->renderParagraph(self::NO_INDENT, [[$paragraph, $format]], 1);
        }
    }

    /**
     * @param  string  $style  Paragraph style name.
     * @return array<string, mixed>|null
     */
    public function getParagraphStyle($style)
    {
        switch ($style) {
            case 'heading1':
                return [
                    'alignment' => Jc::START,
                    'indentation' => [
                        'left' => 0,
                        'right' => 0,
                        'firstLine' => 0,
                        'hanging' => 0,
                    ],
                    'keepNext' => true,
                    'lineHeight' => 1.08,
                    'spaceBefore' => Converter::pointToTwip(12),
                    'spaceAfter' => 0,
                ];
        }

        return null;
    }

    /**
     * Render the title for a service-related document in something resembling "Heading 1"
     */
    /**
     * @return void
     */
    public function renderServiceTitleHeading(Service $service)
    {
        $run = new TextRun($this->config['styles']['paragraphs']['titles'][1]);
        $run->addText($service->titleText(false), $this->config['styles']['fonts']['titles'][1]);
        $run->addTextBreak();
        $run->addText(
            $service->trueDate()->setTimeZone('Europe/Berlin')->isoFormat('DD.MM.YYYY, HH:mm').' Uhr'.', '
            .$service->locationText(),
            $this->config['styles']['fonts']['titles'][1]
        );
        $this->getSection()->addTitle($run, 0);
    }

    /**
     * @return string|null
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * @param  string|null  $recipient  Recipient name.
     */
    public function setRecipient($recipient): void
    {
        $this->recipient = $recipient;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param  array<string, mixed>  $config  Document configuration.
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }
}
