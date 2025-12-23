<?php

use App\Models\Ads\AdConfig;
use App\Models\Service;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (Service::whereNotNull('communiapp_listing_start')->get() as $service) {
            $diff = (int)$service->communiapp_listing_start->diffInDays($service->date);
            if ($diff) {
                AdConfig::updateOrCreate(['service_id' => $service->id, 'slug' => 'communiapp'], [
                    'service_id' => $service->id,
                    'slug' => 'communiapp',
                    'offset' => $diff,
                    'ad_text' => ''
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
