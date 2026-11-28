<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 * @version git: $
 */

namespace Tests\Unit\Services;

use App\Models\Service;
use App\Services\OfferingSignatureService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Verify validation and canonicalization of public offering signatures.
 */
class OfferingSignatureServiceTest extends TestCase
{
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    /**
     * A valid PNG data URL is decoded without retaining the data URL wrapper.
     */
    public function test_valid_png_data_url_is_decoded(): void
    {
        $image = app(OfferingSignatureService::class)->validateImage('data:image/png;base64,'.self::PNG);

        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $image);
    }

    /**
     * Non-PNG or malformed data URLs are rejected before storage.
     */
    public function test_invalid_image_data_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        app(OfferingSignatureService::class)->validateImage('data:image/jpeg;base64,'.base64_encode('not an image'));
    }

    /**
     * Canonical snapshots use stable keys and signer ordering.
     */
    public function test_snapshot_is_canonicalized(): void
    {
        $service = Service::make([
            'id' => 42,
            'city_id' => 7,
            'date' => '2026-11-27 10:00:00',
            'offering_amount' => '12.50',
            'offering_denominations' => ['500' => 1, '200' => 1],
            'offering_goal' => 'Brot für die Welt',
            'offerings_counter1' => 'Anna',
            'offerings_counter2' => 'Bernd',
        ]);

        $snapshot = app(OfferingSignatureService::class)->canonicalSnapshot($service, [
            ['name' => 'Bernd', 'image' => 'bernd-image'],
            ['name' => 'Anna', 'image' => 'anna-image'],
        ]);

        $this->assertSame(['Anna', 'Bernd'], array_column($snapshot['signatures'], 'name'));
        $this->assertSame('12.50', $snapshot['offering']['amount']);
        $this->assertSame(['200' => 1, '500' => 1], $snapshot['offering']['denominations']);
    }
}
