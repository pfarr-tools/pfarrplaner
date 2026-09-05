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

namespace App\Liturgy\Bible;

class ReferenceParser extends \Peregrinus\BibleReferenceParser\ReferenceParser
{
    /**
     * @return ReferenceParser
     */
    public static function getInstance(): ReferenceParser
    {
        if (null === static::$instance) {
            static::$instance = new self();
        }
        return static::$instance;
    }

    public function __construct()
    {
        parent::__construct(
            config('bible.parser.books'),
            config('bible.versions', []),
            config('bible.copyrights', [])
        );
    }
}
