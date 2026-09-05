<?php

use App\Models\People\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\PersonalAccessToken;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        PersonalAccessToken::where('tokenable_type', 'App\\User')->update(['tokenable_type' => User::class]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PersonalAccessToken::where('tokenable_type', User::class)->update(['tokenable_type' => 'App\\User']);    }
};
