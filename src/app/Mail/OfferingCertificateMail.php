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

namespace App\Mail;

use App\Documents\PDF;
use App\Models\Places\City;
use App\Models\Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Send an automatically generated offering certificate.
 */
class OfferingCertificateMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Service  $service  Service represented by the certificate.
     */
    public function __construct(private readonly Service $service)
    {
        $this->from(config('mail.from.address'), config('mail.from.name'));
    }

    /**
     * Build the certificate email with the matching PDF attachment.
     *
     * @return $this Configured mailable.
     */
    public function build(): self
    {
        $service = $this->service->load(['city', 'location', 'organists']);
        /** @var City $city */
        $city = $service->city;
        $pdf = PDF::fromView('reports.offeringcertificate.render', compact('service'))->pdf();
        $filename = 'Opferurkunde-'.$service->date->format('Y-m-d').'.pdf';

        return $this
            ->subject('Opferurkunde '.$city->name.' '.$service->date->format('d.m.Y'))
            ->view('mail.offering-certificate', compact('service'))
            ->attachData($pdf, $filename, ['mime' => 'application/pdf']);
    }
}
