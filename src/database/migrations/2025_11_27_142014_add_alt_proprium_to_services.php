<?php

use App\Models\LiturgyInfo;
use App\Models\Service;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{


    private function translateToCode($title): string {
        $table = [
            "Nikolaustag" => "NIKO",
            "1. Advent" => "1ADV",
            "2. Advent" => "2ADV",
            "3. Advent" => "3ADV",
            "4. Advent" => "4ADV",
            "Christvesper" => "CHRV",
            "Christnacht" => "CHRN",
            "Christfest I" => "CHF1",
            "Christfest II" => "CHF2",
            "Altjahresabend" => "ALTJ",
            "Neujahrstag" => "NEUJ",
            "Epiphanias (Erscheinungsfest)" => "EPIP",
            "1. So. nach Epiphanias" => "1EPI",
            "2. So. nach Epiphanias" => "2EPI",
            "3. So. nach Epiphanias" => "3EPI",
            "letzter So. nach Epiphanias" => "LEPI",
            "4. So. v. d. Passionszeit" => "4EPI",
            "Septuagesimä" => "SEPT",
            "Sexagesimä" => "SEXA",
            "Estomihi" => "ESTO",
            "Invocavit" => "INVO",
            "Reminiszere" => "REMI",
            "Okuli" => "OKUL",
            "Lätare" => "LAET",
            "Judika" => "JUDI",
            "Palmarum / Palmsonntag" => "PALM",
            "Gründonnerstag" => "GRDO",
            "Karfreitag" => "KARF",
            "Karsamstag" => "KARS",
            "Osternacht" => "OSTN",
            "Ostersonntag" => "OSTS",
            "Ostermontag" => "OSTM",
            "Quasimodogeniti" => "QUAS",
            "Misericordias Domini" => "MISD",
            "Jubilate" => "JUBI",
            "Kantate" => "KANT",
            "Rogate" => "ROGA",
            "Christi Himmelfahrt" => "CHHF",
            "Exaudi" => "EXAU",
            "Pfingstsonntag" => "PFIS",
            "Pfingstmontag" => "PFIM",
            "Trinitatis" => "TRIN",
            "1. So. n. Trinitatis" => "1TRI",
            "2. So. n. Trinitatis" => "2TRI",
            "3. So. n. Trinitatis" => "3TRI",
            "4. So. n. Trinitatis" => "4TRI",
            "Tag der Geburt Johannes des Täufers (Johannis)" => "JOHA",
            "5. So. n. Trinitatis" => "5TRI",
            "6. So. n. Trinitatis" => "6TRI",
            "7. So. n. Trinitatis" => "7TRI",
            "8. So. n. Trinitatis" => "8TRI",
            "9. So. n. Trinitatis" => "9TRI",
            "10. So. n. Trinitatis" => "10TR",
            "11. So. n. Trinitatis" => "11TR",
            "12. So. n. Trinitatis" => "12TR",
            "13. So. n. Trinitatis" => "13TR",
            "14. So. n. Trinitatis" => "14TR",
            "15. So. n. Trinitatis" => "15TR",
            "16. So. n. Trinitatis" => "16TR",
            "17. So. n. Trinitatis" => "17TR",
            "18. So n. Trinitatis" => "18TR",
            "Erntedank" => "ERNT",
            "19. So. n. Trinitatis" => "19TR",
            "20. So. n. Trinitatis" => "20TR",
            "Reformationsfest" => "REFO",
            "Drittl.S.d.Kj." => "DRTL",
            "Buß-und Bettag" => "BUBE",
            "Ewigkeitssonntag" => "EWIG",
            "Totensonntag" => "TOTE",
            "Aschermittwoch" => "ASCH",
            "Michaelistag" => "MICH",
            "Martinstag" => "MART",
            "21. So. n. Trinitatis" => "21TR",
            "22. So. n. Trinitatis" => "22TR",
            "Vorletzter Sonntag d. Kj." => "VORL",
            "1. So. n. Christfest" => "1NCF",
            "23. So. n. Trinitatis" => "23TR",
            "2. So. nach Christfest" => "2NCF",
        ];
        return $table[$title] ?? '';
    }



    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('alt_proprium')->nullable()->default(null);
        });

        $migrateIds = Service::select(['id', 'liturgy_info_id'])->whereNotNull('liturgy_info_id')->get()->pluck('liturgy_info_id', 'id');
        foreach ($migrateIds as $serviceId => $liturgyInfoId) {
            $lit = LiturgyInfo::find($liturgyInfoId);
            Service::find($serviceId)->update(['alt_proprium' => $this->translateToCode($lit->title).'-'.$lit->perikope]);
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('alt_proprium');
        });
    }
};
