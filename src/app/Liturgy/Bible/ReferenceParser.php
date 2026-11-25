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
 */

namespace App\Liturgy\Bible;

class ReferenceParser extends \Peregrinus\BibleReferenceParser\ReferenceParser
{
    public static function getInstance(): ReferenceParser
    {
        $instance = static::$instance;
        if (! $instance instanceof self) {
            $instance = new self;
            static::$instance = $instance;
        }

        return $instance;
    }

    /**
     * @return void
     */
    public function __construct()
    {
        /** @var array<string, array<string>|string>|null $bookMap */
        $bookMap = config('bible.parser.books');
        /** @var array<string, string> $versions */
        $versions = config('bible.versions', []);
        /** @var array<string, string> $copyrights */
        $copyrights = config('bible.copyrights', []);
        parent::__construct($bookMap, $versions, $copyrights);
    }
}
