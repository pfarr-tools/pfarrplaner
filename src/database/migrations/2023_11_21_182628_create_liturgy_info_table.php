<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('liturgy_info', function (Blueprint $table) {
            $table->id();
            $table->string('fk_lit_day_id')->nullable();
            $table->string('dayId')->nullable();
            $table->dateTime('date')->nullable();
            $table->string('calendarYear')->nullable();
            $table->string('title')->nullable();
            $table->text('litProfileText')->nullable();
            $table->text('litProfileGist')->nullable();
            $table->string('litColor')->nullable();
            $table->string('litColorName')->nullable();
            $table->string('litRitesImage')->nullable();
            $table->string('feastCircleName')->nullable();
            $table->text('litDetailsTeaser')->nullable();
            $table->text('litDetailsText')->nullable();
            $table->string('litDetailsYoutubeId')->nullable();
            $table->string('litDetailsImageSubline')->nullable();
            $table->string('litDetailsImageCopyright')->nullable();
            $table->string('litDetailsYoutubeCopyright')->nullable();
            $table->string('litDetailsImage')->nullable();
            $table->string('evangeliumId')->nullable();
            $table->string('evangeliumURL')->nullable();
            $table->string('evangeliumReader')->nullable();
            $table->string('litTextsLinkPrayer')->nullable();
            $table->string('litTextsWeeklyPsalm')->nullable();
            $table->string('litTextsWeeklyQuote')->nullable();
            $table->string('litTextsWeeklyQuoteText')->nullable();
            $table->string('litTextsEntryPsalm')->nullable();
            $table->string('litTextsOldTestament')->nullable();
            $table->string('litTextsEpistel')->nullable();
            $table->string('litTextsEvangelium')->nullable();
            $table->string('litTextsPreacher')->nullable();
            $table->string('litTextsHaleluja')->nullable();
            $table->string('litTextsPerikope1')->nullable();
            $table->string('litTextsPerikope2')->nullable();
            $table->string('litTextsPerikope3')->nullable();
            $table->string('litTextsPerikope4')->nullable();
            $table->string('litTextsPerikope5')->nullable();
            $table->string('litTextsPerikope6')->nullable();
            $table->string('litTextsSpeciality')->nullable();
            $table->text('litProfileExtended')->nullable();
            $table->string('litRitesTitle')->nullable();
            $table->string('litRitesSubtitle')->nullable();
            $table->text('litRitesTeaser')->nullable();
            $table->text('litRitesText')->nullable();
            $table->string('litRitesImageSubline')->nullable();
            $table->string('litRitesImageCopyright')->nullable();
            $table->string('perikope')->nullable();
            $table->string('litProfileImage')->nullable();
            $table->string('litTextsWeeklySongNbr')->nullable();
            $table->string('litDetailsHeadline')->nullable();
            $table->string('push_text')->nullable();
            $table->string('litTextsWeeklyQuoteLink')->nullable();
            $table->string('litTextsWeeklyPsalmLink')->nullable();
            $table->string('litTextsEntryPsalmLink')->nullable();
            $table->string('litTextsOldTestamentLink')->nullable();
            $table->string('litTextsEpistelLink')->nullable();
            $table->string('litTextsEvangeliumLink')->nullable();
            $table->string('litTextsPreacherLink')->nullable();
            $table->string('litTextsHalelujaLink')->nullable();
            $table->string('litTextsPerikope1Link')->nullable();
            $table->string('litTextsPerikope2Link')->nullable();
            $table->string('litTextsPerikope3Link')->nullable();
            $table->string('litTextsPerikope4Link')->nullable();
            $table->string('litTextsPerikope5Link')->nullable();
            $table->string('litTextsPerikope6Link')->nullable();
            $table->string('subjects')->nullable();
            $table->string('glossar')->nullable();
            $table->string('songs')->nullable();
            $table->string('ritesDownloads')->nullable();
            $table->string('control')->nullable();
            $table->string('nextDate')->nullable();
            $table->timestamps();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->unsignedBigInteger('liturgy_info_id')->nullable();
            $table->foreign('liturgy_info_id')->references('id')->on('liturgy_info');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['liturgy_info_id']);
            $table->dropColumn('liturgy_info_id');
        });
        Schema::dropIfExists('liturgy_info');
    }
};
