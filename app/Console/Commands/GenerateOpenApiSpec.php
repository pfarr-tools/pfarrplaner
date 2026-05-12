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

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenApi\Generator;

class GenerateOpenApiSpec extends Command
{
    protected $signature = 'openapi:generate
                            {--output= : Output file path (default: public/openapi.json)}';

    protected $description = 'Generate OpenAPI spec from PHP attributes in app/Http/Controllers/Api/';

    /**
     * @return int
     */
    public function handle(): int
    {
        $output = $this->option('output') ?: public_path('openapi.json');

        $this->info('Scanning app/Http/Controllers/Api/ …');

        $openapi = (new Generator())->generate([app_path('Http/Controllers/Api')]);

        file_put_contents($output, $openapi->toJson(JSON_PRETTY_PRINT));

        $this->info("OpenAPI spec written to {$output}");

        return self::SUCCESS;
    }
}
