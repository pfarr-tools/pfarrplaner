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

namespace App\Documents\Word;


use App\Models\Service;
use Illuminate\Support\Facades\Response;
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

class DefaultWordDocument
{
    /** @var PhpWord|null */
    protected $phpWord = null;
    /** @var Section */
    protected $section = null;

    protected $instructionsFontStyle = ['size' => 8, 'italic' => true];
    protected $instructionsParagraphStyle = [];
    protected $recipient = null;

    public const NORMAL = 'Standard';
    public const BLOCKQUOTE = 'Zitat';
    public const INSTRUCTIONS = 'Standard mit Anweisungen';
    public const BOLD = ['bold' => true];
    public const UNDERLINE = ['underline' => Font::UNDERLINE_SINGLE];
    public const BOLD_UNDERLINE = ['bold' => true, 'underline' => Font::UNDERLINE_SINGLE];

    protected $config = [];

    public function __construct($config = [])
    {

        $this->config = array_replace_recursive($this->getBaseConfig(), $this->config, $config);
        Settings::setOutputEscapingEnabled(true);
        $this->phpWord = new PhpWord();
        $this->phpWord->getSettings()->setThemeFontLang(new Language(Language::DE_DE));
        $this->configureLayout($config['layout'] ?? []);;
        $this->setDefaultDocumentStyles($config);
    }

    /**
     * Get the base config array.
     *
     * This will expand all ['tabs'] configs to PhpWord's Tab objects.
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

    protected function configureLayout($config)
    {
        $this->section = $this->phpWord->addSection($config);
    }

    protected function setDefaultDocumentStyles($config = [])
    {
        $this->phpWord->setDefaultFontName($config['defaultFont'] ?? 'Sarabun Light');
        $this->phpWord->setDefaultFontSize($config['defaultFontSize'] ?? 11);

        // Standard
        $this->phpWord->setDefaultParagraphStyle($this->config['styles']['paragraphs']['default'] ?? []);
        $this->phpWord->addFontStyle(self::NORMAL, $this->config['styles']['fonts'][self::NORMAL]);

        // title styles
        foreach ($this->config['styles']['paragraphs']['titles'] as $level => $pStyle) {
            $this->phpWord->addTitleStyle($level, $this->config['styles']['fonts']['titles'][$level] ?? [], $pStyle);
        }

        foreach ($this->config['styles']['paragraphs']['custom'] as $pKey => $pStyle) {
            $this->phpWord->addParagraphStyle($pKey, $pStyle);
        }

        foreach ($this->config['styles']['fonts']['custom'] as $fKey => $fStyle) {
            $this->phpWord->addFontStyle($fKey, $fStyle);
        }

    }

// SETTERS

    /**
     * @return PhpWord|null
     */
    public function getPhpWord(): ?PhpWord
    {
        return $this->phpWord;
    }

    /**
     * @param PhpWord|null $phpWord
     */
    public function setPhpWord(?PhpWord $phpWord): void
    {
        $this->phpWord = $phpWord;
    }

    /**
     * @return Section
     */
    public function getSection(): ?Section
    {
        return $this->section;
    }

    /**
     * @param Section $section
     */
    public function setSection(?Section $section): void
    {
        $this->section = $section;
    }


    /**
     * @param string $template
     * @param array $blocks
     * @param int $emptyParagraphsAfter
     * @param null $existingTextRun
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
            if (null !== $block[0]) {
                $ct = 0;
                foreach (explode("\n", $block[0]) as $item) {
                    if ($ct > 0) $textRun->addTextBreak();
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
     * @param $filename
     * @throws Exception
     */
    public function sendToBrowser($filename)
    {
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $objWriter = IOFactory::createWriter($this->phpWord, 'Word2007');
        $objWriter->save($tempFile);
        return response()->download($tempFile, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
            ->deleteFileAfterSend(true);
    }

    /**
     * Render some text with default formatting
     * @param $text Text
     * @param array $fontOption Font options
     * @param false $breakAfter Text break at the end?
     */
    public function renderNormalText($text, $fontOption = [], $breakAfter = false)
    {
        return $this->renderText($text, self::NORMAL, $fontOption, $breakAfter);
    }

    /**
     * Render some text with specific paragraph style
     * @param $text Text
     * @param array|string $paragraphOption Font options
     * @param array $fontOption Font options
     * @param false $breakAfter Text break at the end?
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
                    $keyWord = $matches[1];
                    $paragraph = trim(str_replace('[' . $keyWord . ']', '', $paragraph));
                    $paragraph = strtr($paragraph, ["\r" => '', "\n" => '', '>>' => "\t"]);
                }
                if (trim($keyWord) && ($keyWord == $this->recipient)) {
                    // highlight for current recipient
                    $textRun->addText(
                        $keyWord . "\t",
                        array_merge($this->getInstructionsFontStyle(), ['fgColor' => 'yellow'])
                    );
                } else {
                    $textRun->addText($keyWord . "\t", $this->getInstructionsFontStyle());
                }
                if (trim($paragraph)) {
                    $this->renderWithLineBreaks($textRun, $paragraph, $fontOption);
                }
            }
            if ((!$instructionMode) && (($ct < count($paragraphs)) || $breakAfter)) {
                $textRun->addTextBreak();
            }
            if ($instructionMode) {
                $textRun = $this->section->addTextRun(self::INSTRUCTIONS);
            }
        }
    }

    protected function renderWithLineBreaks(TextRun $textRun, $text, $fontOption)
    {
        $lines = explode("\n", str_replace("|", "\n", $text));
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
     * @param string $text
     */
    public function renderLiteral(string $text)
    {
        if (!is_array($text)) {
            $text = [$text];
        }
        foreach ($text as $paragraph) {
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
                        "\n" => '<w:br />'
                    ]
                )
            );
            $this->renderParagraph(self::NO_INDENT, [[$paragraph, $format]], 1);
        }
    }


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
    }

    /**
     * Render the title for a service-related document in something resembling "Heading 1"
     */
    public function renderServiceTitleHeading(Service $service)
    {
        $run = new TextRun($this->config['styles']['paragraphs']['titles'][1]);
        $run->addText($service->titleText(false), $this->config['styles']['fonts']['titles'][1]);
        $run->addTextBreak();
        $run->addText(
            $service->date->setTimeZone('Europe/Berlin')->isoFormat('DD.MM.YYYY, HH:mm').' Uhr' . ', '
            . $service->locationText(),
            $this->config['styles']['fonts']['titles'][1]
        );
        $this->getSection()->addTitle($run, 0);
    }


    /**
     * @return null
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * @param null $recipient
     */
    public function setRecipient($recipient): void
    {
        $this->recipient = $recipient;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }




}



