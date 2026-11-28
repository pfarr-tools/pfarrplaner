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

namespace App\Services;

use App\Models\Service;
use Illuminate\Validation\ValidationException;

/**
 * Validate and canonicalize electronic offering signatures.
 */
class OfferingSignatureService
{
    private const MAX_IMAGE_BYTES = 512_000;

    /**
     * Decode and validate a PNG data URL received from the public form.
     *
     * @param  string  $dataUrl  Submitted PNG data URL.
     * @return string Binary PNG contents.
     *
     * @throws ValidationException When the payload is not a safe PNG.
     */
    public function validateImage(string $dataUrl): string
    {
        if (! preg_match('/^data:image\/png;base64,(?<payload>[A-Za-z0-9+\/=]+)$/', $dataUrl, $matches)) {
            throw ValidationException::withMessages(['signatures' => 'Die Unterschrift muss als PNG übermittelt werden.']);
        }

        $image = base64_decode($matches['payload'], true);
        if ($image === false || strlen($image) > self::MAX_IMAGE_BYTES || ! str_starts_with($image, "\x89PNG\r\n\x1a\n")) {
            throw ValidationException::withMessages(['signatures' => 'Die Unterschrift ist ungültig oder zu groß.']);
        }

        $imageInfo = @getimagesizefromstring($image);
        if ($imageInfo === false || $imageInfo['mime'] !== 'image/png') {
            throw ValidationException::withMessages(['signatures' => 'Die Unterschrift ist keine gültige PNG-Datei.']);
        }

        return $image;
    }

    /**
     * Build the stable data structure used for hashes and manifests.
     *
     * @param  Service  $service  Service represented by the settlement.
     * @param  list<array{name: string, image: string}>  $signatures  Validated signers and image hashes.
     * @return array<string, mixed> Canonical snapshot.
     */
    public function canonicalSnapshot(Service $service, array $signatures): array
    {
        usort($signatures, static fn (array $left, array $right): int => strcmp($left['name'], $right['name']));
        $denominations = $service->offering_denominations ?? [];
        ksort($denominations, SORT_NUMERIC);

        return [
            'service' => [
                'id' => $service->id,
                'city_id' => $service->city_id,
                'date' => $service->date?->copy()->utc()->format('Y-m-d\\TH:i:s\\Z'),
            ],
            'offering' => [
                'amount' => (string) $service->offering_amount,
                'denominations' => $denominations,
                'goal' => (string) $service->offeringGoal(),
                'counter1' => (string) $service->offerings_counter1,
                'counter2' => (string) $service->offerings_counter2,
            ],
            'signatures' => $signatures,
        ];
    }
}
