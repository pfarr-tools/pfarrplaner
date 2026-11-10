<?php

namespace Tests\Unit;

use App\Models\Announcements;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use ReflectionClass;
use Tests\TestCase;

class AnnouncementsUnitTest extends TestCase
{
    public function test_funeral_announcement_includes_biblical_reference(): void
    {
        $announcements = (new ReflectionClass(Announcements::class))->newInstanceWithoutConstructor();
        $funeral = new class
        {
            public Carbon $dod;

            public string $buried_name = 'Müller, Max';

            public string $buried_address = 'Musterstraße 1';

            public int $age = 80;

            public string $type = 'Trauerfeier';

            public Service $service;

            public function __construct()
            {
                $this->dod = Carbon::parse('2026-05-20');
                $this->service = new Service;
                $this->service->date = Carbon::parse('2026-05-24');
                $this->service->setRelation('city', (object) ['name' => 'Musterstadt']);
            }
        };

        $reflection = new ReflectionClass(Announcements::class);
        $reflection->getProperty('service')->setValue($announcements, $funeral->service);
        $reflection->getProperty('funerals')->setValue($announcements, new Collection([$funeral]));

        $paragraphs = $announcements->getFuneralsArray();

        $this->assertSame(
            'Unser keiner lebt sich selber, und keiner stirbt sich selber. Leben wir, so leben wir dem Herrn; '
            .'sterben wir, so sterben wir dem Herrn. Darum: wir leben oder sterben, so sind wir des Herrn. '
            .'Denn dazu ist Christus gestorben und wieder lebendig geworden, dass er über Tote und Lebende Herr sei. '
            .'(Röm 14,7–9)',
            end($paragraphs),
        );
    }
}
