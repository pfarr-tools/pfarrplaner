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
 *
 * Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
 *
 * Pfarrplaner is based on the Laravel framework (https://laravel.com).
 * This file may contain code created by Laravel's scaffolding functions.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Console\Commands\DevBuilder;

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Leave\Absence;
use App\Models\Leave\Pool;
use App\Models\Liturgy\Block;
use App\Models\Liturgy\Item;
use App\Models\Location;
use App\Models\Parish;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Places\StreetRange;
use App\Models\Rites\Baptism;
use App\Models\Rites\Funeral;
use App\Models\Rites\Wedding;
use App\Models\Seating\Booking;
use App\Models\Service;
use App\Services\PackageService;
use Carbon\Carbon;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Class DemoBuilder
 * @package App\Console\Commands
 */
class DemoBuilder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:build';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build a new demo site';

    /** @var Generator */
    protected Generator $faker;

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $this->output->title('DemoBuilder');
        $this->line(str_pad('Pfarrplaner app version: ', 30) . PackageService::info()['buildVersion']);
        $this->line(str_pad('Source build date: ', 30) . PackageService::info()['date']->format('Y-m-d H:i'));
        $this->line(str_pad('Demo build date: ', 30) . Carbon::now()->format('Y-m-d H:i'));

        $this->output->section('Pre-flight checks');
        if (!$this->checkRequirements()) {
            return self::FAILURE;
        }


        $this->faker = Factory::create('de_DE');


        foreach (
            [
                'services' => Service::class,
                'users' => User::class,
                'cities' => City::class,
                'absences' => Absence::class,
                'attachments' => Attachment::class,
                'baptisms' => Baptism::class,
                'bookings' => Booking::class,
                'comments' => Comment::class,
                'funerals' => Funeral::class,
                'parishes' => Parish::class,
                'pools' => Pool::class,
                'streetRanges' => StreetRange::class,
                'weddings' => Wedding::class,
            ] as $unit => $model
        ) {
            $methodName = 'handle' . ucfirst($unit);
            $prepMethodName = 'prep' . ucfirst($unit);
            $this->output->section('Anonymizing ' . $unit);
            if (method_exists($this, $prepMethodName)) {
                if (!$this->writeResult('Preparing environment for demo ' . $unit, $this->$prepMethodName())) {
                    return self::FAILURE;
                }
            }
            if (class_exists($model)) {
                if (method_exists($this, $methodName)) {
                    $count = $model::query()->count();
                    $bar = $this->output->createProgressBar($count);
                    foreach ($model::cursor() as $record) {
                        $this->$methodName($record);
                        $bar->advance();
                    }
                    $bar->clear();
                    $this->writeResult($count . ' ' . $unit . ' anonymized.', true);
                }
            } else {
                $this->writeResult('Model ' . $model . ' not found', false);
                return self::FAILURE;
            }
        }

        $this->output->section('Anonymizing liturgy data');
        if (!$this->anonymizeLiturgyData()) {
            return self::FAILURE;
        }

        $this->output->section('Clearing operational demo data');
        if (!$this->anonymizeOperationalData()) {
            return self::FAILURE;
        }
    }

    protected function writeDelayedResult($title, $resultCallBack)
    {
        $this->output->write(str_pad($title, 60), false);
        $result = $resultCallBack();
        $this->output->writeln($result ? '      [<info>OK</info>]' : '  [<error>FAILED</error>]');
        return $result;
    }

    protected function writeResult($title, $result)
    {
        $this->output->write(str_pad($title, 60), false);
        $this->output->writeln($result ? '      [<info>OK</info>]' : '  [<error>FAILED</error>]');
        return $result;
    }

    protected function checkRequirement($title, $examinedValue, $compareTo = true, $individualMethod = false)
    {
        return $this->writeResult(
            'Check: ' . $title . ' => ' . (string)$examinedValue,
            ($individualMethod || ($examinedValue == $compareTo))
        );
    }

    protected function checkRequirements()
    {
        $demoMode = config('app.demo_mode');
        $databaseName = (string) Config::get('database.connections.' . Config::get('database.default') . '.database');

        $totalChecks = $this->checkRequirement('DEMO_MODE enabled', $demoMode)
            && $this->checkRequirement('Environment is demo or DEMO_MODE enabled', app()->environment(), 'demo', $demoMode)
            && $this->checkRequirement(
                'Database name contains _demo or DEMO_MODE enabled',
                $databaseName,
                true,
                $demoMode || str_contains($databaseName, '_demo'),
            );

        return $totalChecks;
    }

    protected function handleAbsences(Absence $absence)
    {
        $absence->update([
                             'reason' => $this->faker->sentence,
                             'admin_notes' => $this->faker->text(),
                             'approver_notes' => $this->faker->text(),
                             'replacement_notes' => $this->faker->text(),
                             'internal_notes' => $this->faker->text(),
                         ]);
    }

    protected function prepAttachments()
    {
        try {
            foreach (['demo.jpg', 'demo.pdf'] as $file) {
                $contents = file_get_contents(public_path('demo/' . $file));
                if ($contents === false || !Storage::put('demo/' . $file, $contents)) {
                    return false;
                }
            }
        } catch (\Throwable $e) {
            return false;
        }
        return true;
    }

    protected function handleAttachments(Attachment $attachment)
    {
        $oldFile = $attachment->file;
        $attachment->update(['title' => 'Demo-Anhang ' . $attachment->id]);
        if (substr($attachment->mimeType, 0, 5) == 'image') {
            $attachment->update(['file' => 'demo/demo.jpg']);
        } else {
            $attachment->update(['file' => 'demo/demo.pdf']);
        }
        if (('demo' != substr($oldFile, 0, 4)) && Storage::exists($oldFile)) {
            Storage::delete($oldFile);
        }
    }

    protected function handleBaptisms(Baptism $baptism)
    {
        $data = [
                             'candidate_name' => $this->faker->name,
                             'candidate_address' => $this->faker->streetAddress,
                             'candidate_zip' => $this->faker->postcode,
                             'candidate_city' => $this->faker->city,
                             'candidate_phone' => $this->faker->phoneNumber,
                             'candidate_email' => $this->faker->email,
                             'first_contact_with' => $this->faker->name,
                             'notes' => $this->faker->text(),
                             'dimissorial_issuer' => 'Pfarramt ' . $this->faker->city,
                             'birth_place' => $this->faker->city,
                         ];
        if ($baptism->first_contact_on) {
            $data['first_contact_on'] = $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d');
        }
        if ($baptism->appointment) {
            $data['appointment'] = $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s');
        }
        if ($baptism->docs_where) {
            $data['docs_where'] = 'Demo-Dokumentation';
        }
        if ($baptism->dimissorial_requested) {
            $data['dimissorial_requested'] = $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d');
        }
        if ($baptism->dimissorial_received) {
            $data['dimissorial_received'] = $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d');
        }
        if ($baptism->dob) {
            $data['dob'] = $this->faker->dateTimeBetween('-95 years', '-1 year')->format('Y-m-d');
        }
        $baptism->update($data);
    }

    protected function handleBookings(Booking $booking)
    {
        $booking->update([
                             'code' => Booking::createCode(),
                             'name' => $this->faker->lastName,
                             'first_name' => $this->faker->firstName,
                             'contact' => $this->faker->phoneNumber,
                             'email' => 'demo-booking-' . $booking->id . '@demo.pfarrplaner.de',
                         ]);
    }

    protected function handleCities(City $city)
    {
        $oldName = $city->name;
        $newName = $this->faker->city;

        $city->update([
                          'name' => $newName,
                          'public_events_calendar_url' => '',
                          'op_domain' => '',
                          'op_customer_key' => '',
                          'op_customer_token' => '',
                          'podcast_title' => '',
                          'podcast_logo' => '',
                          'sermon_default_image' => '',
                          'homepage' => '',
                          'podcast_owner_name' => '',
                          'podcast_owner_email' => '',
                          'google_auth_code' => '',
                          'google_access_token' => '',
                          'google_refresh_token' => '',
                          'youtube_channel_url' => '',
                          'konfiapp_apikey' => '',
                          'youtube_active_stream_id' => '',
                          'youtube_passive_stream_id' => '',
                          'youtube_auto_startstop' => null,
                          'youtube_cutoff_days' => null,
                          'default_offering_url' => '',
                          'youtube_self_declared_for_children' => null,
                          'communiapp_url' => '',
                          'communiapp_token' => '',
                          'communiapp_default_group_id' => null,
                          'communiapp_use_outlook' => null,
                          'communiapp_use_op' => null,
                          'konfiapp_default_type' => '',
                          'official_name' => 'Evangelische Kirchengemeinde ' . $newName,
                          'logo' => '',
                          'default_ministries' => [],
                          'iban' => '',
                          'bic' => '',
                      ]);

        $locations = Location::where('city_id', $city->id)->get();
        /** @var Location $location */
        foreach ($locations as $location) {
            $locationData = ['name' => str_replace($oldName, $newName, $location->name)];
            if ($location->at_text) {
                $locationData['at_text'] = 'Demo-Ort ' . $newName;
            }
            if ($location->instructions) {
                $locationData['instructions'] = $this->faker->text();
            }
            $location->update($locationData);
        }
    }

    protected function handleComments(Comment $comment)
    {
        $comment->delete();
    }

    protected function handleFunerals(Funeral $funeral)
    {
        $data = [
                             'buried_name' => $this->faker->name,
                             'buried_address' => $this->faker->streetAddress,
                             'buried_zip' => $this->faker->postcode,
                             'buried_city' => $this->faker->city,
                             'relative_name' => $this->faker->name,
                             'relative_address' => $this->faker->streetAddress,
                             'relative_zip' => $this->faker->postcode,
                             'relative_city' => $this->faker->city,
                             'relative_contact_data' => $this->faker->phoneNumber,
                             'appointment' => $funeral->appointment
                                 ? Carbon::instance($this->faker->dateTimeBetween('-1 year', 'now'))
                                 : null,
                             'dob' => Carbon::instance($this->faker->dateTimeBetween('-95 years', '-70 years')),
                             'dod' => $funeral->dod
                                 ? Carbon::instance($this->faker->dateTimeBetween('-1 year', 'now'))
                                 : null,
                             'spouse' => $this->faker->name,
                             'parents' => $this->faker->name('male') . ' / ' . $this->faker->name('female'),
                             'children' => join(', ', [$this->faker->name, $this->faker->name, $this->faker->name]),
                             'further_family' => join(', ', [$this->faker->name, $this->faker->name]),
                             'baptism' => '',
                             'confirmation' => '',
                             'undertaker' => $this->faker->name . ' (' . $this->faker->phoneNumber . ')',
                             'eulogies' => '',
                             'notes' => $this->faker->text(),
                             'announcements' => $this->faker->text(),
                             'childhood' => $this->faker->text(),
                             'profession' => '',
                             'family' => $this->faker->text(),
                             'further_life' => $this->faker->text(),
                             'faith' => $this->faker->text(),
                             'events' => $this->faker->text(),
                             'character' => $this->faker->text(),
                             'death' => $this->faker->text(),
                             'life' => $this->faker->text(),
                             'attending' => join(', ', [$this->faker->name, $this->faker->name, $this->faker->name]
                             ),
                             'quotes' => $this->faker->text(),
                             'spoken_name' => '',
                             'professional_life' => $this->faker->text(),
                             'birth_place' => $this->faker->city,
                             'death_place' => $this->faker->city,
                             'dimissorial_issuer' => 'Pfarramt ' . $this->faker->city,
                             'birth_name' => $this->faker->lastName,
                             'appointment_address' => $this->faker->address,
                             'confirmation_text' => $this->faker->sentence,
                             'wedding_text' => $this->faker->sentence,
                         ];
        foreach (['wake', 'announcement', 'baptism_date', 'confirmation_date', 'wedding_date', 'dod_spouse',
                  'dimissorial_requested', 'dimissorial_received'] as $field) {
            if ($funeral->{$field}) {
                $data[$field] = Carbon::instance($this->faker->dateTimeBetween('-1 year', 'now'));
            }
        }
        if ($funeral->wake_location) {
            $data['wake_location'] = 'Demo-Ort';
        }
        $funeral->update($data);
    }

    protected function handleParishes(Parish $parish)
    {
        $parish->load('owningCity');
        $name = str_replace('Pfarramt ', '', $parish->name);
        $data = [
            'code' => trim('Pfarramt ' . $parish->owningCity->name . ' ' . $name),
            'address' => $this->faker->streetAddress,
            'zip' => $this->faker->postcode,
            'city' => $parish->owningCity->name,
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->email,
        ];
        if ($parish->assistant) {
            $data['assistant'] = $this->faker->name;
        }
        if ($parish->congregation_name) {
            $data['congregation_name'] = 'Evangelische Kirchengemeinde ' . $parish->owningCity->name;
        }
        if ($parish->congregation_url) {
            $data['congregation_url'] = 'https://demo.pfarrplaner.de/gemeinde/' . $parish->id;
        }
        if ($parish->opening_hours) {
            $data['opening_hours'] = 'Montag bis Freitag, 9:00 bis 12:00 Uhr';
        }
        $parish->update($data);
    }

    protected function handlePools(Pool $pool)
    {
        $pool->update([
                          'contact' => $this->faker->name,
                          'office' => '',
                          'phone' => $this->faker->phoneNumber,
                          'email' => 'demo-pool-' . $pool->id . '@demo.pfarrplaner.de',
                      ]);
    }

    protected function handleServices(Service $service)
    {
        $data = [
            'internal_remarks' => '',
            'registration_phone' => '',
        ];
        if ($service->special_location) {
            if (str_contains($service->special_location, 'kirche')) {
                $data['special_location'] = 'Allerheiligenkirche ' . $service->city->name;
            } else {
                $data['special_location'] = 'Auf der grünen Wiese';
            }
        }
        $loremFields = [
            'description',
            'others',
            'sermon_description',
            'announcements',
            'offering_text',
            'youtube_prefix_description',
            'youtube_postfix_description',
            'ad_text',
        ];
        foreach ($loremFields as $field) {
            if ($service->{$field}) {
                $data[$field] = $this->faker->text();
            }
        }
        if ($service->cc_staff) {
            $data['cc_staff'] = $this->faker->name . ', ' . $this->faker->name;
        }
        if ($service->cc_location) {
            $data['cc_location'] = 'Demo-Ort';
        }
        if ($service->title) {
            $data['title'] = 'Gottesdienst Nr. ' . $service->id;
        }
        foreach (['youtube_url', 'cc_streaming_url', 'offerings_url', 'meeting_url', 'recording_url', 'external_url'] as $field) {
            if ($service->{$field}) {
                $data[$field] = 'https://demo.pfarrplaner.de/services/' . $service->id;
            }
        }
        if ($service->songsheet) {
            $data['songsheet'] = 'demo/demo.pdf';
        }
        if ($service->sermon_title) {
            $data['sermon_title'] = 'Predigttitel Nr. ' . $service->id;
        }
        if ($service->sermon_image) {
            $data['sermon_image'] = 'demo/demo.jpg';
        }
        if ($service->konfiapp_event_qr) {
            $data['konfiapp_event_qr'] = 'demo-event-' . $service->id;
        }
        $service->update($data);
    }

    protected function handleStreetRanges(StreetRange $streetRange)
    {
        $streetRange->delete();
    }

    protected function prepUsers()
    {
        // allow non-unique api_token
        try {
            if ($this->hasIndex('users', 'users_api_token_unique')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropUnique('users_api_token_unique');
                });
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    protected function handleUsers(User $user)
    {
        $data = [
            'first_name' => $user->last_name == 'Admin' ? 'Demo' : $this->faker->firstName,
            'last_name' => $user->last_name == 'Admin' ? 'Admin' : $this->faker->lastName,
            'address' => $this->faker->address,
            'phone' => $this->faker->phoneNumber,
            'office' => '',
            'own_website' => '',
            'own_podcast_title' => '',
            'own_podcast_url' => '',
            'own_podcast_spotify' => false,
            'own_podcast_itunes' => false,
            'api_token' => Str::random(60),
            'remember_token' => Str::random(60),
            'email' => 'demo-user-' . $user->id . '@demo.pfarrplaner.de',
            'image' => '',
        ];
        if ($user->password != '') {
            $data['password'] = 'test';
        }
        $user->update($data);
        $user->calendarConnections()->delete();
    }

    protected function handleWeddings(Wedding $wedding)
    {
        $data = [
                             'spouse1_name' => $this->faker->name('male'),
                             'spouse1_phone' => $this->faker->phoneNumber,
                             'spouse1_email' => $this->faker->email,
                             'spouse1_birth_name' => $this->faker->lastName,
                             'spouse2_name' => $this->faker->name('female'),
                             'spouse2_phone' => $this->faker->phoneNumber,
                             'spouse2_email' => $this->faker->email,
                             'spouse2_birth_name' => $this->faker->lastName,
                             'spouse1_dob' => $this->faker->date(),
                             'spouse1_address' => $this->faker->streetAddress,
                             'spouse1_zip' => $this->faker->postcode,
                             'spouse1_city' => $this->faker->city,
                             'spouse1_dimissorial_issuer' => 'Pfarramt ' . $this->faker->city,
                             'spouse2_dob' => $this->faker->date(),
                             'spouse2_address' => $this->faker->streetAddress,
                             'spouse2_zip' => $this->faker->postcode,
                             'spouse2_city' => $this->faker->city,
                             'spouse2_dimissorial_issuer' => 'Pfarramt ' . $this->faker->city,
                             'notes' => $this->faker->text(),
                             'music' => $this->faker->text(),
                             'gift' => $this->faker->text(),
                             'flowers' => $this->faker->text(),
                         ];
        foreach (['spouse1_dimissorial_requested', 'spouse1_dimissorial_received',
                  'spouse2_dimissorial_requested', 'spouse2_dimissorial_received',
                  'permission_requested', 'permission_received'] as $field) {
            if ($wedding->{$field}) {
                $data[$field] = $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d');
            }
        }
        if ($wedding->appointment) {
            $data['appointment'] = $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s');
        }
        if ($wedding->registration_document) {
            $data['registration_document'] = 'Demo-Dokument';
        }
        if ($wedding->docs_where) {
            $data['docs_where'] = 'Demo-Dokumentation';
        }
        $wedding->update($data);
    }

    protected function anonymizeLiturgyData(): bool
    {
        try {
            if (Schema::hasTable('liturgy_blocks')) {
                foreach (Block::cursor() as $block) {
                    if ($block->instructions) {
                        $block->update(['instructions' => $this->faker->text()]);
                    }
                }
            }

            if (Schema::hasTable('liturgy_items')) {
                foreach (Item::cursor() as $item) {
                    $data = $item->data;
                    $changed = false;
                    if (is_array($data) && isset($data['responsible']) && is_array($data['responsible'])) {
                        foreach ($data['responsible'] as $index => $participant) {
                            if (is_array($participant)) {
                                if (($participant['type'] ?? null) === 'free' && !empty($participant['name'])) {
                                    $data['responsible'][$index]['name'] = $this->faker->name;
                                    $changed = true;
                                }
                            } elseif (is_string($participant)) {
                                if (str_starts_with($participant, 'free:')) {
                                    $data['responsible'][$index] = 'free:' . $this->faker->name;
                                    $changed = true;
                                } elseif (!str_contains($participant, ':')) {
                                    $data['responsible'][$index] = $this->faker->name;
                                    $changed = true;
                                }
                            }
                        }
                    }
                    $update = [];
                    if ($item->instructions) {
                        $update['instructions'] = $this->faker->text();
                    }
                    if ($changed) {
                        $item->data = $data;
                        $update['serialized_data'] = $item->getAttributes()['serialized_data'];
                    }
                    if ($update) {
                        $item->update($update);
                    }
                }
            }
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    protected function anonymizeOperationalData(): bool
    {
        try {
            foreach (['sessions', 'password_resets', 'visits', 'personal_access_tokens', 'failed_jobs'] as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }
            if (Schema::hasTable('telescope_entries_tags')) {
                DB::table('telescope_entries_tags')->delete();
            }
            if (Schema::hasTable('telescope_entries')) {
                DB::table('telescope_entries')->delete();
            }
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    protected function hasIndex(string $table, string $index): bool
    {
        if (method_exists(Schema::getConnection()->getSchemaBuilder(), 'getIndexes')) {
            return collect(Schema::getIndexes($table))->contains('name', $index);
        }

        return array_key_exists(
            $index,
            Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes($table)
        );
    }
}
