<?php

use App\Baptism;
use App\Funeral;
use App\Liturgy\PronounSets\AbstractPronounSet;
use App\Liturgy\PronounSets\PronounSets;
use App\Service;
use App\Services\NameService;
use App\Wedding;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Liturgy\Item;
use Illuminate\Support\Str;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $pdo = DB::connection()->getPdo();

        $console = new \Symfony\Component\Console\Output\ConsoleOutput();

        foreach ($this->getModels() as $model) {
            $ct = 1;
            $records = $model['class']::all();
            $consoleSection = $console->section();
            $consoleSection->write('<comment>Decrypting</comment> '.$model['table'].' [1 / '.count($records).']...');
            foreach ($records as $record) {
                $consoleSection->overwrite('<comment>Decrypting</comment> '.$model['table'].' ['.$ct.' / '.count($records).']...');
                $update = [];
                foreach ($model['encrypted'] as $field) {
                    $value = $record->$field ?? '';
                    if ($model['table'] == 'user_settings') {
                        if (is_string($value)) {
                            $value = $this->unquote($value);
                        }
                        if (!is_string($value)) $value = '_____' . serialize($value);
                    } else {
                        $value = $this->unquote($value);
                    }
                    $update[$field] = $value;
                }
                DB::table($model['table'])->where('id', $record['id'])->update($update);
                $ct++;
            }

            $consoleSection->overwrite('<info>Success:</info> Decrypted '.count($records).' '.$model['table'].'.');
        }
    }

    private function unquote($value) {
        while (substr($value, 0, 1) == '\'') $value=substr($value, 1);
        while (substr($value, -1) == '\'') $value=substr($value, 0, -1);
        while (substr($value, 0, 2) == '\\'.'\'') $value = substr($value, 2);
        while (substr($value, -2) == '\\'.'\'') $value = substr($value, 0, -2);
        return $value;
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }



    private function getModels() {
        $models = [];
        foreach (glob(app_path('*.php')) as $file) {
            $class = 'App\\'.pathinfo($file, PATHINFO_FILENAME);
            $instance = new $class();
            $reflectionClass = new ReflectionClass($class);
            if ($reflectionClass->hasProperty('encrypted')) {
                $models[] = [
                    'class' => $class,
                    'classShort' => ucfirst(pathinfo($file, PATHINFO_FILENAME)),
                    'table' => $reflectionClass->getProperty('table')->getValue($instance) ?? Str::snake(Str::plural(pathinfo($file, PATHINFO_FILENAME))),
                    'encrypted' => $reflectionClass->getProperty('encrypted')->getValue($instance),
                ];
            }
        }
        return $models;
    }


};
