<?php

use App\Models\Places\City;
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
        Schema::create('ad_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug'); // explicitly NOT unique!
            $table->string('depends_on')->nullable()->default(null);
            $table->foreignIdFor(City::class);
            $table->timestamps();
        });

        Schema::table('services', function ($table) {
            $table->text('ad_text')->nullable()->default('');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_channels');
        Schema::table('services', function ($table) {
            $table->dropColumn('ad_text');
        });
    }
};
