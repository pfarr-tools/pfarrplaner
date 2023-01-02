<?php

use App\Liturgy\Text;
use Illuminate\Database\Migrations\Migration;
use Symfony\Component\Console\Output\ConsoleOutput;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $texts = Text::all();
        $consoleOutput = new ConsoleOutput();
        $consoleSection = $consoleOutput->section();
        $consoleSection->write('<comment>Converting</comment> linebreaks in liturgical texts (1 / '.count($texts).')...');
        $ct = 0;
        foreach ($texts as $text) {
            $ct++;
            $consoleSection->overwrite('<comment>Converting</comment> linebreaks in liturgical texts ('.$ct.' / '.count($texts).')...');

            if (str_contains($text->text, "\n")) {
                $text->update(['text' => '<p>'.str_replace("\n", '<br>', str_replace("\n\n", '</p><p>', $text->text)).'</p>']);
            }

        }
        $consoleSection->overwrite('<info>Converted</info> linebreaks in '.count($texts).' liturgical texts');
        dd('abort');
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }



};
