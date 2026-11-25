<?php

use App\Models\Scopes\ServicesOnlyScope;
use App\Models\Service;
use App\Services\LiturgyService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;

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
        foreach (Service::withoutGlobalScope(ServicesOnlyScope::class)
            ->whereNotNull('alt_liturgy_date')->whereNull('liturgy_info_id')->get() as $service) {
            /** @var Collection<int, object{id: int}> $x */
            $x = LiturgyService::getLiturgyInfoByDate((string) ($service->alt_liturgy_date ?? $service->date));
            if (count($x)) {
                $liturgyInfo = $x->first();
                if ($liturgyInfo !== null) {
                    $service->update(['liturgy_info_id' => $liturgyInfo->id]);
                }
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
