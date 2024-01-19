<?php

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\People\User;
use App\Models\Rites\Baptism;
use App\Models\Rites\Funeral;
use App\Models\Rites\Wedding;
use App\Models\Service;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->text('default_ministries')->nullable()->default('')->change();
        });

        // adapt pivot tables to new model namespaces
        Attachment::where('attachable_type', 'App\\Service')
            ->update(['attachable_type' => Service::class]);
        Attachment::where('attachable_type', 'App\\Baptism')
            ->update(['attachable_type' => Baptism::class]);
        Attachment::where('attachable_type', 'App\\Funeral')
            ->update(['attachable_type' => Funeral::class]);
        Attachment::where('attachable_type', 'App\\Wedding')
            ->update(['attachable_type' => Wedding::class]);
        Comment::where('commentable_type', 'App\\Service')
            ->update(['commentable_type' => Service::class]);
        Comment::where('commentable_type', 'App\\Baptism')
            ->update(['commentable_type' => Baptism::class]);
        Comment::where('commentable_type', 'App\\Funeral')
            ->update(['commentable_type' => Funeral::class]);
        Comment::where('commentable_type', 'App\\Wedding')
            ->update(['commentable_type' => Wedding::class]);


        DB::table('model_has_roles')
            ->where('model_type', 'App\\User')
            ->update(['model_type' => User::class]);

        DB::table('revisions')
            ->update(['revisionable_type' => Service::class]);

        DB::table('visits')
            ->update(['visitable_type' => User::class, 'visitor_type' => User::class]);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
