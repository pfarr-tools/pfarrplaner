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

namespace Tests\Unit\Quality;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/** Check PHPDoc recognition around attributes without loading fixture classes. */
class DocumentationCheckerUnitTest extends TestCase
{
    /**
     * Run the real checker against a small isolated source tree.
     *
     * @param  string  $source  Fixture PHP source.
     * @param  int  $expectedExit  Expected check result.
     */
    #[DataProvider('attributeSources')]
    public function test_attributes_preserve_documentation(string $source, int $expectedExit): void
    {
        $directory = sys_get_temp_dir().'/phpdoc-check-'.bin2hex(random_bytes(8));
        $filesystem = new Filesystem;
        $filesystem->mkdir(array_map(fn (string $name): string => $directory.'/'.$name, ['app', 'routes', 'database', 'scripts']));
        try {
            copy(dirname(__DIR__, 3).'/scripts/check-documentation.php', $directory.'/scripts/check-documentation.php');
            file_put_contents($directory.'/app/Fixture.php', $source);
            $process = new Process([PHP_BINARY, $directory.'/scripts/check-documentation.php']);
            $process->run();
            $this->assertSame($expectedExit, $process->getExitCode(), $process->getErrorOutput().$process->getOutput());
        } finally {
            $filesystem->remove($directory);
        }
    }

    /** @return array<string, array{string, int}> Documented and undocumented attribute cases. */
    public static function attributeSources(): array
    {
        return [
            'class with nested attribute arrays' => [
                '<?php /** Documents the fixture. */ #[Example(options: ["nested" => ["]", "["]])] #[Another] class Fixture {}',
                0,
            ],
            'method with attribute' => [
                '<?php /** Documents the fixture. */ class Fixture { /** Documents the operation. */ #[Example] public function work(): void {} }',
                0,
            ],
            'attribute is not documentation' => [
                '<?php #[Example(options: ["nested" => [1]])] class Fixture {}',
                1,
            ],
            'documentation does not leak into next declaration' => [
                '<?php /** Documents the fixture. */ #[Example] class Fixture { public function undocumented(): void {} }',
                1,
            ],
        ];
    }
}
