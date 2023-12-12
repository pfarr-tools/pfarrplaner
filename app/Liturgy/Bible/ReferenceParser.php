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

namespace App\Liturgy\Bible;


class ReferenceParser
{

    protected $map = [];
    protected $rawMap = [];

    /** @var ReferenceParser */
    protected static $instance = null;

    /**
     * @return ReferenceParser
     */
    public static function getInstance(): ReferenceParser
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->map = config('bible.parser.books');
        $this->buildRawMap();
    }

    protected function buildRawMap()
    {
        foreach ($this->map as $code => $bookNames) {
            if (!is_array($bookNames)) {
                $bookNames = [$bookNames];
            }
            foreach ($bookNames as $bookName) {
                $this->rawMap[$bookName] = $code;
            }
        }
        // build shortened names
        foreach ($this->rawMap as $bookName => $code) {
            $stopAtLength = (is_numeric(substr($bookName, 0, 1)) ? 3 : 2);
            for ($i = strlen($bookName) - 1; $i > $stopAtLength; $i--) {
                $short = substr($bookName, 0, $i);

                if (isset($this->map[$short])) {
                    // this is an official book code, don't use and stop shortening
                    continue;
                } elseif (isset($this->rawMap[$short])) {
                    // conflict: this already matches other book
                    // --> delete completely (if it's not an official name) and stop shortening
                    if (!isset($this->map[$this->rawMap[$short]])) {
                        unset($this->rawMap[$short]);
                    }
                    continue;
                } else {
                    $this->rawMap[$short] = $code;
                }
            }
        }
    }


    public function parseSingleVerseReference($reference, $inferBook, $inferChapter)
    {
        preg_match(
            '/^(?:(?<book>[\p{L}\d\s\.]+)\s+)?(?:(?<chapter>\d+),)?(?<verse>\d+)[a-zA-Z]?\.?$/u',
            $reference,
            $matches
        );

        $bookRaw = ($matches['book'] ?? '') ?: $inferBook;
        $book = $this->matchBook(preg_replace('/(\d).\s?/', '$1', $bookRaw));
        return [
            'book' => $book,
            'bookTitle' => $this->getBookTitle($book),
            'bookRaw' => ($matches['book'] ?? '') ?: $inferBook,
            'chapter' => ($matches['chapter'] ?? '') ?: $inferChapter,
            'verse' => $matches['verse'] ?? '',
        ];
    }

    /**
     * Get a parsed reference from a text
     *
     * Text reference may include optional verses in parentheses, like:
     * Jesaja 42,1-4(5-9)13-14.16f.
     *
     * Note that partial verse references like 20a are expanded to full verses.
     *
     * @param string $reference ReferenceText
     * @return array Parsed reference
     */
    public function parse(string $reference): array
    {
        $originalReference = $reference;

        $reference = trim($reference);
        // add semicola before and after parentheses, fix dash
        $reference = strtr($reference, ['(' => ';(', ')' => ');', '–' => '-']);
        // take care of 'f.' (replace it by a verse range)
        if (preg_match_all('/(\d+)f./', $reference, $matches)) {
            foreach ($matches[0] as $matchIndex => $matched) {
                $rangeEnd = $matches[1][$matchIndex]+1;
                $reference = str_replace($matched, $matches[1][$matchIndex].'-'.$rangeEnd, $reference);
            }
        }
        // replace dots, which are not part of the book name, with semicola
        $reference = preg_replace('/(?<=\d|[)])\.(?=\d|\()/', ';', $reference);
        // separate parts of the reference
        $parts = explode(';', $reference);

        $refs = [];
        $currentBook = '';
        $currentChapter = '';
        foreach ($parts as $part) {
            $optional = false;
            if (str_starts_with($part, '(')) {
                $optional = true;
            }
            $part = strtr($part, ['(' => '', ')' => '']);
            $rangeParts = explode('-', $part);
            $ref = [];
            foreach ($rangeParts as $rangeIndex => $rangePart) {
                $parsed = $this->parseSingleVerseReference($rangePart, $currentBook, $currentChapter);
                $currentBook = $parsed['bookRaw'];
                $currentChapter = $parsed['chapter'];
                $ref[$rangeIndex] = $parsed;
            }
            if (!isset($ref[1])) {
                $ref[1] = $ref[0];
            }
            $refs[] = ['range' => $ref, 'optional' => $optional];
        }

        $correctedReference = $originalReference;
        foreach ($refs as $ref) {
            foreach ($ref['range'] as $part) {
                $correctedReference = str_replace($part['bookRaw'], $part['bookTitle'], $originalReference);
            }
        }
        return ([
            'originalReference' => $originalReference,
            'correctedReference' => $correctedReference,
            'parsed' => $refs,
        ]);
    }

    protected function matchBook($bookCode)
    {
        $book = $bookCode;
        foreach ($this->map as $key => $mappings) {
            if (!is_array($mappings)) {
                $mappings = [$mappings];
            }
            foreach ($mappings as $mapping) {
                if (substr(strtolower($mapping), 0, strlen($bookCode)) == strtolower($bookCode)) {
                    return $key;
                }
            }
        }
        return $book;
    }

    public function getBookTitle($bookCode)
    {
        if (!isset($this->map[$bookCode])) {
            return '';
        }
        $title = is_array($this->map[$bookCode]) ? $this->map[$bookCode][0] : $this->map[$bookCode];
        if (substr($title, 1, 1) == '.') {
            $title = substr($title, 0, 1) . '. ' . substr($title, 2);
        }
        return $title;
    }

    protected function renderSingleReference(
        array $reference,
        bool $optionalOpener = false,
        bool $renderBook = true,
        bool $renderChapter = true
    ) {
        $output = '';

        if ($renderBook) {
            $renderChapter = true;
        }
        if ($renderBook) {
            $output .= $reference['bookTitle'] . ' ';
        }
        if ($renderChapter) {
            $output .= $reference['chapter'] . (trim($reference['verse']) !== '' ? ',' : '');
        }
        return trim($output . ($optionalOpener ? '(' : '') . $reference['verseRaw']);
    }

    protected function renderRange(array $reference, bool $renderBook = true, bool $renderChapter = true)
    {
        $range = $reference['range'];
        $output = $this->renderSingleReference($range[0], $reference['optional'], $renderBook, $renderChapter);
        if ($range[1]['book'] !== $range[0]['book']) {
            $output .= '-' . $this->renderSingleReference($range[1], false);
        } elseif ($range[1]['chapter'] !== $range[0]['chapter']) {
            $output .= '-' . $this->renderSingleReference($range[1], false, false);
        } elseif ($range[1]['verse'] !== $range[0]['verse']) {
            $output .= '-' . $this->renderSingleReference($range[1], false, false, false);
        }
        return $output . ($reference['optional'] ? ')' : '');
    }

    public function render($reference): string
    {
        if (!is_array($reference)) {
            if (trim($reference) == '') {
                return '';
            }
            $reference = $this->parse($reference);
        }
        if ($reference == []) {
            return '';
        }
        $output = '';

        $previous = null;
        foreach ($reference['parsed'] as $section) {
            if (is_null($previous)) {
                $output .= $this->renderRange($section);
            } else {
                if ($previous['range'][0]['book'] !== $section['range'][0]['book']) {
                    $output .= '; ' . $this->renderRange($section);
                } elseif ($previous['range'][0]['chapter'] !== $section['range'][0]['chapter']) {
                    $output .= '; ' . $this->renderRange($section, false);
                } else {
                    $output .= '.' . $this->renderRange($section, false, false);
                }
            }
            $previous = $section;
        }

        return $output;
    }
}
