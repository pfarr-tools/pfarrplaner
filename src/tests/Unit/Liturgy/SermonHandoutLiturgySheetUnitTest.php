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

namespace Tests\Unit\Liturgy;

use App\Documents\Word\FoldedFlyerWordDocument;
use App\Liturgy\LiturgySheets\SermonHandoutLiturgySheet;
use App\Models\Places\City;
use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpWord\Element\Text;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

/** Check the generated contact column without downloading or publishing a document. */
class SermonHandoutLiturgySheetUnitTest extends TestCase
{
    /** A city without a legacy title produces one label and no extra empty text element. */
    public function test_contact_column_uses_one_city_label(): void
    {
        $service = new Service;
        $service->setRelation('city', new City(['name' => 'Teststadt']));
        $service->setRelation('pastors', new Collection);
        $sheet = new SermonHandoutLiturgySheet;
        (new ReflectionProperty($sheet, 'service'))->setValue($sheet, $service);
        $document = new FoldedFlyerWordDocument;

        $originalBasePath = base_path();
        $testResourcePath = sys_get_temp_dir().'/handout-resources-'.bin2hex(random_bytes(6));
        File::ensureDirectoryExists($testResourcePath.'/resources/img/logo');
        file_put_contents($testResourcePath.'/resources/img/logo/ELKW.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aG1cAAAAASUVORK5CYII='));
        $this->app->setBasePath($testResourcePath);
        try {
            (new ReflectionMethod($sheet, 'renderColumn2'))->invoke($sheet, $document);
        } finally {
            $this->app->setBasePath($originalBasePath);
            File::deleteDirectory($testResourcePath);
        }

        $table = $document->getSection()->getElements()[0];
        $run = $table->getRows()[1]->getCells()[0]->getElements()[0];
        $texts = [];
        foreach ($run->getElements() as $element) {
            if ($element instanceof Text) {
                $texts[] = $element->getText();
            }
        }
        $this->assertSame(['Kirchengemeinde Teststadt'], $texts);
    }
}
