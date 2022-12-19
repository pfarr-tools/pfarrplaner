<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Liturgy\Item;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $items = Item::where('data_type', 'freetext')->get();
        foreach ($items as $item) {
            $data = $item->data;
            if (str_contains($data['description'], "\n")) {
                $data['description'] = '<p>'.str_replace("\n", '<br>', str_replace("\r\n", '</p><p>', $data['description'])).'</p>';
                $item->data = $data;
                $item->save();
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
        $items = Item::where('data_type', 'freetext')->get();
        foreach ($items as $item) {
            $data = $item->data;
            if (str_contains($data['description'], "</p>")) {
                $data['description'] = strtr($data['description'], [
                    '</p>' => "\r\n",
                    '<br>' => "\n",
                    '<br/>' => "\n",
                    '<br />' => '\n',
                    '<p>' => '',
                ]);
                $item->data = $data;
                $item->save();
            }
        }
    }
};
