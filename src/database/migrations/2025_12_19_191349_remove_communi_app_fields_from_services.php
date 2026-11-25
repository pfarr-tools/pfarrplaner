<?php

use App\Models\Ads\AdConfig;
use App\Models\Service;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Überträgt bestehende communiapp-Einstellungen in die AdConfigs und entfernt die alten Spalten.
     */
    public function up(): void
    {
        foreach (Service::whereNotNull('communiapp_listing_start')->get() as $service) {
            $date = is_object($service->date) ? $service->date : Carbon\Carbon::parse($service->date);
            $attribute = $service->getAttribute('communiapp_listing_start');
            /** @var Carbon\Carbon $listStart */
            $listStart = is_object($attribute) ? $attribute : Carbon\Carbon::parse($attribute);
            $diff = (int) ($listStart->diffInDays($date));
            if ($diff) {
                AdConfig::updateOrCreate(['service_id' => $service->id, 'slug' => 'communiapp'], [
                    'service_id' => $service->id,
                    'slug' => 'communiapp',
                    'offset' => $diff,
                    'ad_text' => '',
                ]);
            }
        }
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['communiapp_listing_start', 'communiapp_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->date('communiapp_listing_start')->nullable();
            $table->unsignedBigInteger('communiapp_id')->nullable();
        });
    }
};
