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

use App\Documents\PDF;
use App\Models\OfferingSettlement;
use App\Models\Service;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Atomically finalize, archive and verify an electronically signed offering.
 */
class OfferingSettlementFinalizer
{
    /**
     * @param  OfferingSignatureService  $signatures  Signature validator.
     * @param  Closure|null  $pdfRenderer  Optional renderer for isolated tests.
     */
    public function __construct(
        private readonly OfferingSignatureService $signatures,
        private readonly ?Closure $pdfRenderer = null,
    ) {}

    /**
     * Finalize the current service revision, returning an existing final record on repeat.
     *
     * @param  Service  $service  Service with saved offering fields.
     * @param  list<array{name: string, image: string}>  $input  Signatures from the public form.
     * @return OfferingSettlement Finalized settlement.
     *
     * @throws ValidationException When names or images are invalid.
     */
    public function finalize(Service $service, array $input): OfferingSettlement
    {
        $signatures = $this->validatedSignatures($service, $input);
        $snapshot = $this->signatures->canonicalSnapshot($service, array_map(
            static fn (array $signature): array => [
                'name' => $signature['name'],
                'image' => hash('sha256', $signature['image']),
            ],
            $signatures,
        ));
        $snapshotJson = $this->canonicalJson($snapshot);
        $snapshotHash = hash('sha256', $snapshotJson);

        return DB::transaction(function () use ($service, $signatures, $snapshot, $snapshotHash): OfferingSettlement {
            $existing = OfferingSettlement::query()
                ->where('service_id', $service->id)
                ->where('status', 'finalized')
                ->latest('revision')
                ->first();
            if ($existing !== null) {
                return $existing;
            }

            $revision = ((int) OfferingSettlement::query()->where('service_id', $service->id)->max('revision')) + 1;
            $settlement = OfferingSettlement::query()->create([
                'service_id' => $service->id,
                'revision' => $revision,
                'snapshot' => $snapshot,
                'signatures' => [],
                'status' => 'pending',
                'snapshot_hash' => $snapshotHash,
            ]);
            $writtenPaths = [];

            try {
                $signatureMetadata = [];
                foreach ($signatures as $index => $signature) {
                    $path = $this->signaturePath($settlement, $index);
                    $this->putOrFail($path, $signature['image']);
                    $writtenPaths[] = $path;
                    $signatureMetadata[] = [
                        'name' => $signature['name'],
                        'path' => $path,
                        'hash' => hash('sha256', $signature['image']),
                    ];
                }

                $settlement->update([
                    'signatures' => $signatureMetadata,
                    'status' => 'finalized',
                    'finalized_at' => now(),
                ]);
                $pdf = $this->renderPdf($service, $settlement->refresh());
                $pdfPath = $this->pdfPath($settlement);
                $this->putOrFail($pdfPath, $pdf);
                $writtenPaths[] = $pdfPath;

                $manifest = $this->manifest($settlement->refresh(), $pdf);
                $settlement->update([
                    'manifest' => $manifest,
                    'manifest_hash' => hash('sha256', $this->canonicalJson($manifest)),
                    'pdf_path' => $pdfPath,
                    'pdf_hash' => hash('sha256', $pdf),
                ]);

                return $settlement->refresh();
            } catch (\Throwable $exception) {
                Storage::delete($writtenPaths);
                throw $exception;
            }
        });
    }

    /**
     * Verify all hashes in a finalized settlement against private storage.
     *
     * @param  OfferingSettlement  $settlement  Settlement to verify.
     * @return bool Whether every linked object matches its recorded hash.
     */
    public function verify(OfferingSettlement $settlement): bool
    {
        if ($settlement->status !== 'finalized' || ! $settlement->pdf_path || ! Storage::exists($settlement->pdf_path)) {
            return false;
        }
        if (hash('sha256', Storage::get($settlement->pdf_path)) !== $settlement->pdf_hash) {
            return false;
        }
        foreach ($settlement->signatures ?? [] as $signature) {
            if (! Storage::exists($signature['path']) || hash('sha256', Storage::get($signature['path'])) !== $signature['hash']) {
                return false;
            }
        }

        $manifest = $settlement->manifest ?? [];
        if (hash('sha256', $this->canonicalJson($manifest)) !== $settlement->manifest_hash) {
            return false;
        }
        $signature = $manifest['signature'] ?? null;
        $keyId = $manifest['key_id'] ?? null;
        unset($manifest['signature']);

        return is_string($signature)
            && is_string($keyId)
            && hash_equals(hash('sha256', (string) config('app.key')), $keyId)
            && hash_equals(
                hash_hmac('sha256', $this->canonicalJson($manifest), (string) config('app.key')),
                $signature,
            );
    }

    /**
     * Validate names and decode every submitted image.
     *
     * @param  Service  $service  Service being signed.
     * @param  list<array{name: string, image: string}>  $input  Submitted signatures.
     * @return list<array{name: string, image: string}> Validated signatures.
     *
     * @throws ValidationException When the set is incomplete or invalid.
     */
    private function validatedSignatures(Service $service, array $input): array
    {
        $names = collect([$service->offerings_counter1, $service->offerings_counter2])
            ->map(static fn (mixed $name): string => trim((string) $name))
            ->filter()
            ->values()
            ->all();
        if ($names === [] || count($input) !== count($names)) {
            throw ValidationException::withMessages(['signatures' => 'Für jede Opferzähler:in ist eine Unterschrift erforderlich.']);
        }

        $validated = [];
        foreach ($names as $name) {
            $submitted = collect($input)->firstWhere('name', $name);
            if (! is_array($submitted) || ! isset($submitted['image'])) {
                throw ValidationException::withMessages(['signatures' => "Die Unterschrift von {$name} fehlt."]);
            }
            $validated[] = ['name' => $name, 'image' => $this->signatures->validateImage((string) $submitted['image'])];
        }

        return $validated;
    }

    /**
     * Render the final PDF from the immutable settlement data.
     *
     * @param  Service  $service  Service represented by the PDF.
     * @param  OfferingSettlement  $settlement  Settlement metadata.
     * @return string PDF bytes.
     */
    private function renderPdf(Service $service, OfferingSettlement $settlement): string
    {
        if ($this->pdfRenderer !== null) {
            return (string) ($this->pdfRenderer)($service, $settlement);
        }

        return PDF::fromView('reports.offeringcertificate.render', compact('service', 'settlement'))->pdf();
    }

    /**
     * Build the signed manifest for one finalized settlement.
     *
     * @param  OfferingSettlement  $settlement  Settlement metadata.
     * @param  string  $pdf  Archived PDF bytes.
     * @return array<string, mixed> Manifest contents.
     */
    private function manifest(OfferingSettlement $settlement, string $pdf): array
    {
        $payload = [
            'settlement_id' => $settlement->id,
            'service_id' => $settlement->service_id,
            'revision' => $settlement->revision,
            'snapshot_hash' => $settlement->snapshot_hash,
            'signatures' => $settlement->signatures,
            'pdf_hash' => hash('sha256', $pdf),
        ];
        $payload['key_id'] = hash('sha256', (string) config('app.key'));
        $payload['signature'] = hash_hmac('sha256', $this->canonicalJson($payload), (string) config('app.key'));

        return $payload;
    }

    /**
     * Return a stable JSON encoding for all integrity calculations.
     *
     * @param  array<string, mixed>  $value  Data to encode.
     * @return string Canonical JSON.
     */
    private function canonicalJson(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
    }

    /**
     * Store bytes on the private disk or fail finalization.
     *
     * @param  string  $path  Private path.
     * @param  string  $contents  File contents.
     */
    private function putOrFail(string $path, string $contents): void
    {
        if (! Storage::put($path, $contents)) {
            throw new RuntimeException('Die Opferurkunde konnte nicht archiviert werden.');
        }
    }

    /** @return string Private signature path. */
    private function signaturePath(OfferingSettlement $settlement, int $index): string
    {
        return "offering-settlements/{$settlement->service_id}/{$settlement->revision}/signature-{$index}.png";
    }

    /** @return string Private PDF path. */
    private function pdfPath(OfferingSettlement $settlement): string
    {
        return "offering-settlements/{$settlement->service_id}/{$settlement->revision}/certificate.pdf";
    }
}
