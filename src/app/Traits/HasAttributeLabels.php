<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 */

namespace App\Traits;

use Illuminate\Support\Str;

/**
 * Resolves translated labels for model attributes and nested data paths.
 */
trait HasAttributeLabels
{
    /**
     * Returns the configured validation label or a readable fallback for a field path.
     *
     * @param  string  $attribute  Attribute name or dot-separated path.
     * @return string Human-readable label.
     */
    public function getAttributeLabel(string $attribute): string
    {
        return collect(explode('.', $attribute))
            ->map(function (string $segment): string {
                $label = __('validation.attributes.'.$segment);

                return $label === 'validation.attributes.'.$segment
                    ? Str::headline($segment)
                    : $label;
            })
            ->implode(' › ');
    }
}
