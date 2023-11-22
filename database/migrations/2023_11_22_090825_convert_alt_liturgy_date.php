<?php

use App\Liturgy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Service;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (Service::whereNotNull('alt_liturgy_date')->whereNull('liturgy_info_id')->get() as $service) {
            $x = Liturgy::getLiturgyInfoByDate($service->alt_liturgy_date);
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
