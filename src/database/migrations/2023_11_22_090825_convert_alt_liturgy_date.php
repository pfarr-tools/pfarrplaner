<?php

use App\Models\Service;
use App\Services\LiturgyService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // event_class is introduced by a later migration than this data fix.
        foreach (Service::withoutGlobalScope(\App\Models\Scopes\ServicesOnlyScope::class)
                     ->whereNotNull('alt_liturgy_date')->whereNull('liturgy_info_id')->get() as $service) {
            $x = LiturgyService::getLiturgyInfoByDate($service->alt_liturgy_date);
            if (count($x)) {
                $service->update(['liturgy_info_id' => $x->first()->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
