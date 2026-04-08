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

namespace App\Helpers;

use PhpOffice\PhpSpreadsheet\Shared\Drawing;

class PPTUnitsHelper
{
    const UNIT_EMU = 'emu';
    const UNIT_CENTIMETER = 'cm';
    const UNIT_INCH = 'in';
    const UNIT_MILLIMETER = 'mm';
    const UNIT_PIXEL = 'px';
    const UNIT_POINT = 'pt';


    /**
     * Convert specified value from one to another units
     *
     * @param mixed  $value     value at fromUnit units
     * @param string $fromUnit units of value (from units)
     * @param string $toUnit   result units (to units)
     *
     * @return mixed value value at toUnit units
     */
    public static function convert(
        $value,
        $fromUnit = self::UNIT_MILLIMETER,
        $toUnit = self::UNIT_EMU
    ) {
        if ($fromUnit === $toUnit) {
            return $value;
        }

        // Convert from $fromUnit to EMU
        switch ($fromUnit) {
            case self::UNIT_MILLIMETER:
                $value *= 36000;
                break;
            case self::UNIT_CENTIMETER:
                $value *= 360000;
                break;
            case self::UNIT_INCH:
                $value *= 914400;
                break;
            case self::UNIT_PIXEL:
                $value = Drawing::pixelsToEmu($value);
                break;
            case self::UNIT_POINT:
                $value *= 12700;
                break;
            case self::UNIT_EMU:
            default:
                // no changes
        }

        // Convert from EMU to $toUnit
        switch ($toUnit) {
            case self::UNIT_MILLIMETER:
                $value /= 36000;
                break;
            case self::UNIT_CENTIMETER:
                $value /= 360000;
                break;
            case self::UNIT_INCH:
                $value /= 914400;
                break;
            case self::UNIT_PIXEL:
                $value = Drawing::emuToPixels($value);
                break;
            case self::UNIT_POINT:
                $value /= 12700;
                break;
            case self::UNIT_EMU:
            default:
                // no changes
        }
        return $value;
    }


    /**
     * Estimate wrapped lines based on actual font metrics (TTF) and max pixel width.
     * This is stable across PPTX/ODP because we're not relying on viewer tab stops,
     * but on our own wrapping + line counting.
     */
    public static function wrapTextByPixelWidth(
        string $text,
        string $ttfPath,
        int $fontSizePx,
        int $maxWidthPx
    ): array {
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        if ($text === '') {
            return [''];
        }

        $words = preg_split('/\s+/u', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = ($currentLine === '') ? $word : ($currentLine . ' ' . $word);

            if (static::textPixelWidth($testLine, $ttfPath, $fontSizePx) <= $maxWidthPx) {
                $currentLine = $testLine;
                continue;
            }

            // If a single word is longer than max width, hard-split it
            if ($currentLine === '') {
                $lines = array_merge($lines, static::hardSplitWordByPixelWidth($word, $ttfPath, $fontSizePx, $maxWidthPx));
                $currentLine = '';
                continue;
            }

            $lines[] = $currentLine;
            $currentLine = $word;
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return $lines ?: [''];
    }

    /** Measure text width in pixels for a given TTF and font size. */
    public static function textPixelWidth(string $text, string $ttfPath, int $fontSizePx): int
    {
        // imagettfbbox returns an array of 8 coords
        $box = imagettfbbox($fontSizePx, 0, $ttfPath, $text);
        if ($box === false) {
            // Fallback: rough estimate if GD/FreeType unavailable
            return (int) (mb_strlen($text) * ($fontSizePx * 0.55));
        }
        return (int) abs($box[2] - $box[0]);
    }

    /** Split a too-long word into chunks that fit maxWidthPx (pixel-based). */
    public static function hardSplitWordByPixelWidth(
        string $word,
        string $ttfPath,
        int $fontSizePx,
        int $maxWidthPx
    ): array {
        $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);
        $parts = [];
        $current = '';

        foreach ($chars as $ch) {
            $test = $current . $ch;
            if (static::textPixelWidth($test, $ttfPath, $fontSizePx) <= $maxWidthPx) {
                $current = $test;
            } else {
                if ($current !== '') {
                    $parts[] = $current;
                }
                $current = $ch;
            }
        }

        if ($current !== '') {
            $parts[] = $current;
        }

        return $parts ?: [''];
    }


    /**
     * Return true if the text (array of lines) fits into the text area height.
     */
    public static function textFitsOnSlide(array $lines, int $fontSizePx, int $maxWidthPx, int $maxHeightPx, string $ttfPath): bool
    {
        return static::estimateTextHeightPx($lines, $fontSizePx, $maxWidthPx, $ttfPath) <= $maxHeightPx;
    }

    /**
     * Estimate height in pixels after wrapping using TTF font metrics.
     * We model each input line as one paragraph.
     */
    public static function estimateTextHeightPx(array $lines, int $fontSizePx, int $maxWidthPx, string $ttfPath): int
    {
        // Approx line height from font metrics. If you prefer, make this configurable.
        $lineHeightPx = static::estimateLineHeightPx($ttfPath, $fontSizePx);
        $paragraphGapPx = (int) round($lineHeightPx * 0.15); // small gap between paragraphs

        $totalLines = 0;

        foreach ($lines as $line) {
            $line = (string)$line;

            // Handle your "\t" indent convention
            $isIndented = (mb_substr($line, 0, 1) === "\t");
            if ($isIndented) {
                $line = mb_substr($line, 1);
            }

            // Your visual indent is marginLeft(35) px for indented paragraphs,
            // so reduce available width accordingly (keep it consistent for both PPTX+ODP).
            $availableWidthPx = $maxWidthPx - ($isIndented ? 35 : 0);
            $availableWidthPx = max(50, $availableWidthPx);

            // Wrap into lines using your helper (TTF + width)
            $wrappedLines = static::wrapTextByPixelWidth(
                $line,
                $ttfPath,
                $fontSizePx,
                $availableWidthPx
            );

            $totalLines += max(1, count($wrappedLines));
        }

        if ($totalLines === 0) {
            return 0;
        }

        // Height = lines * lineHeight + gaps between paragraphs
        $paragraphCount = count($lines);
        return (int) round(($totalLines * $lineHeightPx) + max(0, $paragraphCount - 1) * $paragraphGapPx);
    }

    /**
     * Estimate a good line height from font metrics. (Works with GD+FreeType.)
     */
    public static function estimateLineHeightPx(string $ttfPath, int $fontSizePx): int
    {
        // Measure something with ascenders/descenders
        $box = imagettfbbox($fontSizePx, 0, $ttfPath, 'Ag');
        if ($box === false) {
            return (int) round($fontSizePx * 1.25);
        }
        $height = abs($box[7] - $box[1]); // y coords
        // Add a little leading
        return (int) max(1, round($height * 1.15));
    }

    /**
     * Split text into two parts so part1 fits. Prefer punctuation split.
     * Returns [part1Lines, part2Lines]. If it cannot split reasonably, part2Lines may be empty.
     */
    public static function splitTextToFitSlide(array $lines, int $fontSizePx, int $maxWidthPx, int $maxHeightPx, string $ttfPath): array
    {
        // Fast path
        if (static::textFitsOnSlide($lines, $fontSizePx, $maxWidthPx, $maxHeightPx, $ttfPath)) {
            return [$lines, []];
        }

        // Join lines with newline markers so we can split at punctuation across lines.
        // We keep '\n' as hard paragraph breaks.
        $fullText = implode("\n", $lines);

        // Candidate split positions by preference groups
        $preferred = ['.', '!', '?'];
        $secondary = [',', ';', ':'];

        // We'll try to find a split near the point where it starts overflowing.
        // Approach: incrementally grow part1 by "chunks" (sentences/clauses) and stop when it overflows.
        $chunks = static::splitIntoChunksKeepingDelimiters($fullText);

        $part1 = '';
        $lastGoodPart1 = '';
        foreach ($chunks as $chunk) {
            $test = $part1 . $chunk;
            $testLines = explode("\n", $test);

            if (static::textFitsOnSlide($testLines, $fontSizePx, $maxWidthPx, $maxHeightPx, $ttfPath)) {
                $part1 = $test;
                $lastGoodPart1 = $part1;
            } else {
                break;
            }
        }

        // If we couldn't fit anything, fall back to a rough halfway split by words.
        if (trim($lastGoodPart1) === '') {
            return static::fallbackSplitByWords($fullText);
        }

        // Now refine: ensure we split at a punctuation mark if possible
        $splitPos = static::bestPunctuationSplitPosition($lastGoodPart1, $preferred, $secondary);

        if ($splitPos !== null) {
            $finalPart1 = trim(mb_substr($lastGoodPart1, 0, $splitPos));
            $finalPart2 = trim(mb_substr($fullText, $splitPos));
        } else {
            $finalPart1 = trim($lastGoodPart1);
            $finalPart2 = trim(mb_substr($fullText, mb_strlen($finalPart1)));
        }

        return [explode("\n", $finalPart1), explode("\n", $finalPart2)];
    }

    /**
     * Split into chunks like sentences/clauses while keeping delimiters, including newlines.
     * This makes it easier to grow text until it fits.
     */
    public static function splitIntoChunksKeepingDelimiters(string $text): array
    {
        // Split after punctuation or newline, keeping delimiter
        // Example chunk ends with ". " or "\n"
        $pattern = '/(.+?(?:[\.!\?][\s]+|[,:;:][\s]+|\n+|$))/su';
        preg_match_all($pattern, $text, $matches);
        return $matches[1] ?: [$text];
    }

    /**
     * Return best split position in the given text (end of last preferred punctuation, else secondary).
     * Position returned is the index *after* the punctuation and following whitespace.
     */
    public static function bestPunctuationSplitPosition(string $text, array $preferred, array $secondary): ?int
    {
        $candidates = [];

        foreach ($preferred as $p) {
            $pos = mb_strrpos($text, $p);
            if ($pos !== false) {
                $candidates[] = $pos + 1;
            }
        }
        if (!$candidates) {
            foreach ($secondary as $p) {
                $pos = mb_strrpos($text, $p);
                if ($pos !== false) {
                    $candidates[] = $pos + 1;
                }
            }
        }

        if (!$candidates) {
            return null;
        }

        // pick the rightmost candidate
        $splitPos = max($candidates);

        // include trailing whitespace after the punctuation (so slide 2 doesn't start with space)
        $len = mb_strlen($text);
        while ($splitPos < $len) {
            $ch = mb_substr($text, $splitPos, 1);
            if (!preg_match('/\s/u', $ch)) {
                break;
            }
            $splitPos++;
        }

        return $splitPos;
    }

    /**
     * Fallback split by words around the middle (keeps newlines as spaces).
     */
    public static function fallbackSplitByWords(string $text): array
    {
        $normalized = preg_replace("/\n+/u", " ", $text);
        $words = preg_split('/\s+/u', trim($normalized));
        $half = (int) floor(count($words) / 2);

        $part1 = implode(' ', array_slice($words, 0, max(1, $half)));
        $part2 = implode(' ', array_slice($words, max(1, $half)));

        return [[ $part1 ], [ $part2 ]];
    }


}
