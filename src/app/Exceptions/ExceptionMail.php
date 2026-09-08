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

namespace App\Exceptions;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Symfony\Component\ErrorHandler\Exception\FlattenException;

class ExceptionMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @var array{message: string, file: string, line: int, trace: string} */
    protected array $exception;

    /** @var array */
    protected $report;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(FlattenException $flat, array $report) {
        $this->exception = [
            'message' => self::sanitizeUtf8($flat->getMessage()),
            'file' => self::sanitizeUtf8($flat->getFile()),
            'line' => (int) $flat->getLine(),
            'trace' => self::sanitizeUtf8($flat->getTraceAsString()),
        ];
        $this->report = self::sanitizeUtf8($report);
    }

    private static function sanitizeUtf8(mixed $value): mixed
    {
        if (is_string($value)) {
            return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }

        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $key => $item) {
                $sanitized[is_string($key) ? self::sanitizeUtf8($key) : $key] = self::sanitizeUtf8($item);
            }
            return $sanitized;
        }

        if (is_scalar($value) || $value === null) {
            return $value;
        }

        if ($value instanceof \JsonSerializable) {
            return self::sanitizeUtf8($value->jsonSerialize());
        }

        if ($value instanceof \Stringable) {
            return self::sanitizeUtf8((string) $value);
        }

        return '[' . get_debug_type($value) . ']';
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('mail.dev.exception', [
            'exception' => $this->exception,
            'report' => $this->report,
        ])->subject('Exception: ' . $this->exception['message']);
    }
}
